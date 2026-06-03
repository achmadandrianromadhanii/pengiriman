<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AuthenticatedSessionController extends Controller
{
    public function create(): InertiaResponse
    {
        return Inertia::render('Auth/Login');
    }

    public function store(LoginRequest $request): InertiaResponse|RedirectResponse
    {
        $validated = $request->validated();

        $admin = Admin::query()
            ->where('email', $validated['email'])
            ->first();

        if (! $admin || ! Hash::check($validated['password'], $admin->password)) {
            return back()
                ->withErrors(['email' => 'Email atau password salah'])
                ->onlyInput('email');
        }

        if ($admin->status !== 'aktif') {
            return back()
                ->withErrors(['email' => 'Akun tidak aktif'])
                ->onlyInput('email');
        }

        Auth::login($admin, (bool) ($validated['remember'] ?? false));
        $request->session()->regenerate();

        $admin->forceFill([
            'last_login' => now(),
        ])->save();

        return Inertia::render('Auth/Login', [
            'loginSuccess' => true,
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
