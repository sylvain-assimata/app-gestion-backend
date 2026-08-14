<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fournisseurs', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 150);
            $table->string('telephone', 30)->nullable();
            $table->string('adresse', 255)->nullable();
            $table->timestamps();

            $table->index('nom');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fournisseurs');
    }
};
