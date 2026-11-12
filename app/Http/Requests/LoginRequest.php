<?php

namespace App\Http\Requests;

use App\Rules\TurnstileRule;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'                 => ['required', 'string', 'email'],
            'password'              => ['required', 'string'],
            'remember'              => ['nullable', 'boolean'],
            'cf_turnstile_response' => ['required', 'string', new TurnstileRule],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'                 => 'Email wajib diisi.',
            'email.email'                    => 'Format email tidak valid.',
            'password.required'              => 'Password wajib diisi.',
            'cf_turnstile_response.required' => 'Verifikasi keamanan Cloudflare wajib diselesaikan.',
        ];
    }
}
