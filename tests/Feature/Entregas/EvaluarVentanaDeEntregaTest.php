<?php

declare(strict_types=1);

use App\Actions\Entregas\EvaluarVentanaDeEntrega;
use App\Actions\Entregas\ResultadoVentanaDeEntrega;
use App\Exceptions\EntregaFueraDeVentana;
use App\Models\Reserva;
use Illuminate\Support\Carbon;

function reservaConVentana(string $inicio, string $fin): Reserva
{
    return Reserva::factory()->create([
        'inicio' => Carbon::parse($inicio, 'America/Bogota'),
        'fin' => Carbon::parse($fin, 'America/Bogota'),
    ]);
}

it('permite entregar el día de inicio y rechaza el día de fin', function () {
    $reserva = reservaConVentana('2026-10-19 09:00:00', '2026-10-22 09:00:00');
    $evaluar = app(EvaluarVentanaDeEntrega::class);

    $this->travelTo(Carbon::parse('2026-10-19 00:00:00', 'America/Bogota'));
    expect($evaluar->handle($reserva)->puedeEntregar)->toBeTrue();

    $this->travelTo(Carbon::parse('2026-10-21 23:59:59', 'America/Bogota'));
    expect($evaluar->handle($reserva)->puedeEntregar)->toBeTrue();

    $this->travelTo(Carbon::parse('2026-10-22 00:00:00', 'America/Bogota'));
    expect($evaluar->handle($reserva)->puedeEntregar)->toBeFalse()
        ->and($evaluar->handle($reserva)->motivoListado)->toBe('Vencida')
        ->and($evaluar->handle($reserva)->mensajeError)->toBe('La reserva venció el 22/10/2026; no se puede entregar');
});

it('bloquea una reserva futura con el mensaje de inicio', function () {
    $reserva = reservaConVentana('2026-10-20 09:00:00', '2026-10-23 09:00:00');

    $this->travelTo(Carbon::parse('2026-10-19 12:00:00', 'America/Bogota'));

    $resultado = app(EvaluarVentanaDeEntrega::class)->handle($reserva);

    expect($resultado->puedeEntregar)->toBeFalse()
        ->and($resultado->motivoListado)->toBe('Disponible desde 20/10/2026')
        ->and($resultado->mensajeError)->toBe('La reserva inicia el 20/10/2026; aún no se puede entregar');
});

it('respeta el límite de día en America/Bogota aunque el instante esté en UTC', function () {
    $reserva = reservaConVentana('2026-10-19 00:00:00', '2026-10-22 00:00:00');
    $evaluar = app(EvaluarVentanaDeEntrega::class);

    $this->travelTo(Carbon::parse('2026-10-19 04:59:59', 'UTC'));
    expect($evaluar->handle($reserva)->puedeEntregar)->toBeFalse();

    $this->travelTo(Carbon::parse('2026-10-19 05:00:00', 'UTC'));
    expect($evaluar->handle($reserva)->puedeEntregar)->toBeTrue();
});

it('usa la comparación exclusiva o inclusiva configurada', function () {
    $reserva = reservaConVentana('2026-10-19 00:00:00', '2026-10-22 00:00:00');
    $evaluar = app(EvaluarVentanaDeEntrega::class);

    config(['entrega.ventana_incluye_inicio' => false]);
    $this->travelTo(Carbon::parse('2026-10-19 12:00:00', 'America/Bogota'));
    expect($evaluar->handle($reserva)->puedeEntregar)->toBeFalse();

    $this->travelTo(Carbon::parse('2026-10-20 00:00:00', 'America/Bogota'));
    expect($evaluar->handle($reserva)->puedeEntregar)->toBeTrue();

    config(['entrega.ventana_incluye_inicio' => true, 'entrega.ventana_incluye_fin' => true]);
    $this->travelTo(Carbon::parse('2026-10-22 00:00:00', 'America/Bogota'));
    expect($evaluar->handle($reserva)->puedeEntregar)->toBeTrue();
});

it('lanza EntregaFueraDeVentana y redirige al listado con un mensaje flash', function () {
    $reserva = reservaConVentana('2026-10-20 09:00:00', '2026-10-23 09:00:00');
    $this->travelTo(Carbon::parse('2026-10-19 12:00:00', 'America/Bogota'));

    $evaluar = app(EvaluarVentanaDeEntrega::class);

    expect(fn () => $evaluar->asegurar($reserva))
        ->toThrow(EntregaFueraDeVentana::class);

    $excepcion = EntregaFueraDeVentana::desde($evaluar->handle($reserva));
    $respuesta = $excepcion->render();

    expect($respuesta->getTargetUrl())->toBe(route('entregas.index'))
        ->and($excepcion->getMessage())->toBe('La reserva inicia el 20/10/2026; aún no se puede entregar');
});

it('no lanza cuando la reserva está dentro de la ventana', function () {
    $reserva = reservaConVentana('2026-10-19 09:00:00', '2026-10-22 09:00:00');
    $this->travelTo(Carbon::parse('2026-10-19 10:00:00', 'America/Bogota'));

    expect(fn () => app(EvaluarVentanaDeEntrega::class)->asegurar($reserva))
        ->not->toThrow(EntregaFueraDeVentana::class);
});

it('construye el resultado dentro de la ventana', function () {
    $resultado = ResultadoVentanaDeEntrega::dentro();

    expect($resultado->puedeEntregar)->toBeTrue()
        ->and($resultado->motivoListado)->toBeNull()
        ->and($resultado->mensajeError)->toBeNull();
});
