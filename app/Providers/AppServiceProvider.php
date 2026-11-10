<?php

namespace App\Providers;

use App\Models\Pengiriman;
use App\Observers\PengirimanObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // [UPDATE: SERVER-SIDE DEVICE ROUTING]
        // Mengganti default Inertia ResponseFactory dengan ResponsiveResponseFactory
        // agar rute otomatis mengarahkan ke folder Mobile/ atau Desktop/ di server.
        $this->app->singleton(\Inertia\ResponseFactory::class, \App\Services\Inertia\ResponsiveResponseFactory::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Pengiriman::observe(PengirimanObserver::class);

        Vite::prefetch(concurrency: 3);

        RateLimiter::for('login', function (Request $request): Limit {
            $email = (string) $request->input('email', '');
            $ip = (string) $request->ip();

            return Limit::perMinute(5)->by(strtolower($email).'|'.$ip);
        });

        RateLimiter::for('tracking', function (Request $request): Limit {
            $ip = (string) $request->ip();

            return Limit::perMinute(30)->by('tracking|'.$ip);
        });
    }
}
