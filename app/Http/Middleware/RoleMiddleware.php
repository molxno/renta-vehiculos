<?php

namespace App\Http\Middleware;

use App\Enums\UsuarioRol;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(
        Request $request,
        Closure $next,
        string $role
    ): Response {
        if (! auth()->check()) {
            abort(403);
        }

        /** @var UsuarioRol $userRole */
        $userRole = $request->user()->rol;

        if ($userRole->value !== $role) {
            abort(403);
        }

        return $next($request);
    }
}
