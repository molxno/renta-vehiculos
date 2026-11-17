<?php

declare(strict_types=1);

use App\Models\Reserva;
use App\Services\Pagos\ProcesadorPagoEfectivo;
use Tests\TestCase;

uses(TestCase::class);

it('authorizes cash without a card number', function () {
    $reserva = new Reserva;
    $reserva->id = 7;

    $resultado = (new ProcesadorPagoEfectivo)->procesar($reserva, 150000, null);

    expect($resultado->aprobado)->toBeTrue()
        ->and($resultado->referencia)->toBe('FAKE-EFECTIVO-7')
        ->and($resultado->mensaje)->toBe('Pago autorizado')
        ->and($resultado->codigo)->toBe('00');
});
