<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AchatRequest;
use App\Models\Achat;
use App\Models\LigneAchat;
use App\Models\MouvementStock;
use App\Models\Produit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AchatController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Achat::with(['fournisseur:id,nom', 'utilisateur:id,nom'])->withCount('lignes');

        if ($fournisseurId = $request->query('fournisseur_id')) {
            $query->where('fournisseur_id', $fournisseurId);
        }

        $achats = $query->latest('date_achat')->paginate((int) $request->query('par_page', 20));

        return response()->json($achats);
    }

    public function show(Achat $achat): JsonResponse
    {
        return response()->json($achat->load(['fournisseur', 'utilisateur:id,nom', 'lignes.produit:id,nom,unite']));
    }

    /**
     * Enregistre un achat / une réception fournisseur : contrairement à une vente,
     * elle AUGMENTE le stock. Chaque ligne crée un mouvement de stock "entree" tracé.
     * Option "mettre_a_jour_prix_achat" pour répercuter le nouveau prix d'achat sur
     * la fiche produit (utile quand le fournisseur change ses tarifs).
     */
    public function store(AchatRequest $request): JsonResponse
    {
        $data = $request->validated();

        $achat = DB::transaction(function () use ($data, $request) {
            $montantTotal = 0;
            foreach ($data['lignes'] as $ligne) {
                $montantTotal += $ligne['quantite'] * $ligne['prix_unitaire'];
            }

            $achat = Achat::create([
                'fournisseur_id' => $data['fournisseur_id'],
                'user_id' => $request->user()->id,
                'date_achat' => now(),
                'montant_total' => $montantTotal,
            ]);

            foreach ($data['lignes'] as $ligne) {
                $produit = Produit::with('stock')->lockForUpdate()->findOrFail($ligne['produit_id']);

                LigneAchat::create([
                    'achat_id' => $achat->id,
                    'produit_id' => $produit->id,
                    'quantite' => $ligne['quantite'],
                    'prix_unitaire' => $ligne['prix_unitaire'],
                ]);

                if ($produit->stock) {
                    $produit->stock->increment('quantite', $ligne['quantite']);
                } else {
                    // Sécurité : si un produit existait sans ligne de stock (cas anormal)
                    $produit->stock()->create([
                        'quantite' => $ligne['quantite'],
                        'seuil_alerte' => 5,
                    ]);
                }

                MouvementStock::create([
                    'produit_id' => $produit->id,
                    'type' => 'entree',
                    'quantite' => $ligne['quantite'],
                    'motif' => "Achat #{$achat->id}",
                    'user_id' => $request->user()->id,
                    'created_at' => now(),
                ]);

                if (! empty($data['mettre_a_jour_prix_achat'])) {
                    $produit->update(['prix_achat' => $ligne['prix_unitaire']]);
                }
            }

            return $achat;
        });

        return response()->json(
            $achat->load(['fournisseur', 'lignes.produit:id,nom']),
            201
        );
    }
}
