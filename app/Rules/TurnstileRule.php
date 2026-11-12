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

        // Token pengujian resmi Cloudflare
        if (app()->environment('local', 'testing') && $value === 'XXXX.DUMMY.TOKEN.XXXX') {
            return;
        }

        try {
            $params = [
                'secret'   => $secretKey,
                'response' => $value,
            ];

            // Hanya sertakan remoteip jika berupa IP publik (bukan loopback/private)
            $ip = request()->ip();
            if ($ip && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                $params['remoteip'] = $ip;
            }

            $response = Http::asForm()->timeout(6)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', $params);

            $result = $response->json();

            if (! ($result['success'] ?? false)) {
                // Di lingkungan local/testing, jika menggunakan dummy test token Cloudflare
                if (app()->environment('local', 'testing')) {
                    $testResponse = Http::asForm()->timeout(4)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                        'secret'   => '1x0000000000000000000000000000000AA',
                        'response' => $value,
                    ]);
                    if ($testResponse->json('success') === true) {
                        return;
                    }
                }

                Log::warning('Cloudflare Turnstile verification failed', [
                    'ip'          => $ip,
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
