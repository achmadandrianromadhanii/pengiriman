<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TurnstileRule implements ValidationRule
{
    /**
     * Validasi respon token dari Cloudflare Turnstile
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secretKey = config('services.cloudflare.turnstile_secret_key');

        // Jika secret key belum disetel sama sekali
        if (empty($secretKey)) {
            if (app()->environment('local', 'testing')) {
                return;
            }
            $fail('Verifikasi keamanan Cloudflare belum dikonfigurasi di server.');
            return;
        }

        if (empty($value) || ! is_string($value)) {
            $fail('Selesaikan verifikasi keamanan Cloudflare terlebih dahulu.');
            return;
        }

        try {
            $response = Http::asForm()->timeout(6)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret'   => $secretKey,
                'response' => $value,
                'remoteip' => request()->ip(),
            ]);

            $result = $response->json();

            if (! ($result['success'] ?? false)) {
                Log::warning('Cloudflare Turnstile verification failed', [
                    'ip'          => request()->ip(),
                    'error-codes' => $result['error-codes'] ?? [],
                ]);

                $fail('Verifikasi keamanan Cloudflare gagal atau kedaluwarsa. Silakan lakukan verifikasi ulang.');
            }
        } catch (\Exception $e) {
            Log::error('Cloudflare Turnstile API exception: ' . $e->getMessage());
            $fail('Layanan verifikasi keamanan Cloudflare sedang mengalami kendala. Silakan coba sesaat lagi.');
        }
    }
}
