<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table des rôles applicatifs (admin, gerant, vendeur, comptable...).
     * Volontairement simple : un utilisateur a UN SEUL rôle (role_id sur users).
     * Suffisant pour une PME ; peut évoluer vers une relation many-to-many
     * si des permissions plus fines sont nécessaires plus tard.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 50)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
