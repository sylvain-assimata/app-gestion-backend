<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lignes_achat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('achat_id')->constrained('achats')->cascadeOnDelete();
            $table->foreignId('produit_id')->constrained('produits')->restrictOnDelete();
            $table->integer('quantite');
            $table->decimal('prix_unitaire', 12, 2); // prix d'achat au moment de la commande
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lignes_achat');
    }
};
