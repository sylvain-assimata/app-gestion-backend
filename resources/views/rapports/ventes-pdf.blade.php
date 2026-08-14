<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 18px; margin-bottom: 0; }
        p.periode { color: #555; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #f2f2f2; }
        tfoot td { font-weight: bold; background: #f9f9f9; }
        .statut-payee { color: #1a7f37; }
        .statut-partielle { color: #b45309; }
        .statut-en_attente { color: #b91c1c; }
    </style>
</head>
<body>
    <h1>Récapitulatif des ventes</h1>
    <p class="periode">Période du {{ $debut->format('d/m/Y') }} au {{ $fin->format('d/m/Y') }}</p>

    <table>
        <thead>
            <tr>
                <th>N°</th>
                <th>Date</th>
                <th>Client</th>
                <th>Vendeur</th>
                <th>Montant</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($ventes as $vente)
                <tr>
                    <td>{{ $vente->id }}</td>
                    <td>{{ $vente->date_vente->format('d/m/Y H:i') }}</td>
                    <td>{{ $vente->client->nom ?? 'Client de passage' }}</td>
                    <td>{{ $vente->vendeur->nom ?? '-' }}</td>
                    <td>{{ number_format((float) $vente->montant_total, 2, ',', ' ') }}</td>
                    <td class="statut-{{ $vente->statut_paiement }}">{{ $vente->statut_paiement }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4">Total</td>
                <td colspan="2">{{ number_format((float) $total, 2, ',', ' ') }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
