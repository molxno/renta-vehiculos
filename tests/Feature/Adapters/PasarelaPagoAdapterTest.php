<?php

declare(strict_types=1);

use App\Contracts\PasarelaPago;
use App\Fakes\FakePasarelaPago;
use App\Services\Adapters\PasarelaPagoAdapter;
use App\Services\ExternalPaymentSdk\BankPaymentGateway;

it('translates an approved bank transaction into the payment port', function () {
    $resultado = app(PasarelaPago::class)->autorizar(12, 53550000, '4111111111111111');

    expect($resultado)->toBe([
        'aprobado' => true,
        'referencia' => 'FAKE-PAY-12',
        'mensaje' => 'Pago autorizado',
        'codigo' => '00',
    ]);
});

it('translates a declined bank transaction into the payment port', function () {
    $resultado = (new PasarelaPagoAdapter(new BankPaymentGateway))
        ->autorizar(12, 100, '4111111111110000');

    expect($resultado)->toBe([
        'aprobado' => false,
        'referencia' => null,
        'mensaje' => 'Pago rechazado',
        'codigo' => '05',
    ]);
});

it('keeps the original fake gateway for isolated bindings', function () {
    $pasarela = new FakePasarelaPago;

    expect($pasarela->autorizar(3, 100, '4111111111111111')['aprobado'])->toBeTrue()
        ->and($pasarela->autorizar(3, 100, '4111111111110000'))->toMatchArray([
            'aprobado' => false,
            'referencia' => null,
            'mensaje' => 'Pago rechazado',
            'codigo' => '05',
        ]);
});
