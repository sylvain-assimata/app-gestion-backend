<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete(); // vendeur
            $table->timestamp('date_vente')->useCurrent();
            $table->decimal('montant_total', 12, 2);
            $table->enum('statut_paiement', ['payee', 'partielle', 'en_attente'])->default('en_attente');
            $table->timestamps();

            $table->index('date_vente');
            $table->index('statut_paiement');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventes');
    }
};
