# Socle Backend — App Gestion PME (Laravel + Sanctum)

Ce dossier contient le **socle** de l'API : authentification, rôles, structure de base.
Les fichiers sont prêts à être copiés dans un projet Laravel fraîchement créé.

## Installation

```bash
# 1. Créer le projet Laravel (si ce n'est pas déjà fait)
composer create-project laravel/laravel app-gestion-api
cd app-gestion-api

# 2. Installer Sanctum
composer require laravel/sanctum
php artisan vendor:publish --tag=sanctum-migrations
php artisan vendor:publish --tag=sanctum-config

# 3. Copier les fichiers de ce dossier dans le projet en respectant les chemins :
#    app/Models/Role.php
#    app/Models/User.php            (remplace le modèle par défaut)
#    app/Http/Requests/RegisterRequest.php
#    app/Http/Requests/LoginRequest.php
#    app/Http/Controllers/Api/AuthController.php
#    app/Http/Middleware/CheckRole.php
#    database/migrations/2024_01_01_000001_create_roles_table.php
#    database/migrations/2024_01_01_000002_create_users_table.php (remplace la migration users par défaut)
#    database/seeders/RoleSeeder.php
#    database/seeders/DatabaseSeeder.php (remplace celui par défaut)
#    bootstrap/app.php (remplace celui par défaut)
#    routes/api.php

# 4. Configurer l'environnement
cp .env.example .env
php artisan key:generate
# Renseigner DB_DATABASE, DB_USERNAME, DB_PASSWORD dans .env

# 5. Créer la base de données puis lancer les migrations + seeders
php artisan migrate --seed

# 6. Démarrer le serveur de développement
php artisan serve
```

## Tester l'API

```bash
# Connexion avec le compte admin créé par le seeder
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"ChangeMoi123!"}'

# -> renvoie { "user": {...}, "token": "..." }
# Utiliser ensuite le token dans l'en-tête :
# Authorization: Bearer <token>

curl http://localhost:8000/api/me \
  -H "Authorization: Bearer <token>"
```

## Points de sécurité déjà en place
- Mots de passe hashés en bcrypt (jamais stockés en clair)
- Validation stricte des entrées (Form Requests)
- Throttling anti brute-force sur `/login` (5 tentatives / minute par email+IP)
- Tokens d'API révocables individuellement (Sanctum), un par appareil/session
- Rôles vérifiables via middleware `role:xxx` réutilisable sur toute nouvelle route
- ⚠️ À faire avant la mise en production : restreindre ou protéger `/register`
  (actuellement ouverte pour permettre la création du premier compte)

## Module Clients & Fournisseurs (inclus)

