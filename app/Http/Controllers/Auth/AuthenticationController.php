<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\Auth\AuthenticationService;
use App\Services\Onboarding\OnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticationController extends Controller
{
    public function __construct(
        private readonly AuthenticationService $authenticationService,
        private readonly OnboardingService $onboardingService,
    ) {}

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

        /** @var User $user */
        $user = Auth::user();

        if (! $user->onboarding_completed) {
            return redirect()->to($this->onboardingService->nextIncompleteStepRoute($user));
        }

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

        return redirect()->to(route('onboarding.type.show'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
