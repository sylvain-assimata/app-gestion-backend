<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MouvementStockRequest;
use App\Models\MouvementStock;
use App\Models\Produit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    /**
     * Liste des stocks avec leur produit, filtrable sur les seuls produits en alerte.
     * GET /api/stocks?alerte=1
     */
    public function index(Request $request): JsonResponse
    {
        $query = Produit::with('stock')->whereHas('stock');

        if ($request->boolean('alerte')) {
            $query->whereHas('stock', function ($q) {
                $q->whereColumn('quantite', '<=', 'seuil_alerte');
            });
        }

        $produits = $query->orderBy('nom')->paginate((int) $request->query('par_page', 20));

        return response()->json($produits);
    }

    /**
     * Historique des mouvements d'un produit.
     * GET /api/produits/{produit}/mouvements
     */
    public function historique(Produit $produit): JsonResponse
    {
        $mouvements = $produit->mouvementsStock()
            ->with('utilisateur:id,nom')
            ->latest('created_at')
            ->paginate(20);

        return response()->json($mouvements);
    }

    /**
     * Enregistre un mouvement de stock (entrée ou sortie) et met à jour la
     * quantité en une seule transaction, avec verrouillage de la ligne de stock
     * pour éviter les incohérences en cas d'accès concurrents (deux ventes en
     * même temps sur le même produit, par exemple).
     * POST /api/produits/{produit}/mouvements
     */
    public function ajusterStock(MouvementStockRequest $request, Produit $produit): JsonResponse
    {
        $data = $request->validated();

        $stock = DB::transaction(function () use ($data, $produit, $request) {
            $stock = $produit->stock()->lockForUpdate()->firstOrFail();

            if ($data['type'] === 'sortie' && $stock->quantite < $data['quantite']) {
                abort(422, "Stock insuffisant (disponible : {$stock->quantite}).");
            }

            $stock->quantite += $data['type'] === 'entree' ? $data['quantite'] : -$data['quantite'];
            $stock->save();

            MouvementStock::create([
                'produit_id' => $produit->id,
                'type' => $data['type'],
                'quantite' => $data['quantite'],
                'motif' => $data['motif'] ?? null,
                'user_id' => $request->user()->id,
                'created_at' => now(),
            ]);

            return $stock;
        });

        return response()->json($stock);
    }
}
