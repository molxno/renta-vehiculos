<?php

declare(strict_types=1);

use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\DevolucionController;
use App\Http\Controllers\EntregaController;
use App\Http\Controllers\FacturaController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\TrmController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/catalogo', CatalogoController::class)->name('catalogo');

Route::get('/reservas', [ReservaController::class, 'index'])->name('reservas.index');
Route::get('/reservas/crear', [ReservaController::class, 'create'])
    ->middleware('auth')
    ->name('reservas.create');
Route::post('/reservas', [ReservaController::class, 'store'])
    ->middleware('auth')
    ->name('reservas.store');
Route::get('/reservas/qr/{qrCodigo}', [ReservaController::class, 'showByQrCode'])->name('reservas.qr');
Route::get('/reservas/{reserva}', [ReservaController::class, 'show'])->name('reservas.show');
Route::post('/reservas/{reserva}/confirmar', [ReservaController::class, 'confirmar'])
    ->middleware('auth')
    ->name('reservas.confirmar');
Route::post('/reservas/{reserva}/cancelar', [ReservaController::class, 'cancelar'])->name('reservas.cancelar');

Route::middleware(['auth', 'rol:administrador'])
    ->prefix('administracion')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/trm', [TrmController::class, 'edit'])->name('trm.edit');
        Route::put('/trm', [TrmController::class, 'update'])->name('trm.update');
    });

Route::get('/reservas/{reserva}/entrega', [EntregaController::class, 'create'])->name('entregas.create');
Route::post('/reservas/{reserva}/entrega', [EntregaController::class, 'store'])->name('entregas.store');
Route::get('/reservas/{reserva}/devolucion', [DevolucionController::class, 'create'])->name('devoluciones.create');
Route::post('/reservas/{reserva}/devolucion', [DevolucionController::class, 'store'])->name('devoluciones.store');

Route::get('/facturas/{factura}', [FacturaController::class, 'show'])->name('facturas.show');
Route::get('/reservas/{reserva}/pago', [PagoController::class, 'create'])->name('pagos.create');
Route::post('/reservas/{reserva}/pago', [PagoController::class, 'store'])->name('pagos.store');

require __DIR__.'/auth.php';
