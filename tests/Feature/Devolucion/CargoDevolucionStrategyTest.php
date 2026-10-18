<?php

declare(strict_types=1);

use App\Contracts\CalculadoraDevolucion;
use App\Enums\ReservaEstado;
use App\Models\ActaEntrega;
use App\Models\Contrato;
use App\Models\Dano;
use App\Models\Multa;
use App\Models\Reserva;
use App\Services\Devolucion\Strategies\CombustibleCargoStrategy;
use App\Services\Devolucion\Strategies\DanoCargoStrategy;
use App\Services\Devolucion\Strategies\KilometrajeCargoStrategy;
use App\Services\Devolucion\Strategies\RetrasoCargoStrategy;
use Illuminate\Support\Carbon;

it('is composed of the four return charge strategies', function () {
    $calculadora = app(CalculadoraDevolucion::class);
    $estrategias = (new ReflectionProperty($calculadora, 'estrategias'))->getValue($calculadora);

    expect($estrategias)->toHaveCount(4)
        ->and($estrategias[0])->toBeInstanceOf(RetrasoCargoStrategy::class)
        ->and($estrategias[1])->toBeInstanceOf(KilometrajeCargoStrategy::class)
        ->and($estrategias[2])->toBeInstanceOf(CombustibleCargoStrategy::class)
        ->and($estrategias[3])->toBeInstanceOf(DanoCargoStrategy::class);
});

it('lets each strategy calculate its own charge line independently', function () {
    $reserva = Reserva::factory()->create([
        'estado' => ReservaEstado::Entregada,
        'inicio' => '2026-02-05 09:00:00',
        'fin' => '2026-02-08 09:00:00',
        'total_centavos' => 66000000,
    ]);
    Contrato::factory()->create([
        'reserva_id' => $reserva->id,
        'condiciones' => ['kilometraje_incluido' => 300],
    ]);
    ActaEntrega::factory()->create([
        'reserva_id' => $reserva->id,
        'checklist' => ['kilometraje' => 10000, 'combustible' => 'lleno'],
    ]);
    Dano::factory()->create([
        'reserva_id' => $reserva->id,
        'descripcion' => 'Golpe',
        'monto_centavos' => 1000000,
    ]);
    Multa::factory()->create([
        'reserva_id' => $reserva->id,
        'concepto' => 'Fotomulta',
        'monto_centavos' => 2000000,
    ]);
    $reserva->load(['actaEntrega', 'contrato', 'danos', 'multas']);
    $devueltoEn = Carbon::parse('2026-02-09 10:00:00');
    $condiciones = ['kilometraje' => 10450, 'combustible' => 'cuarto'];

    expect((new RetrasoCargoStrategy)->lineas($reserva, $devueltoEn, $condiciones))
        ->toBe([['concepto' => 'Retraso de 2 día(s)', 'monto_centavos' => 66000000]])
        ->and((new KilometrajeCargoStrategy)->lineas($reserva, $devueltoEn, $condiciones))
        ->toBe([['concepto' => 'Exceso de kilometraje (150 km)', 'monto_centavos' => 22500000]])
        ->and((new CombustibleCargoStrategy)->lineas($reserva, $devueltoEn, $condiciones))
        ->toBe([['concepto' => 'Combustible faltante (3 cuarto(s) de tanque)', 'monto_centavos' => 10500000]])
        ->and((new DanoCargoStrategy)->lineas($reserva, $devueltoEn, $condiciones))
        ->toBe([
            ['concepto' => 'Daño: Golpe', 'monto_centavos' => 1000000],
            ['concepto' => 'Multa: Fotomulta', 'monto_centavos' => 2000000],
        ]);
});
