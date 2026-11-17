<?php

declare(strict_types=1);

use App\Enums\ReservaEstado;
use App\Mail\ReservaEstadoCambiadoMail;
use App\Models\Reserva;
use App\Models\ReservaHistorialEstado;
use Illuminate\Support\Facades\Mail;

it('emails the client for operational status changes', function (ReservaEstado $desde, string $operacion, ReservaEstado $hacia) {
    Mail::fake();
    $reserva = Reserva::factory()->create(['estado' => $desde]);

    $reserva->{$operacion}();

    Mail::assertSent(
        ReservaEstadoCambiadoMail::class,
        fn (ReservaEstadoCambiadoMail $mail): bool => $mail->hasTo($reserva->user->email)
            && $mail->evento->estadoNuevo === $hacia,
    );
})->with([
    'confirmar' => [ReservaEstado::Pendiente, 'confirmar', ReservaEstado::Confirmada],
    'entregar' => [ReservaEstado::Confirmada, 'entregar', ReservaEstado::Entregada],
    'facturar' => [ReservaEstado::Entregada, 'devolver', ReservaEstado::Facturada],
    'pagar' => [ReservaEstado::Facturada, 'pagar', ReservaEstado::Pagada],
]);

it('does not email the client when the reservation is cancelled', function () {
    Mail::fake();
    $reserva = Reserva::factory()->pendiente()->create();

    $reserva->cancelar();

    Mail::assertNothingSent();
    expect(ReservaHistorialEstado::query()->where('reserva_id', $reserva->id)->count())->toBe(1);
});
