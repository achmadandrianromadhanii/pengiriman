<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecureHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // [UPDATE: PERBAIKAN X-FRAME-OPTIONS]
        // Fungsi: Mengubah 'DENY' menjadi 'SAMEORIGIN'.
        // Cara Kerja: Sebelumnya di-set 'DENY' yang membuat website ini sama sekali tidak bisa
        // dimasukkan ke dalam iframe (seperti simulator Lighthouse/DevTools).
        // Dengan 'SAMEORIGIN', website tetap aman dari Clickjacking untuk domain luar, tapi 
        // tetap bisa dipreview melalui iframe atau simulator di domain/localhost yang sama.
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        // HSTS hanya kalau HTTPS (biar tidak ganggu lokal)
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
