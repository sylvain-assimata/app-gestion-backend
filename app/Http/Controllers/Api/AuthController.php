<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Créer un compte utilisateur.
     * SÉCURITÉ : cette route ne fonctionne que tant qu'AUCUN utilisateur n'existe
     * encore — elle sert uniquement à créer le tout premier compte admin lors de
     * l'installation. Une fois ce compte créé, toute création de compte passe
     * obligatoirement par POST /api/utilisateurs (protégée par role:admin, cf.
     * UserController), pour qu'un tiers ne puisse jamais s'auto-créer un accès.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        if (User::query()->exists()) {
            return response()->json([
                'message' => "L'inscription libre est désactivée. Demandez à un administrateur de créer votre compte.",
            ], 403);
        }

        $data = $request->validated();

        $user = User::create([
            'nom' => $data['nom'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => $data['role_id'],
        ]);

        return response()->json([
            'message' => 'Compte créé avec succès.',
            'user' => $user->load('role'),
        ], 201);
    }

    /**
     * Connexion : vérifie les identifiants, applique un throttling anti brute-force,
     * puis retourne un token Sanctum à utiliser dans l'en-tête Authorization.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        // Clé de limitation basée sur l'email + l'IP : bloque après 5 échecs / minute
        $throttleKey = Str::lower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $secondes = RateLimiter::availableIn($throttleKey);

            return response()->json([
                'message' => "Trop de tentatives. Réessayez dans {$secondes} secondes.",
            ], 429);
        }

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($throttleKey, 60); // verrouille 60s après trop d'échecs
            return response()->json(['message' => 'Identifiants invalides.'], 401);
        }

        if (! $user->actif) {
            return response()->json(['message' => 'Ce compte est désactivé.'], 403);
        }

        RateLimiter::clear($throttleKey);

        // Un seul token actif par appareil : on peut nommer le token par device
        $token = $user->createToken($request->userAgent() ?? 'api-token')->plainTextToken;

        return response()->json([
            'user' => $user->load('role'),
            'token' => $token,
        ]);
    }

    /**
     * Déconnexion : révoque uniquement le token utilisé pour cette requête.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnecté.']);
    }

    /**
     * Retourne l'utilisateur authentifié (utile pour le frontend au chargement).
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user()->load('role'));
    }
}
