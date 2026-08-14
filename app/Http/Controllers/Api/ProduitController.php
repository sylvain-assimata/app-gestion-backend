<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProduitRequest;
use App\Models\Produit;
use App\Models\Stock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProduitController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Produit::with('stock');

        if ($recherche = $request->query('recherche')) {
            $query->where('nom', 'like', "%{$recherche}%");
        }

        if ($categorie = $request->query('categorie')) {
            $query->where('categorie', $categorie);
        }

        $produits = $query->orderBy('nom')
            ->paginate((int) $request->query('par_page', 20));

        return response()->json($produits);
    }

    /**
     * Crée le produit ET sa ligne de stock associée en une seule transaction,
     * pour ne jamais se retrouver avec un produit sans stock.
     */
    public function store(ProduitRequest $request): JsonResponse
    {
        $data = $request->validated();

        $produit = DB::transaction(function () use ($data) {
            $produit = Produit::create([
                'nom' => $data['nom'],
                'categorie' => $data['categorie'] ?? null,
                'prix_achat' => $data['prix_achat'],
                'prix_vente' => $data['prix_vente'],
                'unite' => $data['unite'] ?? 'unité',
            ]);

            Stock::create([
                'produit_id' => $produit->id,
                'quantite' => $data['quantite_initiale'] ?? 0,
                'seuil_alerte' => $data['seuil_alerte'] ?? 5,
            ]);

            return $produit;
        });

        return response()->json($produit->load('stock'), 201);
    }

    public function show(Produit $produit): JsonResponse
    {
        return response()->json($produit->load('stock'));
    }

    public function update(ProduitRequest $request, Produit $produit): JsonResponse
    {
        $produit->update($request->safe()->only([
            'nom', 'categorie', 'prix_achat', 'prix_vente', 'unite',
        ]));

        return response()->json($produit->load('stock'));
    }

    public function destroy(Produit $produit): JsonResponse
    {
        $produit->delete(); // la ligne de stock est supprimée en cascade (cf. migration)

        return response()->json(null, 204);
    }
}
