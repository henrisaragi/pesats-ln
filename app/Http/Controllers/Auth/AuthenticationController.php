<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Auth\AuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticationController extends Controller
{
    public function __construct(private readonly AuthenticationService $authenticationService) {}

    public function showLogin(): View
    {
        return view('auth._modern-login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        $authenticated = $this->authenticationService->attemptLogin(
            $credentials['login'],
            $credentials['password'],
            $request->boolean('remember'),
        );

        if (! $authenticated) {
            throw ValidationException::withMessages([
                'login' => __('Username/email atau password yang Anda masukkan salah.'),
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('second', ['dashboards', 'index']));
    }

    public function showRegister(): View
    {
        return view('auth._modern-register');
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $user = $this->authenticationService->register($request->validated());

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->intended(route('second', ['dashboards', 'index']));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
