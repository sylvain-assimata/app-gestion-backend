<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'nom',
        'telephone',
        'adresse',
        'solde',
    ];

    protected function casts(): array
    {
        return [
            'solde' => 'decimal:2',
        ];
    }

    // Relation vers les ventes ajoutée quand le module Ventes sera développé :
    // public function ventes(): HasMany { return $this->hasMany(Vente::class); }
}
