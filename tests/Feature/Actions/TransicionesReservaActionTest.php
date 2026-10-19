<?php

declare(strict_types=1);

use App\Actions\Devoluciones\RegistrarDevolucion;
use App\Actions\Entregas\RegistrarEntrega;
use App\Enums\ReservaEstado;
use App\Models\LicenciaConduccion;
use App\Models\Reserva;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\ValidationException;

it('rejects delivering a reservation that is not confirmed from the action', function () {
    $reserva = Reserva::factory()->pendiente()->enVentanaDeEntrega()->create();

    expect(fn () => app(RegistrarEntrega::class)->handle(
        $reserva,
        Date::parse('2026-02-01 09:00:00'),
        null,
        null,
    ))->toThrow(ValidationException::class);

    expect($reserva->fresh()->estado)->toBe(ReservaEstado::Pendiente);
});

it('records an unverified license when the delivery action runs without one', function () {
    $reserva = Reserva::factory()->enVentanaDeEntrega()->create();

    $acta = app(RegistrarEntrega::class)->handle($reserva, Date::now(), null, null);

    expect($acta->licencia_resultado)->toBe('Licencia no verificada')
        ->and($acta->coordinador_id)->toBeNull()
        ->and($acta->numero)->toBe('ACT-'.$acta->id)
        ->and($acta->firmante_nombre)->toBe($reserva->user->name);
});

it('records expired and invalid license verification on the delivery certificate', function () {
    $reserva = Reserva::factory()->enVentanaDeEntrega()->create();
    LicenciaConduccion::factory()->create([
        'user_id' => $reserva->user_id,
        'numero' => 'LIC-X',
        'fecha_vencimiento' => now()->subDay()->toDateString(),
        'categoria' => 'XYZ',
    ]);

    $acta = app(RegistrarEntrega::class)->handle(
        $reserva,
        Date::now(),
        ['combustible' => 'medio'],
        null,
    );

    expect($acta->licencia_resultado)->toBe('Licencia LIC-X vencida, categoría XYZ no válida');
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
