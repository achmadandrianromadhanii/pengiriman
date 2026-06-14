<?php

namespace App\Http\Requests;

use App\Models\Admin;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                // ── [UPDATE: KOMPATIBILITAS TABEL AUTH] ─────────────
                // Fungsi: Memvalidasi keunikan email pada tabel admin, bukan tabel users (default bawaan Laravel).
                // Penjelasan: Error "Undefined table: users" terjadi karena Laravel mencoba mencari email di tabel "users".
                // Karena kita menggunakan tabel "admins", maka kita harus mengubah referensi modelnya ke Admin::class.
                Rule::unique(Admin::class)->ignore($this->user()->id),
            ],
        ];
    }
}
