<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FournisseurRequest;
use App\Models\Fournisseur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FournisseurController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Fournisseur::query();

        if ($recherche = $request->query('recherche')) {
            $query->where('nom', 'like', "%{$recherche}%");
        }

        $fournisseurs = $query->orderBy('nom')
            ->paginate((int) $request->query('par_page', 20));

        return response()->json($fournisseurs);
    }

    public function store(FournisseurRequest $request): JsonResponse
    {
        $fournisseur = Fournisseur::create($request->validated());

        return response()->json($fournisseur, 201);
    }

    public function show(Fournisseur $fournisseur): JsonResponse
    {
        return response()->json($fournisseur);
    }

    public function update(FournisseurRequest $request, Fournisseur $fournisseur): JsonResponse
    {
        $fournisseur->update($request->validated());

        return response()->json($fournisseur);
    }

    public function destroy(Fournisseur $fournisseur): JsonResponse
    {
        $fournisseur->delete();

        return response()->json(null, 204);
    }
}
