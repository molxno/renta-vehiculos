<?php

use App\Contracts\DisponibilidadService;
use App\Enums\ReservaEstado;
use App\Enums\VehiculoEstado;
use App\Models\Reserva;
use App\Models\Vehiculo;
use Database\Seeders\DisponibilidadSeeder;
use Illuminate\Support\Carbon;

describe('servicio de disponibilidad', function () {
    it('excludes overlapping reservations but allows cancelled and consecutive reservations', function () {
        $vehiculoOcupado = Vehiculo::factory()->create();
        $vehiculoCancelado = Vehiculo::factory()->create();
        $vehiculoConReservaConsecutiva = Vehiculo::factory()->create();
        $vehiculoYaLiberado = Vehiculo::factory()->create();

        Reserva::factory()->create([
            'vehiculo_id' => $vehiculoOcupado->id,
            'inicio' => '2026-11-11 09:00:00',
            'fin' => '2026-11-13 09:00:00',
        ]);
        Reserva::factory()->create([
            'vehiculo_id' => $vehiculoCancelado->id,
            'estado' => ReservaEstado::Cancelada,
            'inicio' => '2026-11-10 09:00:00',
            'fin' => '2026-11-12 09:00:00',
        ]);
        Reserva::factory()->create([
            'vehiculo_id' => $vehiculoConReservaConsecutiva->id,
            'inicio' => '2026-11-12 09:00:00',
            'fin' => '2026-11-14 09:00:00',
        ]);
        Reserva::factory()->create([
            'vehiculo_id' => $vehiculoYaLiberado->id,
            'inicio' => '2026-11-08 09:00:00',
            'fin' => '2026-11-10 09:00:00',
        ]);

        $resultados = app(DisponibilidadService::class)->consultar(
            Carbon::parse('2026-11-10 09:00:00'),
            Carbon::parse('2026-11-12 09:00:00'),
        );
        $resultadosPorVehiculo = collect($resultados)->keyBy('vehiculo_id');

        expect($resultados)->toHaveCount(4)
            ->and($resultadosPorVehiculo[$vehiculoOcupado->id]['disponible'])->toBeFalse()
            ->and($resultadosPorVehiculo[$vehiculoOcupado->id]['motivo'])->toBe('El vehículo tiene una reserva para esas fechas.')
            ->and($resultadosPorVehiculo[$vehiculoCancelado->id]['disponible'])->toBeTrue()
            ->and($resultadosPorVehiculo[$vehiculoConReservaConsecutiva->id]['disponible'])->toBeTrue()
            ->and($resultadosPorVehiculo[$vehiculoYaLiberado->id]['disponible'])->toBeTrue();
    });

    it('filters by vehicle and suggests modifying the criteria when it is occupied', function () {
        $vehiculoOcupado = Vehiculo::factory()->create();
        Vehiculo::factory()->create();

        Reserva::factory()->create([
            'vehiculo_id' => $vehiculoOcupado->id,
            'inicio' => '2026-11-10 09:00:00',
            'fin' => '2026-11-12 09:00:00',
        ]);

        $resultados = app(DisponibilidadService::class)->consultar(
            Carbon::parse('2026-11-10 09:00:00'),
            Carbon::parse('2026-11-12 09:00:00'),
            $vehiculoOcupado->id,
        );

        expect($resultados)->toHaveCount(1)
            ->and($resultados[0]['vehiculo_id'])->toBe($vehiculoOcupado->id)
            ->and($resultados[0]['disponible'])->toBeFalse()
            ->and($resultados[0]['motivo'])->toContain('Modifica las fechas o selecciona otro vehículo.');
    });

    it('marks vehicles outside the rental fleet as unavailable and suggests another criterion', function () {
        $vehiculo = Vehiculo::factory()->create([
            'estado' => VehiculoEstado::Mantenimiento,
        ]);

        $resultados = app(DisponibilidadService::class)->consultar(
            Carbon::parse('2026-11-10 09:00:00'),
            Carbon::parse('2026-11-12 09:00:00'),
            $vehiculo->id,
        );

        expect($resultados)->toHaveCount(1)
            ->and($resultados[0]['disponible'])->toBeFalse()
            ->and($resultados[0]['motivo'])->toContain('El vehículo no está disponible para renta.')
            ->and($resultados[0]['motivo'])->toContain('Modifica las fechas o selecciona otro vehículo.');
    });

    it('provides an independently runnable availability fixture', function () {
        $this->seed(DisponibilidadSeeder::class);

        $vehiculoReservado = Vehiculo::query()->where('placa', 'ABC-123')->sole();
        $resultados = app(DisponibilidadService::class)->consultar(
            Carbon::parse('2026-11-10 09:00:00'),
            Carbon::parse('2026-11-12 09:00:00'),
        );
        $resultadosPorVehiculo = collect($resultados)->keyBy('vehiculo_id');

        expect($resultados)->toHaveCount(3)
            ->and($resultadosPorVehiculo[$vehiculoReservado->id]['disponible'])->toBeFalse()
            ->and($resultadosPorVehiculo[$vehiculoReservado->id]['motivo'])->toBe('El vehículo tiene una reserva para esas fechas.')
            ->and(collect($resultados)->where('disponible', true))->toHaveCount(2);
    });
});
