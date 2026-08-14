<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fournisseur extends Model
{
    protected $fillable = [
        'nom',
        'telephone',
        'adresse',
    ];

    // Relation vers les achats ajoutée quand le module Achats sera développé :
    // public function achats(): HasMany { return $this->hasMany(Achat::class); }
}
