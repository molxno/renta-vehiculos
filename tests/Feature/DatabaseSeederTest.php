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
use Database\Seeders\MinimumCatalogSeeder;
use Illuminate\Support\Facades\Hash;

test('database seeder loads the catalog and flow fixtures', function () {
    $this->seed();

    expect(User::count())->toBe(5)
        ->and(Vehiculo::count())->toBe(3)
        ->and(Reserva::count())->toBe(6)
        ->and(Factura::count())->toBe(2)
        ->and(Pago::count())->toBe(4)
        ->and(Pago::query()->where('estado', PagoEstado::Aprobado)->where('referencia', 'like', 'ANT-%')->count())->toBe(3);

    expect(Reserva::where('estado', ReservaEstado::Confirmada)->exists())->toBeTrue()
        ->and(Reserva::where('estado', ReservaEstado::Entregada)->exists())->toBeTrue()
        ->and(Reserva::where('estado', ReservaEstado::Devuelta)->exists())->toBeTrue()
        ->and(Reserva::where('estado', ReservaEstado::Facturada)->exists())->toBeTrue()
        ->and(Reserva::where('qr_codigo', 'QR-RESERVA-CONFIRMADA')->exists())->toBeTrue()
        ->and(Reserva::where('qr_codigo', 'QR-RESERVA-FUTURA')->exists())->toBeTrue()
        ->and(Reserva::where('qr_codigo', 'QR-RESERVA-VENCIDA')->exists())->toBeTrue()
        ->and(Reserva::where('qr_codigo', 'QR-ALQUILER-ENTREGADO')->exists())->toBeTrue()
        ->and(Reserva::where('qr_codigo', 'QR-DEVOLUCION-FACTURA')->exists())->toBeTrue()
        ->and(Reserva::where('qr_codigo', 'QR-PAGO-RECHAZADO')->exists())->toBeTrue()
        ->and(Factura::where('numero', 'FAC-DEVOLUCION-PENDIENTE')->where('estado', FacturaEstado::Pendiente)->exists())->toBeTrue()
        ->and(Factura::where('numero', 'FAC-PAGO-RECHAZADO')->where('estado', FacturaEstado::Pendiente)->exists())->toBeTrue()
        ->and(Pago::where('estado', PagoEstado::Rechazado)->exists())->toBeTrue()
        ->and(Hash::check('password', User::where('email', 'cliente@renta.test')->firstOrFail()->getAuthPassword()))->toBeTrue()
        ->and(User::where('email', 'cliente@renta.test')->firstOrFail()->getAuthPassword())->toStartWith('$2y$04$');
});

test('minimum catalog seeder skips after the demo client already exists', function () {
    $this->seed(MinimumCatalogSeeder::class);
    $userId = User::where('email', 'cliente@renta.test')->value('id');

    $this->seed(MinimumCatalogSeeder::class);

    expect(User::where('email', 'cliente@renta.test')->value('id'))->toBe($userId)
        ->and(User::count())->toBe(5)
        ->and(Vehiculo::count())->toBe(3);
});

test('flow fixture seeders can run independently', function () {
    $this->seed(DevolucionFacturadaSeeder::class);

    expect(Reserva::where('qr_codigo', 'QR-DEVOLUCION-FACTURA')->exists())->toBeTrue()
        ->and(Factura::where('numero', 'FAC-DEVOLUCION-PENDIENTE')->exists())->toBeTrue();
});
