<?php

declare(strict_types=1);

use App\Actions\Devoluciones\RegistrarDevolucion;
use App\Actions\Entregas\RegistrarEntrega;
use App\Enums\ReservaEstado;
use App\Models\Reserva;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\ValidationException;

it('rejects delivering a reservation that is not confirmed from the action', function () {
    $reserva = Reserva::factory()->pendiente()->create();

    expect(fn () => app(RegistrarEntrega::class)->handle(
        $reserva,
        Date::parse('2026-02-01 09:00:00'),
        null,
        null,
    ))->toThrow(ValidationException::class);

    expect($reserva->fresh()->estado)->toBe(ReservaEstado::Pendiente);
});

it('rejects returning a reservation that was not delivered from the action', function () {
    $reserva = Reserva::factory()->create(['estado' => ReservaEstado::Confirmada]);

    expect(fn () => app(RegistrarDevolucion::class)->handle(
        $reserva,
        Date::parse('2026-02-08 09:00:00'),
        null,
        null,
    ))->toThrow(ValidationException::class);

    expect($reserva->fresh()->estado)->toBe(ReservaEstado::Confirmada);
});
