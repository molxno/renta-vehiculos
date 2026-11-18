<?php

declare(strict_types=1);

use App\Fakes\FakePasarelaPago;
use App\Models\Reserva;
use App\Services\Pagos\ProcesadorPagoTarjeta;
use Tests\TestCase;

uses(TestCase::class);

function reservaDePago(int $id): Reserva
{
    $reserva = new Reserva;
    $reserva->id = $id;

    return $reserva;
}

it('approves a card that the gateway accepts', function () {
    $resultado = (new ProcesadorPagoTarjeta(new FakePasarelaPago))
        ->procesar(reservaDePago(12), 53550000, '4111111111111111');

    expect($resultado->aprobado)->toBeTrue()
        ->and($resultado->referencia)->toBe('FAKE-PAY-12')
        ->and($resultado->mensaje)->toBe('Pago autorizado')
        ->and($resultado->codigo)->toBe('00');
});

it('rejects a card ending in 0000', function () {
    $resultado = (new ProcesadorPagoTarjeta(new FakePasarelaPago))
        ->procesar(reservaDePago(12), 53550000, '4111111111110000');

    expect($resultado->aprobado)->toBeFalse()
        ->and($resultado->referencia)->toBeNull()
        ->and($resultado->mensaje)->toBe('Pago rechazado')
        ->and($resultado->codigo)->toBe('05');
});

it('casts a missing card number before calling the gateway', function () {
    $resultado = (new ProcesadorPagoTarjeta(new FakePasarelaPago))
        ->procesar(reservaDePago(9), 1000, null);

    expect($resultado->aprobado)->toBeTrue()
        ->and($resultado->referencia)->toBe('FAKE-PAY-9');
});
