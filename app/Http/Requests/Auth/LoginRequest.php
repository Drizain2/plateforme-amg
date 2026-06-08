<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required|string',
            'remember' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'L\'email est requis.',
            'email.email' => 'L\'email doit être une adresse email valide.',
            'password.required' => 'Le mot de passe est requis.',
        ];
    }

    public function authenticate():User{
        $cretendials = $this->only('email', 'password');

        if(!Auth::attempt($cretendials, $this->boolean('remember'))) {
            throw  ValidationException::withMessages([
                'email' => 'Identifiant incorrects'
            ]);
        }
        $user = Auth::user();

        if(!$user->is_active){
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'Ce compte est desactivé'
            ]);
        }

        if(!$user->shop || !$user->shop->is_active){
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'La boutique associée à ce compte est desactivée'
            ]);
        }

        return $user;
    }
}
