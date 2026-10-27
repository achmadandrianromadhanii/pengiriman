<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),

            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'nama' => $user->nama,
                    'name' => $user->nama,
                    'email' => $user->email,
                    'foto' => ($user->foto && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->foto)) ? $user->foto : null,
                    'status' => $user->status,
                ] : null,
            ],

            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
                'warning' => fn () => $request->session()->get('warning'),
            ],

            'isMobile' => (bool) preg_match('/Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $request->header('User-Agent') ?? ''),
        ];
    }
}
