<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fournisseur_id')->constrained('fournisseurs')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('date_achat')->useCurrent();
            $table->decimal('montant_total', 12, 2);
            $table->timestamps();

            $table->index('date_achat');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achats');
    }
};
