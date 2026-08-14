<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 150);
            $table->string('telephone', 30)->nullable();
            $table->string('adresse', 255)->nullable();
            $table->decimal('solde', 12, 2)->default(0); // positif = client doit de l'argent
            $table->timestamps();

            $table->index('nom'); // accélère la recherche par nom
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
