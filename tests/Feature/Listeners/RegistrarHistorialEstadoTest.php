<?php

declare(strict_types=1);

use App\Enums\ReservaEstado;
use App\Models\Reserva;
use App\Models\ReservaHistorialEstado;

it('stores the previous and next status for a confirmation', function () {
    $this->freezeTime();
    $reserva = Reserva::factory()->pendiente()->create();

    $reserva->confirmar();

    $historial = ReservaHistorialEstado::query()->where('reserva_id', $reserva->id)->sole();

    expect($historial->estado_anterior)->toBe(ReservaEstado::Pendiente)
        ->and($historial->estado_nuevo)->toBe(ReservaEstado::Confirmada)
        ->and($historial->ocurrio_at?->toDateTimeString())->toBe(now()->toDateTimeString());
});

it('prefers the historial timestamp on the client timeline', function () {
    $this->freezeTime();
    $reserva = Reserva::factory()->pendiente()->create();
    $reserva->confirmar();
    $reserva->entregar();
    $reserva->devolver();
    $reserva->pagar();

    $fecha = now()->toIso8601String();

    $this->actingAs($reserva->user)
        ->withHeaders(cabecerasInertia())
        ->get(route('reservas.show', $reserva))
        ->assertOk()
        ->assertJsonPath('props.timeline.0.done', true)
        ->assertJsonPath('props.timeline.0.at', $fecha)
        ->assertJsonPath('props.timeline.1.done', true)
        ->assertJsonPath('props.timeline.1.at', $fecha)
        ->assertJsonPath('props.timeline.2.done', true)
        ->assertJsonPath('props.timeline.2.at', $fecha)
        ->assertJsonPath('props.timeline.3.done', true)
        ->assertJsonPath('props.timeline.3.at', $fecha);

    expect(ReservaHistorialEstado::query()->where('reserva_id', $reserva->id)->count())->toBe(4);
});
