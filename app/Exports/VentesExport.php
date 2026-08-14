<?php

namespace App\Exports;

use App\Models\Vente;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class VentesExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        private readonly Carbon $debut,
        private readonly Carbon $fin,
    ) {}

    public function collection()
    {
        return Vente::with(['client:id,nom', 'vendeur:id,nom'])
            ->whereBetween('date_vente', [$this->debut, $this->fin])
            ->orderBy('date_vente')
            ->get();
    }

    public function headings(): array
    {
        return ['N° vente', 'Date', 'Client', 'Vendeur', 'Montant total', 'Statut paiement'];
    }

    public function map($vente): array
    {
        return [
            $vente->id,
            $vente->date_vente->format('d/m/Y H:i'),
            $vente->client->nom ?? 'Client de passage',
            $vente->vendeur->nom ?? '-',
            (float) $vente->montant_total,
            $vente->statut_paiement,
        ];
    }
}
