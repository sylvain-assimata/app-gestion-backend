<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProduitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:150'],
            'categorie' => ['nullable', 'string', 'max:100'],
            'prix_achat' => ['required', 'numeric', 'min:0'],
            'prix_vente' => ['required', 'numeric', 'min:0'],
            'unite' => ['nullable', 'string', 'max:30'],
            // Quantité et seuil initiaux, utilisés uniquement à la création
            // pour initialiser la ligne de stock associée.
            'quantite_initiale' => ['nullable', 'integer', 'min:0'],
            'seuil_alerte' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
