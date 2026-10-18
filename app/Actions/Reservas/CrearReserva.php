<?php

declare(strict_types=1);

namespace App\Actions\Reservas;

use App\Contracts\DisponibilidadService;
use App\Contracts\GeneradorQr;
use App\Contracts\TarifaService;
use App\Enums\ReservaEstado;
use App\Models\Reserva;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CrearReserva
{
    public function __construct(
        private DisponibilidadService $disponibilidad,
        private TarifaService $tarifa,
        private GeneradorQr $generadorQr,
    ) {}

    public function handle(
        int $userId,
        int $vehiculoId,
        DateTimeInterface $inicio,
        DateTimeInterface $fin,
        ?int $convenioId = null,
        ?string $moneda = null,
    ): Reserva {
        $disponibilidad = $this->disponibilidad->consultar($inicio, $fin, $vehiculoId)[0] ?? null;

        if ($disponibilidad === null || $disponibilidad['disponible'] !== true) {
            throw ValidationException::withMessages([
                'vehiculo_id' => $disponibilidad['motivo']
                    ?? 'El vehículo no está disponible. Modifica las fechas o selecciona otro vehículo.',
            ]);
        }

        $cotizacion = $this->tarifa->cotizar($vehiculoId, $inicio, $fin, $convenioId);

        return DB::transaction(function () use ($userId, $vehiculoId, $inicio, $fin, $convenioId, $moneda, $cotizacion): Reserva {
            $reserva = Reserva::query()->create([
                'user_id' => $userId,
                'vehiculo_id' => $vehiculoId,
                'convenio_id' => $convenioId,
                'estado' => ReservaEstado::Confirmada,
                'inicio' => $inicio,
                'fin' => $fin,
                'total_centavos' => $cotizacion['total_centavos'],
                'moneda' => $moneda ?? $cotizacion['moneda'],
            ]);

            $reserva->update([
                'qr_codigo' => $this->generadorQr->generar($reserva->id),
            ]);

            return $reserva->refresh();
        });
    }
}
