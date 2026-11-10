<?php

namespace App\Services\Inertia;

use Inertia\Response;
use Inertia\ResponseFactory;

/**
 * =========================================================================
 * RESPONSIVE INERTIA RESPONSE FACTORY (SERVER-SIDE DEVICE ROUTING)
 * =========================================================================
 * Fungsi:
 *   Mendeteksi User-Agent secara langsung di server (Laravel PHP) dan
 *   mengarahkan komponen ke folder Pages/Mobile/ atau Pages/Desktop/.
 *
 * Cara Kerja:
 *   1. Setiap controller memanggil Inertia::render('Dashboard/Index', $data)
 *   2. Server mengecek apakah User-Agent adalah perangkat Mobile.
 *   3. Jika Mobile dan file resources/js/Pages/Mobile/Dashboard/Index.vue ada,
 *      komponen yang dikirim adalah 'Mobile/Dashboard/Index'.
 *   4. Jika Desktop dan file resources/js/Pages/Desktop/Dashboard/Index.vue ada,
 *      komponen yang dikirim adalah 'Desktop/Dashboard/Index'.
 *   5. Jika halaman bersama (misal Splash, System/About), gunakan apa adanya.
 *
 * Manfaat Performa:
 *   - Browser HP Android langsung menerima HTML + nama file komponen Mobile
 *     tanpa perlu melakukan parsing User-Agent dan dynamic fallback di client JS.
 *   - Menghilangkan Total Blocking Time (TBT) dan loading waterfall di HP Android.
 * =========================================================================
 */
class ResponsiveResponseFactory extends ResponseFactory
{
    /**
     * Cache for resolved components during the lifecycle.
     */
    protected static array $resolvedCache = [];

    /**
     * Render an Inertia response with server-side device detection.
     *
     * @param  string  $component
     * @param  array<array-key, mixed>|\Illuminate\Contracts\Support\Arrayable|\Inertia\ProvidesInertiaProperties  $props
     */
    public function render(string $component, $props = []): Response
    {
        $resolved = $this->resolveResponsiveComponent($component);

        return parent::render($resolved, $props);
    }

    /**
     * Resolves component path to Mobile or Desktop if available on disk.
     */
    protected function resolveResponsiveComponent(string $component): string
    {
        // Jika sudah secara eksplisit menyertakan Desktop/ atau Mobile/, gunakan langsung
        if (str_starts_with($component, 'Desktop/') || str_starts_with($component, 'Mobile/')) {
            return $component;
        }

        $request = request();
        $userAgent = $request ? ($request->header('User-Agent') ?? '') : '';
        $isMobile = (bool) preg_match('/Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $userAgent);
        $prefix = $isMobile ? 'Mobile' : 'Desktop';
        $cacheKey = "{$prefix}:{$component}";

        if (isset(static::$resolvedCache[$cacheKey])) {
            return static::$resolvedCache[$cacheKey];
        }

        $candidate = "{$prefix}/{$component}";

        // Periksa apakah file komponen khusus Mobile atau Desktop tersedia
        $componentPath = resource_path("js/Pages/{$candidate}.vue");
        if (file_exists($componentPath)) {
            return static::$resolvedCache[$cacheKey] = $candidate;
        }

        // Fallback untuk halaman bersama (misalnya Splash, Profile/Security, System/About)
        return static::$resolvedCache[$cacheKey] = $component;
    }
}
