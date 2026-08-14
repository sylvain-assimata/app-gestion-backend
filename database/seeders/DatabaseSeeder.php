<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $adminRole = Role::where('nom', 'admin')->first();

        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'nom' => 'Administrateur',
                // Mot de passe par défaut UNIQUEMENT pour le premier lancement local.
                // À changer immédiatement après la première connexion.
                'password' => Hash::make('ChangeMoi123!'),
                'role_id' => $adminRole->id,
            ]
        );
    }
}
