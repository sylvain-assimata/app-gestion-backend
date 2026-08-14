<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientRequest;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    /**
     * Liste des clients, avec recherche par nom/téléphone et pagination.
     * Exemple : GET /api/clients?recherche=diallo&par_page=20
     */
    public function index(Request $request): JsonResponse
    {
        $query = Client::query();

        if ($recherche = $request->query('recherche')) {
            $query->where(function ($q) use ($recherche) {
                $q->where('nom', 'like', "%{$recherche}%")
                  ->orWhere('telephone', 'like', "%{$recherche}%");
            });
        }

        $clients = $query->orderBy('nom')
            ->paginate((int) $request->query('par_page', 20));

        return response()->json($clients);
    }

    public function store(ClientRequest $request): JsonResponse
    {
        $client = Client::create($request->validated());

        return response()->json($client, 201);
    }

    public function show(Client $client): JsonResponse
    {
        return response()->json($client);
    }

    public function update(ClientRequest $request, Client $client): JsonResponse
    {
        $client->update($request->validated());

        return response()->json($client);
    }

    /**
     * Suppression bloquée si le client a un solde non nul, pour éviter de perdre
     * la trace d'un impayé ou d'un avoir. Le module Ventes pourra aussi bloquer
     * la suppression si des ventes existent, une fois ce module développé.
     */
    public function destroy(Client $client): JsonResponse
    {
        if ((float) $client->solde !== 0.0) {
            return response()->json([
                'message' => 'Impossible de supprimer un client avec un solde non nul.',
            ], 422);
        }

        $client->delete();

        return response()->json(null, 204);
    }
}
