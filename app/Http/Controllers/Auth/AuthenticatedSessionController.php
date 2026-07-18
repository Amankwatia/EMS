<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

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
            $this->auditLogger->record(
                $request,
                'admin.login_failed',
                "Failed admin login attempt for {$request->input('email')}.",
                severity: 'warning',
                includeAuthenticatedUser: false,
            );

            throw $exception;
        }

        $request->session()->regenerate();
        $request->user()->load('roles');

        $this->auditLogger->record(
            $request,
            'admin.login',
            "Admin {$request->user()->email} logged in.",
        );

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
