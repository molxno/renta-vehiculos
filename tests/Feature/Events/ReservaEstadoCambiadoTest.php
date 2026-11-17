<?php

declare(strict_types=1);

use App\Enums\ReservaEstado;
use App\Events\ReservaEstadoCambiado;
use App\Exceptions\TransicionEstadoInvalida;
use App\Models\Reserva;
use Illuminate\Support\Facades\Event;

it('dispatches a state-changed event from a legal transition', function () {
    Event::fake([ReservaEstadoCambiado::class]);
    $reserva = Reserva::factory()->pendiente()->create();

    $reserva->confirmar();

    Event::assertDispatched(
        ReservaEstadoCambiado::class,
        function (ReservaEstadoCambiado $evento) use ($reserva): bool {
            return $evento->reserva->is($reserva)
                && $evento->estadoAnterior === ReservaEstado::Pendiente
                && $evento->estadoNuevo === ReservaEstado::Confirmada;
        },
    );
});

it('does not dispatch when the transition is illegal', function () {
    Event::fake([ReservaEstadoCambiado::class]);
    $reserva = Reserva::factory()->pendiente()->create();

    expect(fn () => $reserva->pagar())->toThrow(TransicionEstadoInvalida::class);
    Event::assertNotDispatched(ReservaEstadoCambiado::class);
});
