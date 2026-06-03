<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KotaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nama_kota' => is_string($this->input('nama_kota')) ? trim($this->input('nama_kota')) : $this->input('nama_kota'),
            'provinsi' => is_string($this->input('provinsi')) ? trim($this->input('provinsi')) : $this->input('provinsi'),
            'kode_pos' => is_string($this->input('kode_pos')) ? trim($this->input('kode_pos')) : $this->input('kode_pos'),
            'status' => is_string($this->input('status')) ? strtolower(trim($this->input('status'))) : $this->input('status'),
            'latitude' => $this->input('latitude') === '' ? null : $this->input('latitude'),
            'longitude' => $this->input('longitude') === '' ? null : $this->input('longitude'),
        ]);
    }

    public function rules(): array
    {
        return [
            'nama_kota' => ['required', 'string', 'min:2', 'max:100'],
            'provinsi' => ['required', 'string', 'min:2', 'max:100'],
            'kode_pos' => ['required', 'string', 'min:3', 'max:10'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'status' => ['required', 'in:aktif,nonaktif'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nama_kota' => 'nama kota',
            'kode_pos' => 'kode pos',
        ];
    }
}
