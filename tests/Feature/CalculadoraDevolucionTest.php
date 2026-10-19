<?php

use App\Contracts\CalculadoraDevolucion;
use App\Enums\ReservaEstado;
use App\Fakes\FakeCalculadoraDevolucion;
use App\Models\ActaEntrega;
use App\Models\Contrato;
use App\Models\Dano;
use App\Models\Multa;
use App\Models\Reserva;
use App\Services\CalculadoraDevolucionEstandar;
use Illuminate\Support\Carbon;

/**
 * Reserva de 3 días por 660.000 COP (220.000 COP diarios), entregada con
 * 10.000 km, tanque lleno y 300 km incluidos en el contrato.
 *
 * @param  array<string, mixed>  $checklistEntrega
 * @param  array<string, mixed>  $condicionesContrato
 */
function reservaEntregada(
    array $checklistEntrega = ['kilometraje' => 10000, 'combustible' => 'lleno'],
    array $condicionesContrato = ['kilometraje_incluido' => 300],
): Reserva {
    $reserva = Reserva::factory()->create([
        'estado' => ReservaEstado::Entregada,
        'inicio' => '2026-02-05 09:00:00',
        'fin' => '2026-02-08 09:00:00',
        'total_centavos' => 66000000,
    ]);
    Contrato::factory()->create(['reserva_id' => $reserva->id, 'condiciones' => $condicionesContrato]);
    ActaEntrega::factory()->create(['reserva_id' => $reserva->id, 'checklist' => $checklistEntrega]);

    return $reserva;
}

