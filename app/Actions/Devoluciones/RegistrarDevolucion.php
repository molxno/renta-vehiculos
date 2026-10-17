<?php

declare(strict_types=1);

namespace App\Actions\Devoluciones;

use App\Enums\ReservaEstado;
use App\Models\ActaDevolucion;
use App\Models\Reserva;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final class RegistrarDevolucion
{
    /**
     * Registra el acta de devolución y marca la reserva como devuelta.
     *
     * @param  array<array-key, mixed>|null  $checklist
     */
    public function handle(
        Reserva $reserva,
        CarbonInterface $devueltoAt,
        ?array $checklist,
        ?string $observaciones,
    ): ActaDevolucion {
        return DB::transaction(function () use ($reserva, $devueltoAt, $checklist, $observaciones): ActaDevolucion {
            $acta = $reserva->actaDevolucion()->create([
                'devuelto_at' => $devueltoAt,
                'checklist' => $checklist,
                'observaciones' => $observaciones,
            ]);
            $reserva->update(['estado' => ReservaEstado::Devuelta]);

            return $acta;
        });
    }
}
