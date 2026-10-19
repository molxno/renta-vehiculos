<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Cuenta\GuardarLicenciaCliente;
use App\Http\Requests\ActualizarLicenciaRequest;
use App\Models\LicenciaConduccion;
use App\Models\User;
use App\Support\ReglasLicenciaConduccion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

final class CuentaController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        $user->load('licenciaConduccion');
        $licencia = $user->licenciaConduccion;

        return inertia('Cuenta/Show', [
            'perfil' => [
                'name' => $user->name,
                'email' => $user->email,
                'tipo_documento' => $user->tipo_documento,
                'numero_documento' => $user->numero_documento,
                'telefono' => $user->telefono,
            ],
            'licencia' => $this->licenciaProps($licencia),
            'categoriasLicencia' => ReglasLicenciaConduccion::categorias(),
            'updateUrl' => route('cuenta.update'),
        ]);
    }

    public function update(
        ActualizarLicenciaRequest $request,
        GuardarLicenciaCliente $guardarLicenciaCliente,
    ): RedirectResponse {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        $guardarLicenciaCliente->handle(
            $user,
            $request->string('licencia_numero')->toString(),
            $request->string('licencia_categoria')->toString(),
            $request->string('licencia_vencimiento')->toString(),
        );

        return to_route('cuenta.show')->with('success', 'Licencia actualizada');
    }

    /**
     * @return array{numero: string, categoria: string, fecha_vencimiento: string, vigente: bool}|null
     */
    private function licenciaProps(?LicenciaConduccion $licencia): ?array
    {
        if (! $licencia instanceof LicenciaConduccion) {
            return null;
        }

        return [
            'numero' => $licencia->numero,
            'categoria' => $licencia->categoria,
            'fecha_vencimiento' => $licencia->fecha_vencimiento->toDateString(),
            'vigente' => $licencia->estaVigente(),
        ];
    }
}
