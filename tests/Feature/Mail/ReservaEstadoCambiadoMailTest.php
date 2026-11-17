<?php

declare(strict_types=1);

use App\Enums\ReservaEstado;
use App\Events\ReservaEstadoCambiado;
use App\Mail\ReservaEstadoCambiadoMail;
use App\Models\Reserva;

it('renders a subject and body that name the new status', function (ReservaEstado $nuevo, string $asunto, string $etiqueta) {
    $reserva = Reserva::factory()->create();
    $mailable = new ReservaEstadoCambiadoMail(
        new ReservaEstadoCambiado($reserva, ReservaEstado::Pendiente, $nuevo, now()),
    );

    $mailable->assertHasSubject($asunto)
        ->assertSeeInText((string) $reserva->id)
        ->assertSeeInHtml($etiqueta)
        ->assertSeeInHtml(route('reservas.show', $reserva));
})->with([
    'confirmada' => [ReservaEstado::Confirmada, 'Tu reserva fue confirmada', 'confirmada'],
    'entregada' => [ReservaEstado::Entregada, 'Tu vehículo fue entregado', 'entregada'],
    'facturada' => [ReservaEstado::Facturada, 'Tu factura está lista', 'facturada'],
    'pagada' => [ReservaEstado::Pagada, 'Tu pago fue registrado', 'pagada'],
    'cancelada' => [ReservaEstado::Cancelada, 'Actualización de tu reserva', 'cancelada'],
]);
