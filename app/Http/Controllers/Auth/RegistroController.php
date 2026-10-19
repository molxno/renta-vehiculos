<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\RegistrarCliente;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrarClienteRequest;
use App\Services\AterrizajePorRol;
use App\Support\ReglasLicenciaConduccion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Response;

final class RegistroController extends Controller
{
    public function create(): Response
    {
        return inertia('Auth/Register', [
            'categoriasLicencia' => ReglasLicenciaConduccion::categorias(),
        ]);
    }

    public function store(
        RegistrarClienteRequest $request,
        RegistrarCliente $registrarCliente,
        AterrizajePorRol $aterrizajePorRol,
    ): RedirectResponse {
        $user = $registrarCliente->handle(
            $request->string('name')->toString(),
            $request->string('email')->toString(),
            $request->string('tipo_documento')->toString(),
            $request->string('numero_documento')->toString(),
            $request->string('telefono')->toString(),
            $request->string('password')->toString(),
            $request->string('licencia_numero')->toString(),
            $request->string('licencia_categoria')->toString(),
            $request->string('licencia_vencimiento')->toString(),
        );

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->to($aterrizajePorRol->urlTrasAutenticacion($user, $request))
            ->with('success', 'Cuenta creada');
    }
}
