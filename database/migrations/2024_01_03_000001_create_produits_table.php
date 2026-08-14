<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produits', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 150);
            $table->string('categorie', 100)->nullable();
            $table->decimal('prix_achat', 12, 2)->default(0);
            $table->decimal('prix_vente', 12, 2);
            $table->string('unite', 30)->default('unité');
            $table->timestamps();

            $table->index('nom');
            $table->index('categorie');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produits');
    }
};
