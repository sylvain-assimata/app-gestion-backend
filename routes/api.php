<?php

use App\Http\Controllers\Api\AchatController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\FournisseurController;
use App\Http\Controllers\Api\PaiementController;
use App\Http\Controllers\Api\ProduitController;
use App\Http\Controllers\Api\RapportController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VenteController;
use Illuminate\Support\Facades\Route;

// --- Routes publiques ---
Route::post('/register', [AuthController::class, 'register']); // à protéger en prod, cf. AuthController
Route::post('/login', [AuthController::class, 'login']);

// --- Routes protégées (token Sanctum requis) ---
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Gestion des utilisateurs et des rôles : réservée aux admins
    Route::middleware('role:admin')->group(function () {
        Route::get('utilisateurs', [UserController::class, 'index']);
        Route::post('utilisateurs', [UserController::class, 'store']);
        Route::put('utilisateurs/{user}', [UserController::class, 'update']);
        Route::get('roles', [UserController::class, 'roles']);
    });

    // Clients : consultables et modifiables par admin, gerant et vendeur
    Route::middleware('role:admin,gerant,vendeur')->group(function () {
        Route::apiResource('clients', ClientController::class);
    });

    // Fournisseurs : réservés à admin, gerant et comptable (le vendeur n'en a pas besoin)
    Route::middleware('role:admin,gerant,comptable')->group(function () {
        Route::apiResource('fournisseurs', FournisseurController::class);
    });

    // Produits : consultables par tous les rôles métier, modifiables par admin/gerant
    Route::middleware('role:admin,gerant,vendeur,comptable')->group(function () {
        Route::get('produits', [ProduitController::class, 'index']);
        Route::get('produits/{produit}', [ProduitController::class, 'show']);
        Route::get('produits/{produit}/mouvements', [StockController::class, 'historique']);
        Route::get('stocks', [StockController::class, 'index']);
    });

    Route::middleware('role:admin,gerant')->group(function () {
        Route::post('produits', [ProduitController::class, 'store']);
        Route::put('produits/{produit}', [ProduitController::class, 'update']);
        Route::delete('produits/{produit}', [ProduitController::class, 'destroy']);
    });

    // Ajuster le stock (entrée/sortie) : accessible aussi au vendeur pour les sorties
    // liées aux ventes (le module Ventes appellera cette même logique en interne).
    Route::middleware('role:admin,gerant,vendeur')->group(function () {
        Route::post('produits/{produit}/mouvements', [StockController::class, 'ajusterStock']);
    });

    // Ventes & paiements : le vendeur encaisse au comptoir, le comptable règle les impayés
    Route::middleware('role:admin,gerant,vendeur,comptable')->group(function () {
        Route::get('ventes', [VenteController::class, 'index']);
        Route::get('ventes/{vente}', [VenteController::class, 'show']);
        Route::post('ventes', [VenteController::class, 'store']);
        Route::post('ventes/{vente}/paiements', [PaiementController::class, 'store']);
    });

    // Achats fournisseurs : réservés à admin, gerant, comptable (pas le vendeur)
    Route::middleware('role:admin,gerant,comptable')->group(function () {
        Route::get('achats', [AchatController::class, 'index']);
        Route::get('achats/{achat}', [AchatController::class, 'show']);
        Route::post('achats', [AchatController::class, 'store']);
    });

    // Rapports : consultation ouverte à admin, gerant, comptable
    Route::middleware('role:admin,gerant,comptable')->group(function () {
        Route::get('rapports/chiffre-affaires', [RapportController::class, 'chiffreAffaires']);
        Route::get('rapports/produits-plus-vendus', [RapportController::class, 'produitsPlusVendus']);
        Route::get('rapports/marges', [RapportController::class, 'marges']);
        Route::get('rapports/stocks-bas', [RapportController::class, 'stocksBas']);
        Route::get('rapports/export/ventes/pdf', [RapportController::class, 'exportVentesPdf']);
        Route::get('rapports/export/ventes/excel', [RapportController::class, 'exportVentesExcel']);
    });
});
