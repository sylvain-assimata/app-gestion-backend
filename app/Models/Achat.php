<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Achat extends Model
{
    protected $fillable = [
        'fournisseur_id',
        'user_id',
        'date_achat',
        'montant_total',
    ];

    protected function casts(): array
    {
        return [
            'montant_total' => 'decimal:2',
            'date_achat' => 'datetime',
        ];
    }

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneAchat::class);
    }
}
