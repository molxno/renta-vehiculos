<?php

use App\Http\Controllers\DevolucionController;
use App\Http\Controllers\EntregaController;
use App\Http\Controllers\FacturaController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\ReservaController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::get('/reservas', [ReservaController::class, 'index'])->name('reservas.index');
Route::get('/reservas/crear', [ReservaController::class, 'create'])->name('reservas.create');
Route::post('/reservas', [ReservaController::class, 'store'])->name('reservas.store');
Route::get('/reservas/{reserva}', [ReservaController::class, 'show'])->name('reservas.show');
Route::post('/reservas/{reserva}/confirmar', [ReservaController::class, 'confirmar'])->name('reservas.confirmar');
Route::post('/reservas/{reserva}/cancelar', [ReservaController::class, 'cancelar'])->name('reservas.cancelar');

Route::get('/reservas/{reserva}/entrega', [EntregaController::class, 'create'])->name('entregas.create');
Route::post('/reservas/{reserva}/entrega', [EntregaController::class, 'store'])->name('entregas.store');
Route::get('/reservas/{reserva}/devolucion', [DevolucionController::class, 'create'])->name('devoluciones.create');
Route::post('/reservas/{reserva}/devolucion', [DevolucionController::class, 'store'])->name('devoluciones.store');

Route::get('/facturas/{factura}', [FacturaController::class, 'show'])->name('facturas.show');
Route::get('/reservas/{reserva}/pago', [PagoController::class, 'create'])->name('pagos.create');
Route::post('/reservas/{reserva}/pago', [PagoController::class, 'store'])->name('pagos.store');
