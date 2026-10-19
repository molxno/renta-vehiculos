<?php

use App\Enums\FacturaEstado;
use App\Enums\PagoEstado;
use App\Enums\ReservaEstado;
use App\Models\Factura;
use App\Models\Pago;
use App\Models\Reserva;
use App\Models\User;
use App\Models\Vehiculo;
use Database\Seeders\DevolucionFacturadaSeeder;

test('database seeder loads the catalog and flow fixtures', function () {
    $this->seed();

    expect(User::count())->toBe(5)
        ->and(Vehiculo::count())->toBe(3)
        ->and(Reserva::count())->toBe(4)
        ->and(Factura::count())->toBe(2)
        ->and(Pago::count())->toBe(1);

    expect(Reserva::where('estado', ReservaEstado::Confirmada)->exists())->toBeTrue()
        ->and(Reserva::where('estado', ReservaEstado::Entregada)->exists())->toBeTrue()
        ->and(Reserva::where('estado', ReservaEstado::Devuelta)->exists())->toBeTrue()
        ->and(Reserva::where('estado', ReservaEstado::Facturada)->exists())->toBeTrue()
        ->and(Reserva::where('qr_codigo', 'QR-RESERVA-CONFIRMADA')->exists())->toBeTrue()
        ->and(Reserva::where('qr_codigo', 'QR-ALQUILER-ENTREGADO')->exists())->toBeTrue()
        ->and(Reserva::where('qr_codigo', 'QR-DEVOLUCION-FACTURA')->exists())->toBeTrue()
        ->and(Reserva::where('qr_codigo', 'QR-PAGO-RECHAZADO')->exists())->toBeTrue()
        ->and(Factura::where('numero', 'FAC-DEVOLUCION-PENDIENTE')->where('estado', FacturaEstado::Pendiente)->exists())->toBeTrue()
        ->and(Factura::where('numero', 'FAC-PAGO-RECHAZADO')->where('estado', FacturaEstado::Pendiente)->exists())->toBeTrue()
        ->and(Pago::where('estado', PagoEstado::Rechazado)->exists())->toBeTrue();
});

test('flow fixture seeders can run independently', function () {
    $this->seed(DevolucionFacturadaSeeder::class);

    expect(Reserva::where('qr_codigo', 'QR-DEVOLUCION-FACTURA')->exists())->toBeTrue()
        ->and(Factura::where('numero', 'FAC-DEVOLUCION-PENDIENTE')->exists())->toBeTrue();
});
