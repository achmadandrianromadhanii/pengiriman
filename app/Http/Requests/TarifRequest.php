<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TarifRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'jenis_layanan' => ['bail', 'required', 'in:reguler,express,kargo,ekonomi'],

            'harga_dasar' => ['bail', 'required', 'numeric', 'min:0'],
            'harga_per_km' => ['bail', 'required', 'numeric', 'min:0'],
            'harga_per_kg' => ['bail', 'required', 'numeric', 'min:0'],

            'estimasi_hari' => ['bail', 'required', 'integer', 'min:1', 'max:365'],
            'status' => ['bail', 'required', 'in:aktif,nonaktif'],
        ];
    }
}
