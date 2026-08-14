<?php

namespace App\Http\Controllers\Api;

use App\Exports\VentesExport;
use App\Http\Controllers\Controller;
use App\Models\LigneVente;
use App\Models\Produit;
use App\Models\Vente;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class RapportController extends Controller
{
    /**
     * Résout et valide la période demandée (par défaut : les 30 derniers jours).
     * @return array{0: Carbon, 1: Carbon}
     */
    private function periode(Request $request): array
    {
        $debut = $request->query('debut')
            ? Carbon::parse($request->query('debut'))->startOfDay()
            : now()->subDays(30)->startOfDay();

        $fin = $request->query('fin')
            ? Carbon::parse($request->query('fin'))->endOfDay()
            : now()->endOfDay();

        return [$debut, $fin];
    }

    /**
     * Chiffre d'affaires sur la période + évolution jour par jour.
     * GET /api/rapports/chiffre-affaires?debut=2026-07-01&fin=2026-07-31
     */
    public function chiffreAffaires(Request $request): JsonResponse
    {
        [$debut, $fin] = $this->periode($request);

        $ventes = Vente::whereBetween('date_vente', [$debut, $fin]);

        $totalCA = (clone $ventes)->sum('montant_total');
        $nombreVentes = (clone $ventes)->count();

        $parJour = (clone $ventes)
            ->selectRaw('DATE(date_vente) as jour, SUM(montant_total) as total, COUNT(*) as nombre')
            ->groupBy('jour')
            ->orderBy('jour')
            ->get();

        return response()->json([
            'periode' => ['debut' => $debut->toDateString(), 'fin' => $fin->toDateString()],
            'chiffre_affaires_total' => round((float) $totalCA, 2),
            'nombre_ventes' => $nombreVentes,
            'panier_moyen' => $nombreVentes > 0 ? round($totalCA / $nombreVentes, 2) : 0,
            'par_jour' => $parJour,
        ]);
    }

    /**
     * Produits les plus vendus (quantité et chiffre d'affaires générés) sur la période.
     * GET /api/rapports/produits-plus-vendus?debut=&fin=&limite=10
     */
    public function produitsPlusVendus(Request $request): JsonResponse
    {
        [$debut, $fin] = $this->periode($request);
        $limite = (int) $request->query('limite', 10);

        $produits = LigneVente::query()
            ->join('ventes', 'ventes.id', '=', 'lignes_vente.vente_id')
            ->join('produits', 'produits.id', '=', 'lignes_vente.produit_id')
            ->whereBetween('ventes.date_vente', [$debut, $fin])
            ->selectRaw('produits.id, produits.nom, SUM(lignes_vente.quantite) as quantite_vendue, SUM(lignes_vente.quantite * lignes_vente.prix_unitaire) as chiffre_affaires')
            ->groupBy('produits.id', 'produits.nom')
            ->orderByDesc('quantite_vendue')
            ->limit($limite)
            ->get();

        return response()->json($produits);
    }

    /**
     * Marge brute réalisée sur la période : (prix de vente - prix d'achat au moment
     * de la vente n'étant pas stocké sur la ligne, on utilise le prix d'achat ACTUEL
     * du produit comme approximation raisonnable pour une PME).
     * GET /api/rapports/marges?debut=&fin=
     */
    public function marges(Request $request): JsonResponse
    {
        [$debut, $fin] = $this->periode($request);

        $lignes = LigneVente::query()
            ->join('ventes', 'ventes.id', '=', 'lignes_vente.vente_id')
            ->join('produits', 'produits.id', '=', 'lignes_vente.produit_id')
            ->whereBetween('ventes.date_vente', [$debut, $fin])
            ->selectRaw('produits.id, produits.nom,
                SUM(lignes_vente.quantite) as quantite_vendue,
                SUM(lignes_vente.quantite * lignes_vente.prix_unitaire) as chiffre_affaires,
                SUM(lignes_vente.quantite * produits.prix_achat) as cout_estime')
            ->groupBy('produits.id', 'produits.nom')
            ->get()
            ->map(function ($ligne) {
                $ligne->marge_estimee = round($ligne->chiffre_affaires - $ligne->cout_estime, 2);
                return $ligne;
            });

        return response()->json([
            'periode' => ['debut' => $debut->toDateString(), 'fin' => $fin->toDateString()],
            'marge_totale_estimee' => round($lignes->sum('marge_estimee'), 2),
            'detail' => $lignes,
        ]);
    }

    /**
     * Produits sous leur seuil d'alerte (raccourci pratique pour le dashboard rapports).
     * GET /api/rapports/stocks-bas
     */
    public function stocksBas(): JsonResponse
    {
        $produits = Produit::with('stock')
            ->whereHas('stock', fn ($q) => $q->whereColumn('quantite', '<=', 'seuil_alerte'))
            ->orderBy('nom')
            ->get();

        return response()->json($produits);
    }

    /**
     * Export PDF des ventes de la période (facture récapitulative).
     * GET /api/rapports/export/ventes/pdf?debut=&fin=
     */
    public function exportVentesPdf(Request $request): Response
    {
        [$debut, $fin] = $this->periode($request);

        $ventes = Vente::with(['client:id,nom', 'vendeur:id,nom'])
            ->whereBetween('date_vente', [$debut, $fin])
            ->orderBy('date_vente')
            ->get();

        $pdf = Pdf::loadView('rapports.ventes-pdf', [
            'ventes' => $ventes,
            'debut' => $debut,
            'fin' => $fin,
            'total' => $ventes->sum('montant_total'),
        ]);

        return $pdf->download("ventes_{$debut->toDateString()}_au_{$fin->toDateString()}.pdf");
    }

    /**
     * Export Excel des ventes de la période.
     * GET /api/rapports/export/ventes/excel?debut=&fin=
     */
    public function exportVentesExcel(Request $request)
    {
        [$debut, $fin] = $this->periode($request);

        return Excel::download(
            new VentesExport($debut, $fin),
            "ventes_{$debut->toDateString()}_au_{$fin->toDateString()}.xlsx"
        );
    }
}
