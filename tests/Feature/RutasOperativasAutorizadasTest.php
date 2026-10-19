<?php

declare(strict_types=1);

use App\Enums\UsuarioRol;
use App\Models\Factura;
use App\Models\Reserva;
use App\Models\User;

it('redirects guests from operational rental routes to login', function (string $routeName, array $parameters) {
    $this->get(route($routeName, $parameters))->assertRedirectToRoute('login');
})->with([
    'entregas' => fn (): array => ['entregas.create', ['reserva' => Reserva::factory()->create()]],
    'entregas index' => fn (): array => ['entregas.index', []],
    'entregas show' => fn (): array => ['entregas.show', ['reserva' => Reserva::factory()->create()]],
    'devoluciones' => fn (): array => ['devoluciones.create', ['reserva' => Reserva::factory()->create()]],
    'devoluciones index' => fn (): array => ['devoluciones.index', []],
    'devoluciones show' => fn (): array => ['devoluciones.show', ['reserva' => Reserva::factory()->create()]],
    'pagos' => fn (): array => ['pagos.create', ['reserva' => Reserva::factory()->create()]],
    'pagos index' => fn (): array => ['pagos.index', []],
    'pagos show' => fn (): array => ['pagos.show', ['reserva' => Reserva::factory()->create()]],
    'facturas' => fn (): array => ['facturas.show', ['factura' => Factura::factory()->create()]],
    'reservas index' => fn (): array => ['reservas.index', []],
]);

it('forbids a client from opening operational employee routes', function (string $routeName, array $parameters) {
    $this->actingAs(User::factory()->cliente()->create())
        ->get(route($routeName, $parameters))
        ->assertForbidden();
})->with([
    'entregas' => fn (): array => ['entregas.create', ['reserva' => Reserva::factory()->create()]],
    'entregas index' => fn (): array => ['entregas.index', []],
    'entregas show' => fn (): array => ['entregas.show', ['reserva' => Reserva::factory()->create()]],
    'devoluciones' => fn (): array => ['devoluciones.create', ['reserva' => Reserva::factory()->create()]],
    'devoluciones index' => fn (): array => ['devoluciones.index', []],
    'devoluciones show' => fn (): array => ['devoluciones.show', ['reserva' => Reserva::factory()->create()]],
    'pagos' => fn (): array => ['pagos.create', ['reserva' => Reserva::factory()->create()]],
    'pagos index' => fn (): array => ['pagos.index', []],
    'pagos show' => fn (): array => ['pagos.show', ['reserva' => Reserva::factory()->create()]],
    'facturas' => fn (): array => ['facturas.show', ['factura' => Factura::factory()->create()]],
]);

it('allows an employee to open operational routes', function (UsuarioRol $rol) {
    $reservation = Reserva::factory()->create();
    $invoice = Factura::factory()->create();

    autenticarEmpleado($rol);

    $this->get(route('entregas.index'))->assertOk();
    $this->get(route('entregas.create', $reservation))->assertOk();
    $this->get(route('devoluciones.index'))->assertOk();
    $this->get(route('devoluciones.create', $reservation))->assertOk();
    $this->get(route('facturas.show', $invoice))->assertOk();
})->with([
    'coordinador' => UsuarioRol::Coordinador,
    'cajero' => UsuarioRol::Cajero,
    'administrador' => UsuarioRol::Administrador,
]);

it('allows cashiers and administrators to collect payment', function (UsuarioRol $rol) {
    $reservation = Reserva::factory()->create();

    autenticarEmpleado($rol);

    $this->get(route('pagos.index'))->assertOk();
    $this->get(route('pagos.create', $reservation))->assertOk();
})->with([
    'cajero' => UsuarioRol::Cajero,
    'administrador' => UsuarioRol::Administrador,
]);

it('forbids a coordinator from collecting payment', function () {
    $reservation = Reserva::factory()->create();

    autenticarEmpleado(UsuarioRol::Coordinador);

    $this->get(route('pagos.index'))->assertForbidden();
    $this->get(route('pagos.create', $reservation))->assertForbidden();
    $this->get(route('pagos.show', $reservation))->assertForbidden();
    $this->post(route('pagos.store', $reservation), [])->assertForbidden();
});
