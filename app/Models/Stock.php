<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    protected $fillable = [
        'produit_id',
        'quantite',
        'seuil_alerte',
    ];

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }

    public function estEnAlerte(): bool
    {
        return $this->quantite <= $this->seuil_alerte;
    }
}
