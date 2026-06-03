<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PengirimanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'pengirim_nama' => $this->has('pengirim_nama') ? trim((string) $this->input('pengirim_nama')) : null,
            'pengirim_hp' => $this->has('pengirim_hp') ? preg_replace('/\s+/', '', (string) $this->input('pengirim_hp')) : null,
            'pengirim_alamat' => $this->has('pengirim_alamat') ? trim((string) $this->input('pengirim_alamat')) : null,

            'penerima_nama' => $this->has('penerima_nama') ? trim((string) $this->input('penerima_nama')) : null,
            'penerima_hp' => $this->has('penerima_hp') ? preg_replace('/\s+/', '', (string) $this->input('penerima_hp')) : null,
            'penerima_alamat' => $this->has('penerima_alamat') ? trim((string) $this->input('penerima_alamat')) : null,

            'catatan' => $this->filled('catatan') ? trim((string) $this->input('catatan')) : null,
        ]);

        $barang = $this->input('barang');

        if (is_array($barang)) {
            $barang = array_map(function ($b) {
                if (! is_array($b)) {
                    return $b;
                }

                if (array_key_exists('nama_barang', $b)) {
                    $b['nama_barang'] = trim((string) $b['nama_barang']);
                }

                if (array_key_exists('keterangan', $b)) {
                    $b['keterangan'] = $b['keterangan'] === null ? null : trim((string) $b['keterangan']);
                }

                return $b;
            }, $barang);

            $this->merge(['barang' => $barang]);
        }
    }

    public function rules(): array
    {
        return [
            'pengirim_nama' => ['bail', 'required', 'string', 'min:2', 'max:100'],
            'pengirim_hp' => ['bail', 'required', 'regex:/^(08|628|62)[0-9]{8,11}$/'],
            'pengirim_alamat' => ['bail', 'required', 'string', 'min:15', 'max:500'],
            'pengirim_kota_id' => ['bail', 'required', 'integer', 'exists:kota,id'],

            'penerima_nama' => ['bail', 'required', 'string', 'min:2', 'max:100'],
            'penerima_hp' => ['bail', 'required', 'regex:/^(08|628|62)[0-9]{8,11}$/'],
            'penerima_alamat' => ['bail', 'required', 'string', 'min:15', 'max:500'],
            'penerima_kota_id' => ['bail', 'required', 'integer', 'exists:kota,id', 'different:pengirim_kota_id'],

            'barang' => ['bail', 'required', 'array', 'min:1'],
            'barang.*' => ['bail', 'required', 'array'],
            'barang.*.nama_barang' => ['bail', 'required', 'string', 'max:100'],
            'barang.*.berat_kg' => ['bail', 'required', 'numeric', 'min:0.01', 'max:9999'],
            'barang.*.panjang_cm' => ['bail', 'required', 'numeric', 'min:0', 'max:9999'],
            'barang.*.lebar_cm' => ['bail', 'required', 'numeric', 'min:0', 'max:9999'],
            'barang.*.tinggi_cm' => ['bail', 'required', 'numeric', 'min:0', 'max:9999'],
            'barang.*.keterangan' => ['nullable', 'string', 'max:255'],

            'jenis_layanan' => ['bail', 'required', Rule::in(['reguler', 'express', 'kargo', 'ekonomi'])],

            // NOTE:
            // biaya_pengiriman tidak dijadikan sumber kebenaran (backend hitung ulang dari tarif DB + berat ditagihkan),
            // jadi dibuat nullable agar tidak memblokir request jika UI berubah.
            'biaya_pengiriman' => ['nullable', 'numeric', 'min:0'],
            'biaya_tambahan' => ['nullable', 'numeric', 'min:0'],
            'biaya_asuransi' => ['nullable', 'numeric', 'min:0'],

            'metode_pembayaran' => ['bail', 'required', Rule::in(['dibayar_pengirim', 'dibayar_penerima', 'cod'])],
            'catatan' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'pengirim_hp.regex' => 'No HP pengirim harus diawali 08 atau 62 dan panjangnya valid.',
            'penerima_hp.regex' => 'No HP penerima harus diawali 08 atau 62 dan panjangnya valid.',
            'penerima_kota_id.different' => 'Kota pengirim dan penerima tidak boleh sama.',

            'barang.required' => 'Minimal 1 barang wajib diisi.',
            'barang.array' => 'Format data barang tidak valid.',
            'barang.min' => 'Minimal 1 barang wajib diisi.',

            'barang.*.nama_barang.required' => 'Nama barang wajib diisi.',
            'barang.*.berat_kg.required' => 'Berat barang wajib diisi.',
            'barang.*.berat_kg.min' => 'Berat barang minimal 0.01 kg.',

            'jenis_layanan.in' => 'Jenis layanan tidak valid.',
            'metode_pembayaran.in' => 'Metode pembayaran tidak valid.',
        ];
    }
}
