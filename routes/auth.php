<?php

use App\Http\Controllers\Auth\RegistroController;
use App\Http\Controllers\Auth\SesionController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [SesionController::class, 'create']);
    Route::get('/iniciar-sesion', [SesionController::class, 'create'])->name('login');
    Route::post('/iniciar-sesion', [SesionController::class, 'store'])->name('login.store');
    Route::get('/registro', [RegistroController::class, 'create'])->name('register');
    Route::post('/registro', [RegistroController::class, 'store'])->name('register.store');
});

Route::post('/cerrar-sesion', [SesionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');
