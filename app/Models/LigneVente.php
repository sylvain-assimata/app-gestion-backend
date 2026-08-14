<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigneVente extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'vente_id',
        'produit_id',
        'quantite',
        'prix_unitaire',
    ];

    protected function casts(): array
    {
        return [
            'prix_unitaire' => 'decimal:2',
        ];
    }

    public function vente(): BelongsTo
    {
        return $this->belongsTo(Vente::class);
    }

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }

    public function sousTotal(): float
    {
        return round($this->quantite * (float) $this->prix_unitaire, 2);
    }
}
