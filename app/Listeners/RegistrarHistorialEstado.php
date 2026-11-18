<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ReservaEstadoCambiado;
use App\Models\ReservaHistorialEstado;

final class RegistrarHistorialEstado
{
    public function handle(ReservaEstadoCambiado $evento): void
    {
        ReservaHistorialEstado::query()->create([
            'reserva_id' => $evento->reserva->id,
            'estado_anterior' => $evento->estadoAnterior,
            'estado_nuevo' => $evento->estadoNuevo,
            'ocurrio_at' => $evento->fecha,
        ]);
    }
}
