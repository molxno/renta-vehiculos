<?php

declare(strict_types=1);

use App\Services\ConvertidorTrm;
use App\Services\ExternalPaymentSdk\BankPaymentGateway;

it('scales a TRM with and without a decimal part', function () {
    $convertidor = new ConvertidorTrm;

    expect($convertidor->escalarDecimal('4200.2500'))->toBe(42002500)
        ->and($convertidor->escalarDecimal('4200'))->toBe(42000000);
});

it('converts COP cents to USD cents using a scaled TRM', function () {
    expect((new ConvertidorTrm)->convertirACentavosUsd(30000000, 42002500))->toBe(7142);
});

it('approves a positive amount unless the card ends in 0000', function () {
    $pasarela = new BankPaymentGateway;

    expect($pasarela->processTransaction(100.5, '4111111111111111')['success'])->toBeTrue()
        ->and($pasarela->processTransaction(100.5, '4111111111110000')['success'])->toBeFalse()
        ->and($pasarela->processTransaction(0, '4111111111111111')['success'])->toBeFalse();
});
