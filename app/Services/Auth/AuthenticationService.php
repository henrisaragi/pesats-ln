<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthenticationService
{
    public function attemptLogin(string $login, string $password, bool $remember = false): bool
    {
        return Auth::attempt(
            [$this->loginField($login) => $login, 'password' => $password],
            $remember
        );
    }

    /**
     * @param  array{name: string, username: string, email: string, password: string}  $data
     */
    public function register(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_active' => true,
        ]);
    }

    /**
     * @return array{user: User, token: string}|null
     */
    public function issueApiToken(string $login, string $password): ?array
    {
        $user = User::query()->where($this->loginField($login), $login)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        return [
            'user' => $user,
            'token' => $user->createToken('api')->plainTextToken,
        ];
    }

    /**
     * @param  array{name: string, username: string, email: string, password: string}  $data
     * @return array{user: User, token: string}
     */
    public function registerWithApiToken(array $data): array
    {
        $user = $this->register($data);

        return [
            'user' => $user,
            'token' => $user->createToken('api')->plainTextToken,
        ];
    }

    private function loginField(string $login): string
    {
        return filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
    }
}
