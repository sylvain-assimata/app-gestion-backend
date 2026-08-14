<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vente extends Model
{
    protected $fillable = [
        'client_id',
        'user_id',
        'date_vente',
        'montant_total',
        'statut_paiement',
    ];

    protected function casts(): array
    {
        return [
            'montant_total' => 'decimal:2',
            'date_vente' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function vendeur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneVente::class);
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class);
    }

    public function montantPaye(): float
    {
        return (float) $this->paiements()->sum('montant');
    }

    public function montantRestantDu(): float
    {
        return round((float) $this->montant_total - $this->montantPaye(), 2);
    }
}
