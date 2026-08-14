<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigneAchat extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'achat_id',
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

    public function achat(): BelongsTo
    {
        return $this->belongsTo(Achat::class);
    }

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }
}
