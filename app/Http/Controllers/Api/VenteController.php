<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\VenteRequest;
use App\Models\LigneVente;
use App\Models\MouvementStock;
use App\Models\Paiement;
use App\Models\Produit;
use App\Models\Vente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VenteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Vente::with(['client:id,nom', 'vendeur:id,nom'])->withCount('lignes');

        if ($statut = $request->query('statut')) {
            $query->where('statut_paiement', $statut);
        }

        if ($clientId = $request->query('client_id')) {
            $query->where('client_id', $clientId);
        }

        $ventes = $query->latest('date_vente')->paginate((int) $request->query('par_page', 20));

        return response()->json($ventes);
    }

    public function show(Vente $vente): JsonResponse
    {
        return response()->json(
            $vente->load(['client', 'vendeur:id,nom', 'lignes.produit:id,nom,unite', 'paiements'])
        );
    }

    /**
     * Crée une vente complète :
     * 1) vérifie et décrémente le stock de chaque produit (avec verrou anti-concurrence)
     * 2) calcule le montant total à partir du prix de vente actuel des produits
     * 3) enregistre un paiement initial si fourni, et déduit le statut de paiement
     * 4) met à jour le solde du client si la vente n'est pas payée en totalité
     * Le tout dans une transaction unique : en cas d'erreur (ex. stock insuffisant),
     * rien n'est enregistré.
     */
    public function store(VenteRequest $request): JsonResponse
    {
        $data = $request->validated();

        $vente = DB::transaction(function () use ($data, $request) {
            $montantTotal = 0;
            $lignesAPreparer = [];

            foreach ($data['lignes'] as $ligne) {
                $produit = Produit::with('stock')->lockForUpdate()->findOrFail($ligne['produit_id']);
                $stock = $produit->stock;

                if (! $stock || $stock->quantite < $ligne['quantite']) {
                    $disponible = $stock->quantite ?? 0;
                    abort(422, "Stock insuffisant pour \"{$produit->nom}\" (disponible : {$disponible}).");
                }

                $prixUnitaire = (float) $produit->prix_vente;
                $montantTotal += $prixUnitaire * $ligne['quantite'];

                $lignesAPreparer[] = [
                    'produit' => $produit,
                    'stock' => $stock,
                    'quantite' => $ligne['quantite'],
                    'prix_unitaire' => $prixUnitaire,
                ];
            }

            $vente = Vente::create([
                'client_id' => $data['client_id'] ?? null,
                'user_id' => $request->user()->id,
                'date_vente' => now(),
                'montant_total' => $montantTotal,
                'statut_paiement' => 'en_attente', // recalculé plus bas
            ]);

            foreach ($lignesAPreparer as $item) {
                LigneVente::create([
                    'vente_id' => $vente->id,
                    'produit_id' => $item['produit']->id,
                    'quantite' => $item['quantite'],
                    'prix_unitaire' => $item['prix_unitaire'],
                ]);

                // Décrément du stock + traçabilité du mouvement
                $item['stock']->decrement('quantite', $item['quantite']);

                MouvementStock::create([
                    'produit_id' => $item['produit']->id,
                    'type' => 'sortie',
                    'quantite' => $item['quantite'],
                    'motif' => "Vente #{$vente->id}",
                    'user_id' => $request->user()->id,
                    'created_at' => now(),
                ]);
            }

            $montantPaye = 0;
            if (! empty($data['paiement'])) {
                Paiement::create([
                    'vente_id' => $vente->id,
                    'montant' => $data['paiement']['montant'],
                    'mode' => $data['paiement']['mode'],
                    'date_paiement' => now(),
                ]);
                $montantPaye = min((float) $data['paiement']['montant'], $montantTotal);
            }

            $this->mettreAJourStatutEtSolde($vente, $montantTotal, $montantPaye);

            return $vente;
        });

        return response()->json(
            $vente->load(['client', 'lignes.produit:id,nom', 'paiements']),
            201
        );
    }

    private function mettreAJourStatutEtSolde(Vente $vente, float $montantTotal, float $montantPayeInitial): void
    {
        $restantDu = round($montantTotal - $montantPayeInitial, 2);

        $vente->statut_paiement = match (true) {
            $restantDu <= 0 => 'payee',
            $montantPayeInitial > 0 => 'partielle',
            default => 'en_attente',
        };
        $vente->save();

        if ($vente->client_id && $restantDu > 0) {
            $vente->client()->increment('solde', $restantDu);
        }
    }
}
