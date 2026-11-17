<?php

declare(strict_types=1);

use App\Enums\MetodoPago;
use App\Fakes\FakePasarelaPago;
use App\Services\Pagos\FabricaProcesadorPago;
use App\Services\Pagos\ProcesadorPagoEfectivo;
use App\Services\Pagos\ProcesadorPagoTarjeta;
use App\Services\Pagos\ProcesadorPagoTransferencia;
use Tests\TestCase;

uses(TestCase::class);

it('creates the processor that matches the payment method', function (MetodoPago $metodo, string $clase) {
    $fabrica = new FabricaProcesadorPago(new FakePasarelaPago);

    expect($fabrica->crear($metodo))->toBeInstanceOf($clase);
})->with([
    'tarjeta' => [MetodoPago::Tarjeta, ProcesadorPagoTarjeta::class],
    'efectivo' => [MetodoPago::Efectivo, ProcesadorPagoEfectivo::class],
    'transferencia' => [MetodoPago::Transferencia, ProcesadorPagoTransferencia::class],
]);
