<?php

declare(strict_types=1);

use App\Models\Reserva;
use App\Services\Pagos\ProcesadorPagoTransferencia;
use Tests\TestCase;

uses(TestCase::class);

it('authorizes a transfer without a card number', function () {
    $reserva = new Reserva;
    $reserva->id = 8;

    $resultado = (new ProcesadorPagoTransferencia)->procesar($reserva, 150000, '4111111111110000');

    expect($resultado->aprobado)->toBeTrue()
        ->and($resultado->referencia)->toBe('FAKE-TRANSFERENCIA-8')
        ->and($resultado->mensaje)->toBe('Pago autorizado')
        ->and($resultado->codigo)->toBe('00');
});
