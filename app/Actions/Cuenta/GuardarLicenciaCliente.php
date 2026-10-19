<?php

declare(strict_types=1);

namespace App\Actions\Cuenta;

use App\Models\LicenciaConduccion;
use App\Models\User;

final class GuardarLicenciaCliente
{
    public function handle(
        User $user,
        string $numero,
        string $categoria,
        string $fechaVencimiento,
    ): LicenciaConduccion {
        return LicenciaConduccion::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'numero' => $numero,
                'categoria' => $categoria,
                'fecha_vencimiento' => $fechaVencimiento,
            ],
        );
    }
}
