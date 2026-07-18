<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        try {
            $request->authenticate();
        } catch (ValidationException $exception) {
            AuditLog::create([
                'action' => 'admin.login_failed',
                'description' => "Failed admin login attempt for {$request->input('email')}.",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'severity' => 'warning',
                'created_at' => now(),
            ]);

            throw $exception;
        }

        $request->session()->regenerate();
        $request->user()->load('roles');

        AuditLog::create([
            'user_id' => $request->user()->id,
            'role' => $request->user()->roles->pluck('name')->join(', '),
            'action' => 'admin.login',
            'description' => "Admin {$request->user()->email} logged in.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
