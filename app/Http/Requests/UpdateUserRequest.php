<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => ['sometimes', 'string', 'max:100'],
            'role_id' => ['sometimes', 'exists:roles,id'],
            'actif' => ['sometimes', 'boolean'],
            // Changement de mot de passe optionnel (ex. réinitialisation par un admin)
            'password' => ['sometimes', 'nullable', Password::min(8)->mixedCase()->numbers()],
        ];
    }
}
