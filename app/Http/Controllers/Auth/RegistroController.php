<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\RegistrarCliente;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrarClienteRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Response;

final class RegistroController extends Controller
{
    public function create(): Response
    {
        return inertia('Auth/Register');
    }

    public function store(
        RegistrarClienteRequest $request,
        RegistrarCliente $registrarCliente,
    ): RedirectResponse {
        $user = $registrarCliente->handle(
            $request->string('name')->toString(),
            $request->string('email')->toString(),
            $request->string('tipo_documento')->toString(),
            $request->string('numero_documento')->toString(),
            $request->string('telefono')->toString(),
            $request->string('password')->toString(),
        );

        Auth::login($user);
        $request->session()->regenerate();

        return to_route('home');
    }
}
