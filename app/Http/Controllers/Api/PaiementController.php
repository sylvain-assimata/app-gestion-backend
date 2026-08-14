<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaiementRequest;
use App\Models\Paiement;
use App\Models\Vente;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PaiementController extends Controller
{
    /**
     * Enregistre un paiement supplémentaire (règlement d'un impayé, acompte, etc.),
     * recalcule le statut de la vente et réduit le solde du client en conséquence.
     * POST /api/ventes/{vente}/paiements
     */
    public function store(PaiementRequest $request, Vente $vente): JsonResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $vente) {
            $vente = Vente::with('paiements')->lockForUpdate()->findOrFail($vente->id);

            $restantDuAvant = $vente->montantRestantDu();

            if ($data['montant'] > $restantDuAvant) {
                abort(422, "Le montant dépasse le solde restant dû ({$restantDuAvant}).");
            }

            Paiement::create([
                'vente_id' => $vente->id,
                'montant' => $data['montant'],
                'mode' => $data['mode'],
                'date_paiement' => now(),
            ]);

            $restantDuApres = round($restantDuAvant - $data['montant'], 2);

            $vente->statut_paiement = $restantDuApres <= 0 ? 'payee' : 'partielle';
            $vente->save();

            if ($vente->client_id) {
                $vente->client()->decrement('solde', $data['montant']);
            }
        });

        return response()->json(
            $vente->fresh()->load(['client', 'paiements'])
        );
    }
}
