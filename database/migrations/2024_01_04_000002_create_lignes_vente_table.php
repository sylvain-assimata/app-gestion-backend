<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lignes_vente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vente_id')->constrained('ventes')->cascadeOnDelete();
            $table->foreignId('produit_id')->constrained('produits')->restrictOnDelete();
            $table->integer('quantite');
            $table->decimal('prix_unitaire', 12, 2); // copie du prix au moment de la vente
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lignes_vente');
    }
};