CRUD complet pour `clients` et `fournisseurs` :
- `GET /api/clients?recherche=xxx&par_page=20` (recherche + pagination)
- `POST /api/clients`, `GET /api/clients/{id}`, `PUT /api/clients/{id}`, `DELETE /api/clients/{id}`
- Mêmes routes pour `/api/fournisseurs`
- Accès `clients` : rôles `admin`, `gerant`, `vendeur`
- Accès `fournisseurs` : rôles `admin`, `gerant`, `comptable`
- Suppression d'un client bloquée si son solde n'est pas nul (protection contre la perte d'un impayé)

### Tester

```bash
curl -X POST http://localhost:8000/api/clients \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"nom":"Boutique Adjé","telephone":"90000000"}'

curl "http://localhost:8000/api/clients?recherche=Adje" \
  -H "Authorization: Bearer <token>"
```

## Module Produits & Stocks (inclus)

- `GET /api/produits?recherche=xxx&categorie=yyy` : liste avec stock inclus
- `POST /api/produits` : crée le produit **et** sa ligne de stock en une transaction
  (`quantite_initiale`, `seuil_alerte` optionnels à la création)
- `PUT/DELETE /api/produits/{id}` : réservé à `admin`, `gerant`
- `GET /api/stocks?alerte=1` : ne renvoie que les produits sous le seuil d'alerte
- `GET /api/produits/{id}/mouvements` : historique paginé des entrées/sorties
- `POST /api/produits/{id}/mouvements` : enregistre une entrée ou sortie de stock
  - Transaction avec verrou (`lockForUpdate`) pour éviter les incohérences en cas
    de ventes simultanées sur le même produit
  - Refuse une sortie si le stock est insuffisant (422)
  - Accessible à `admin`, `gerant`, `vendeur` (le vendeur en aura besoin au moment
    de la vente, cf. module suivant)

### Tester

```bash
# Créer un produit avec un stock initial de 50 et une alerte à 10
curl -X POST http://localhost:8000/api/produits \
  -H "Authorization: Bearer <token>" -H "Content-Type: application/json" \
  -d '{"nom":"Sac de riz 25kg","prix_achat":12000,"prix_vente":14000,"quantite_initiale":50,"seuil_alerte":10}'

# Enregistrer une sortie de stock (vente, casse, etc.)
curl -X POST http://localhost:8000/api/produits/1/mouvements \
  -H "Authorization: Bearer <token>" -H "Content-Type: application/json" \
  -d '{"type":"sortie","quantite":5,"motif":"Vente comptoir"}'

# Voir les produits en alerte de stock
curl "http://localhost:8000/api/stocks?alerte=1" -H "Authorization: Bearer <token>"
```

## Module Ventes & Paiements (inclus)

- `POST /api/ventes` : crée une vente avec plusieurs lignes de produits
  - Vérifie et **verrouille** le stock de chaque produit (`lockForUpdate`), refuse (422)
    si le stock est insuffisant pour l'un des produits
  - Calcule le montant total à partir du prix de vente **actuel** des produits
  - Décrémente le stock et trace chaque sortie dans `mouvements_stock`
  - Accepte un paiement initial optionnel (`paiement: {montant, mode}`)
  - Déduit automatiquement le statut : `payee`, `partielle` ou `en_attente`
  - Si la vente n'est pas payée intégralement et a un client, incrémente le solde du client
  - Tout est fait dans **une seule transaction** : en cas d'erreur, rien n'est enregistré
    (pas de stock décrémenté sans vente créée, etc.)
- `GET /api/ventes?statut=en_attente&client_id=3` : liste filtrable
- `GET /api/ventes/{id}` : détail avec lignes, paiements, client
- `POST /api/ventes/{id}/paiements` : encaisse un paiement supplémentaire (règlement
  d'un impayé), recalcule le statut et réduit le solde du client. Refuse (422) si le
  montant dépasse le solde restant dû.

### Tester

```bash
# Vente à un client, payée partiellement au comptant
curl -X POST http://localhost:8000/api/ventes \
  -H "Authorization: Bearer <token>" -H "Content-Type: application/json" \
  -d '{
    "client_id": 1,
    "lignes": [{"produit_id": 1, "quantite": 2}],
    "paiement": {"montant": 10000, "mode": "especes"}
  }'

# Régler le reste plus tard
curl -X POST http://localhost:8000/api/ventes/1/paiements \
  -H "Authorization: Bearer <token>" -H "Content-Type: application/json" \
  -d '{"montant": 18000, "mode": "mobile_money"}'
```

## Module Achats fournisseurs (inclus)

- `POST /api/achats` : réception fournisseur avec plusieurs lignes de produits
  - **Augmente** le stock (contrairement à une vente) et trace chaque entrée dans
    `mouvements_stock`
  - Option `mettre_a_jour_prix_achat: true` pour répercuter le nouveau prix sur la
    fiche produit
  - Réservé aux rôles `admin`, `gerant`, `comptable`
- `GET /api/achats?fournisseur_id=2` : liste filtrable
- `GET /api/achats/{id}` : détail avec lignes

### Tester

```bash
curl -X POST http://localhost:8000/api/achats \
  -H "Authorization: Bearer <token>" -H "Content-Type: application/json" \
  -d '{
    "fournisseur_id": 1,
    "lignes": [{"produit_id": 1, "quantite": 100, "prix_unitaire": 12500}],
    "mettre_a_jour_prix_achat": true
  }'
```

## Module Rapports & exports (inclus)

Toutes les routes acceptent `?debut=YYYY-MM-DD&fin=YYYY-MM-DD` (par défaut : 30
derniers jours). Réservées à `admin`, `gerant`, `comptable`.

- `GET /api/rapports/chiffre-affaires` : CA total, nombre de ventes, panier moyen,
  détail jour par jour
- `GET /api/rapports/produits-plus-vendus?limite=10` : top produits par quantité
  vendue, avec le CA généré par chacun
- `GET /api/rapports/marges` : marge brute estimée par produit et au total (calculée
  avec le prix d'achat **actuel** du produit — approximation raisonnable pour une
  PME qui ne suit pas de FIFO/CUMP strict)
- `GET /api/rapports/stocks-bas` : produits sous leur seuil d'alerte
- `GET /api/rapports/export/ventes/pdf` : téléchargement PDF du récapitulatif des
  ventes de la période (via `barryvdh/laravel-dompdf`)
- `GET /api/rapports/export/ventes/excel` : téléchargement Excel équivalent (via
  `maatwebsite/excel`)

### Installation des packages d'export

```bash
composer require barryvdh/laravel-dompdf maatwebsite/excel
```

Les deux packages exposent leur `ServiceProvider` automatiquement (auto-discovery
Laravel) — aucune configuration supplémentaire n'est nécessaire pour ce module.

### Tester

```bash
curl "http://localhost:8000/api/rapports/chiffre-affaires?debut=2026-07-01&fin=2026-07-31" \
  -H "Authorization: Bearer <token>"

curl "http://localhost:8000/api/rapports/export/ventes/pdf?debut=2026-07-01&fin=2026-07-31" \
  -H "Authorization: Bearer <token>" -o ventes-juillet.pdf
```

## Module Utilisateurs & rôles (inclus)

- `GET /api/utilisateurs` : liste des comptes de l'entreprise, avec leur rôle
- `POST /api/utilisateurs` : crée un compte pour un membre de l'équipe
- `PUT /api/utilisateurs/{id}` : modifie le nom, le rôle, le statut `actif`, ou
  réinitialise le mot de passe
- `GET /api/roles` : liste des rôles disponibles (pour peupler un formulaire)
- Toutes réservées au rôle `admin`

### Sécurité : `/register` maintenant verrouillée

`POST /api/register` ne fonctionne plus que **si aucun utilisateur n'existe
encore** (403 sinon) : elle ne sert qu'à créer le tout premier compte admin à
l'installation. Ensuite, toute création de compte passe par
`POST /api/utilisateurs`, protégée par `role:admin` — personne ne peut plus
s'auto-créer un accès.

## Prochain module suggéré
Le **déploiement en production** (VPS, HTTPS, migrations, build frontend).
