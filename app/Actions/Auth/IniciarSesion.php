<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

final class IniciarSesion
{
    public function handle(string $email, string $password, bool $remember, string $ip): void
    {
        $this->ensureIsNotRateLimited($email, $ip);

        if (! Auth::attempt(['email' => $email, 'password' => $password], $remember)) {
            RateLimiter::hit($this->throttleKey($email, $ip));

            throw ValidationException::withMessages([
                'email' => 'Las credenciales no coinciden con nuestros registros.',
            ]);
        }

        RateLimiter::clear($this->throttleKey($email, $ip));
    }

    private function ensureIsNotRateLimited(string $email, string $ip): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($email, $ip), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($email, $ip));

        throw ValidationException::withMessages([
            'email' => sprintf(
                'Demasiados intentos. Inténtalo de nuevo en %d segundos.',
                $seconds,
            ),
        ]);
    }

    private function throttleKey(string $email, string $ip): string
    {
        return mb_strtolower($email).'|'.$ip;
    }
}
