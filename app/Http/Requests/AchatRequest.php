<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AchatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fournisseur_id' => ['required', 'exists:fournisseurs,id'],
            'lignes' => ['required', 'array', 'min:1'],
            'lignes.*.produit_id' => ['required', 'exists:produits,id'],
            'lignes.*.quantite' => ['required', 'integer', 'min:1'],
            'lignes.*.prix_unitaire' => ['required', 'numeric', 'min:0'],

            // Si vrai, met aussi à jour le prix_achat du produit avec ce nouveau prix
            'mettre_a_jour_prix_achat' => ['nullable', 'boolean'],
        ];
    }
}
