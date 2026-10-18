<?php

declare(strict_types=1);

use App\Enums\ReservaEstado;
use App\Exceptions\TransicionEstadoInvalida;
use App\Models\Reserva;

it('allows the legal reservation transitions', function (ReservaEstado $desde, string $operacion, ReservaEstado $hacia) {
    $reserva = Reserva::factory()->create(['estado' => $desde]);

    $reserva->{$operacion}();

    expect($reserva->fresh()->estado)->toBe($hacia);
})->with([
    'confirmar pendiente' => [ReservaEstado::Pendiente, 'confirmar', ReservaEstado::Confirmada],
    'cancelar pendiente' => [ReservaEstado::Pendiente, 'cancelar', ReservaEstado::Cancelada],
    'entregar confirmada' => [ReservaEstado::Confirmada, 'entregar', ReservaEstado::Entregada],
    'cancelar confirmada' => [ReservaEstado::Confirmada, 'cancelar', ReservaEstado::Cancelada],
    'devolver entregada' => [ReservaEstado::Entregada, 'devolver', ReservaEstado::Facturada],
    'pagar facturada' => [ReservaEstado::Facturada, 'pagar', ReservaEstado::Pagada],
]);

it('rejects an illegal reservation transition', function (ReservaEstado $estado, string $operacion) {
    $reserva = Reserva::factory()->create(['estado' => $estado]);

    expect(fn () => $reserva->{$operacion}())
        ->toThrow(TransicionEstadoInvalida::class);
})->with([
    'pendiente entregar' => [ReservaEstado::Pendiente, 'entregar'],
    'pendiente devolver' => [ReservaEstado::Pendiente, 'devolver'],
    'pendiente pagar' => [ReservaEstado::Pendiente, 'pagar'],
    'confirmada confirmar' => [ReservaEstado::Confirmada, 'confirmar'],
    'confirmada devolver' => [ReservaEstado::Confirmada, 'devolver'],
    'confirmada pagar' => [ReservaEstado::Confirmada, 'pagar'],
    'entregada confirmar' => [ReservaEstado::Entregada, 'confirmar'],
    'entregada entregar' => [ReservaEstado::Entregada, 'entregar'],
    'entregada pagar' => [ReservaEstado::Entregada, 'pagar'],
    'entregada cancelar' => [ReservaEstado::Entregada, 'cancelar'],
    'facturada confirmar' => [ReservaEstado::Facturada, 'confirmar'],
    'facturada entregar' => [ReservaEstado::Facturada, 'entregar'],
    'facturada devolver' => [ReservaEstado::Facturada, 'devolver'],
    'facturada cancelar' => [ReservaEstado::Facturada, 'cancelar'],
    'pagada confirmar' => [ReservaEstado::Pagada, 'confirmar'],
    'pagada entregar' => [ReservaEstado::Pagada, 'entregar'],
    'pagada devolver' => [ReservaEstado::Pagada, 'devolver'],
    'pagada pagar' => [ReservaEstado::Pagada, 'pagar'],
    'pagada cancelar' => [ReservaEstado::Pagada, 'cancelar'],
    'cancelada confirmar' => [ReservaEstado::Cancelada, 'confirmar'],
    'cancelada entregar' => [ReservaEstado::Cancelada, 'entregar'],
    'cancelada devolver' => [ReservaEstado::Cancelada, 'devolver'],
    'cancelada pagar' => [ReservaEstado::Cancelada, 'pagar'],
    'cancelada cancelar' => [ReservaEstado::Cancelada, 'cancelar'],
    'devuelta pagar' => [ReservaEstado::Devuelta, 'pagar'],
    'devuelta cancelar' => [ReservaEstado::Devuelta, 'cancelar'],
]);

it('describes an unknown transition with the current status', function () {
    expect(TransicionEstadoInvalida::para('archivar', ReservaEstado::Pendiente)->getMessage())
        ->toBe('No se puede archivar una reserva en estado pendiente.');
});