describe('calculadora de devolución', function () {
    it('is the bound implementation of the contract', function () {
        expect(app(CalculadoraDevolucion::class))->toBeInstanceOf(CalculadoraDevolucionEstandar::class);
    });

    it('keeps a deterministic fake for isolated bindings', function () {
        expect((new FakeCalculadoraDevolucion)->calcular(1, Carbon::parse('2026-01-01')))
            ->toHaveKey('total_centavos', 53550000);
    });

    it('charges only the base rent plus VAT when returned on time without differences', function () {
        $reserva = reservaEntregada();

        $cobro = app(CalculadoraDevolucion::class)->calcular(
            $reserva->id,
            Carbon::parse('2026-02-08 09:00:00'),
            ['kilometraje' => 10300, 'combustible' => 'lleno'],
        );

        expect($cobro)->toBe([
            'cargos_centavos' => 0,
            'subtotal_centavos' => 66000000,
            'impuestos_centavos' => 12540000,
            'total_centavos' => 78540000,
            'desglose' => [
                ['concepto' => 'Renta base', 'monto_centavos' => 66000000],
            ],
        ]);
    });

    it('charges one day for the first 24 hours of delay and double for each additional day', function (string $devueltoEn, string $concepto, int $monto) {
        $reserva = reservaEntregada();

        $cobro = app(CalculadoraDevolucion::class)->calcular($reserva->id, Carbon::parse($devueltoEn));

        expect($cobro['desglose'])->toContain(['concepto' => $concepto, 'monto_centavos' => $monto])
            ->and($cobro['cargos_centavos'])->toBe($monto);
    })->with([
        'un minuto tarde' => ['2026-02-08 09:01:00', 'Retraso de 1 día(s)', 22000000],
        '24 horas exactas' => ['2026-02-09 09:00:00', 'Retraso de 1 día(s)', 22000000],
        'un minuto del segundo día' => ['2026-02-09 09:01:00', 'Retraso de 2 día(s)', 66000000],
        'tres días' => ['2026-02-11 09:00:00', 'Retraso de 3 día(s)', 110000000],
    ]);

    it('charges the mileage that exceeds the contract allowance', function () {
        $reserva = reservaEntregada();

        $cobro = app(CalculadoraDevolucion::class)->calcular(
            $reserva->id,
            Carbon::parse('2026-02-08 09:00:00'),
            ['kilometraje' => 10450],
        );

        expect($cobro['desglose'])->toContain(['concepto' => 'Exceso de kilometraje (150 km)', 'monto_centavos' => 22500000])
            ->and($cobro['cargos_centavos'])->toBe(22500000);
    });

    it('uses the configured mileage allowance when the contract does not define one', function () {
        config(['devolucion.kilometraje_incluido' => 100]);
        $reserva = reservaEntregada(condicionesContrato: []);

        $cobro = app(CalculadoraDevolucion::class)->calcular(
            $reserva->id,
            Carbon::parse('2026-02-08 09:00:00'),
            ['kilometraje' => 10150],
        );

        expect($cobro['desglose'])->toContain(['concepto' => 'Exceso de kilometraje (50 km)', 'monto_centavos' => 7500000]);
    });

    it('does not charge mileage when the delivery record has no odometer reading', function () {
        $reserva = reservaEntregada(checklistEntrega: ['combustible' => 'lleno']);

        $cobro = app(CalculadoraDevolucion::class)->calcular(
            $reserva->id,
            Carbon::parse('2026-02-08 09:00:00'),
            ['kilometraje' => 99999, 'combustible' => 'lleno'],
        );

        expect($cobro['cargos_centavos'])->toBe(0);
    });

    it('does not charge mileage when the odometer reading is not numeric', function () {
        $reserva = reservaEntregada(checklistEntrega: ['kilometraje' => 'n/a', 'combustible' => 'lleno']);

        $cobro = app(CalculadoraDevolucion::class)->calcular(
            $reserva->id,
            Carbon::parse('2026-02-08 09:00:00'),
            ['kilometraje' => '', 'combustible' => 'lleno'],
        );

        expect($cobro['cargos_centavos'])->toBe(0);
    });

    it('charges each missing quarter of fuel compared with the delivery record', function (string $combustible, int $monto) {
        $reserva = reservaEntregada();

        $cobro = app(CalculadoraDevolucion::class)->calcular(
            $reserva->id,
            Carbon::parse('2026-02-08 09:00:00'),
            ['combustible' => $combustible],
        );

        expect($cobro['cargos_centavos'])->toBe($monto);
    })->with([
        'tanque lleno' => ['lleno', 0],
        'tres cuartos' => ['tres_cuartos', 3500000],
        'un cuarto' => ['cuarto', 10500000],
        'vacío' => ['vacio', 14000000],
    ]);

    it('describes the missing fuel in the breakdown', function () {
        $reserva = reservaEntregada();

        $cobro = app(CalculadoraDevolucion::class)->calcular(
            $reserva->id,
            Carbon::parse('2026-02-08 09:00:00'),
            ['combustible' => 'medio'],
        );

        expect($cobro['desglose'])->toContain(['concepto' => 'Combustible faltante (2 cuarto(s) de tanque)', 'monto_centavos' => 7000000]);
    });

    it('includes unsaved preview damages from the submitted conditions', function () {
        $reserva = reservaEntregada();

        $cobro = app(CalculadoraDevolucion::class)->calcular(
            $reserva->id,
            Carbon::parse('2026-02-08 09:00:00'),
            [
                'danos' => [
                    ['descripcion' => 'Rayón en puerta', 'monto_centavos' => 5000000],
                    'x',
                    ['descripcion' => '', 'monto_centavos' => ''],
                    ['descripcion' => 'Golpe', 'monto_centavos' => 'abc'],
                ],
            ],
        );

        expect($cobro['desglose'])->toBe([
            ['concepto' => 'Renta base', 'monto_centavos' => 66000000],
            ['concepto' => 'Daño: Rayón en puerta', 'monto_centavos' => 5000000],
            ['concepto' => 'Daño: Golpe', 'monto_centavos' => 0],
        ])
            ->and($cobro['cargos_centavos'])->toBe(5000000);
    });

    it('ignores a non array preview damage payload', function () {
        $reserva = reservaEntregada();

        $cobro = app(CalculadoraDevolucion::class)->calcular(
            $reserva->id,
            Carbon::parse('2026-02-08 09:00:00'),
            ['danos' => 'invalido'],
        );

        expect($cobro['cargos_centavos'])->toBe(0)
            ->and($cobro['desglose'])->toBe([
                ['concepto' => 'Renta base', 'monto_centavos' => 66000000],
            ]);
    });

    it('includes registered damages and fines in the final cost', function () {
        $reserva = reservaEntregada();
        Dano::factory()->create(['reserva_id' => $reserva->id, 'descripcion' => 'Rayón en puerta', 'monto_centavos' => 5000000]);
        Multa::factory()->create(['reserva_id' => $reserva->id, 'concepto' => 'Fotomulta', 'monto_centavos' => 3000000]);

        $cobro = app(CalculadoraDevolucion::class)->calcular($reserva->id, Carbon::parse('2026-02-08 09:00:00'));

        expect($cobro['desglose'])->toBe([
            ['concepto' => 'Renta base', 'monto_centavos' => 66000000],
            ['concepto' => 'Daño: Rayón en puerta', 'monto_centavos' => 5000000],
            ['concepto' => 'Multa: Fotomulta', 'monto_centavos' => 3000000],
        ])
            ->and($cobro['cargos_centavos'])->toBe(8000000)
            ->and($cobro['subtotal_centavos'])->toBe(74000000)
            ->and($cobro['impuestos_centavos'])->toBe(14060000)
            ->and($cobro['total_centavos'])->toBe(88060000);
    });
});
