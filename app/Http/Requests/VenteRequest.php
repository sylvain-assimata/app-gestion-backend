<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['nullable', 'exists:clients,id'],
            'lignes' => ['required', 'array', 'min:1'],
            'lignes.*.produit_id' => ['required', 'exists:produits,id'],
            'lignes.*.quantite' => ['required', 'integer', 'min:1'],

            // Paiement optionnel encaissé immédiatement (vente comptant totale ou partielle)
            'paiement' => ['nullable', 'array'],
            'paiement.montant' => ['required_with:paiement', 'numeric', 'min:0.01'],
            'paiement.mode' => ['required_with:paiement', Rule::in(['especes', 'mobile_money', 'carte', 'virement'])],
        ];
    }
}
