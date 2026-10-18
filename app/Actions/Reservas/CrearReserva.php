<?php

declare(strict_types=1);

namespace App\Actions\Reservas;

use App\Contracts\DisponibilidadService;
use App\Contracts\TarifaService;
use App\Enums\ReservaEstado;
use App\Models\Reserva;
use App\Models\User;
use App\Models\Vehiculo;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CrearReserva
{
    public function __construct(
        private DisponibilidadService $disponibilidad,
        private TarifaService $tarifa,
    ) {}

    public function handle(
        User $cliente,
        int $vehiculoId,
        ?int $convenioId,
        DateTimeInterface $inicio,
        DateTimeInterface $fin,
    ): Reserva {
        return DB::transaction(function () use ($cliente, $vehiculoId, $convenioId, $inicio, $fin): Reserva {
            Vehiculo::query()->whereKey($vehiculoId)->lockForUpdate()->firstOrFail();

            $vehiculoDisponible = false;
            $motivoNoDisponible = null;

            foreach ($this->disponibilidad->consultar($inicio, $fin, $vehiculoId) as $resultado) {
                if ($resultado['vehiculo_id'] === $vehiculoId) {
                    $vehiculoDisponible = $resultado['disponible'];
                    $motivoNoDisponible = $resultado['motivo'];

                    break;
                }
            }

            if (! $vehiculoDisponible) {
                throw ValidationException::withMessages([
                    'vehiculo_id' => $motivoNoDisponible !== null && $motivoNoDisponible !== ''
                        ? $motivoNoDisponible
                        : 'El vehículo no está disponible para las fechas seleccionadas.',
                ]);
            }

            $cotizacion = $this->tarifa->cotizar($vehiculoId, $inicio, $fin, $convenioId);

            return Reserva::query()->create([
                'user_id' => $cliente->id,
                'vehiculo_id' => $vehiculoId,
                'convenio_id' => $convenioId,
                'estado' => ReservaEstado::Pendiente,
                'inicio' => $inicio,
                'fin' => $fin,
                'total_centavos' => $cotizacion['total_centavos'],
                'moneda' => $cotizacion['moneda'],
            ]);
        });
    }
}
