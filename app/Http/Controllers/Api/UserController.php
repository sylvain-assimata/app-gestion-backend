<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Liste des utilisateurs de l'entreprise. Réservé aux admins (cf. routes/api.php).
     */
    public function index(): JsonResponse
    {
        return response()->json(User::with('role')->orderBy('nom')->get());
    }

    /**
     * Crée un compte pour un membre de l'équipe. Réutilise les mêmes règles de
     * validation que l'inscription publique (cf. AuthController::register), mais
     * cette route-ci est protégée par le middleware role:admin.
     */
    public function store(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'nom' => $data['nom'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => $data['role_id'],
        ]);

        return response()->json($user->load('role'), 201);
    }

    /**
     * Modifie le rôle, le statut actif/inactif, le nom, ou réinitialise le mot
     * de passe d'un utilisateur.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json($user->load('role'));
    }

    /**
     * Liste des rôles disponibles, utile pour peupler le formulaire côté frontend.
     */
    public function roles(): JsonResponse
    {
        return response()->json(Role::orderBy('nom')->get());
    }
}
