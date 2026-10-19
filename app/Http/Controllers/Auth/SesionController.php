<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\CerrarSesion;
use App\Actions\Auth\IniciarSesion;
use App\Http\Controllers\Controller;
use App\Http\Requests\IniciarSesionRequest;
use App\Models\User;
use App\Services\AterrizajePorRol;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

final class SesionController extends Controller
{
    public function create(): Response
    {
        return inertia('Auth/Login');
    }

    public function store(
        IniciarSesionRequest $request,
        IniciarSesion $iniciarSesion,
        AterrizajePorRol $aterrizajePorRol,
    ): RedirectResponse {
        $iniciarSesion->handle(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
            $request->boolean('remember'),
            (string) $request->ip(),
        );

        $request->session()->regenerate();

        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        return redirect()->to($aterrizajePorRol->urlTrasAutenticacion($user, $request));
    }

    public function destroy(Request $request, CerrarSesion $cerrarSesion): RedirectResponse
    {
        $cerrarSesion->handle($request);

        return to_route('home');
    }
}
