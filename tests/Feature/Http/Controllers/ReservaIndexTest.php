<?php

declare(strict_types=1);

use App\Actions\Reservas\ListarReservas;
use App\Enums\ReservaEstado;
use App\Http\Controllers\ReservaController;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('forbids listing reservations when the request has no authenticated user', function () {
    expect(fn () => app(ReservaController::class)->index(Request::create('/reservas'), app(ListarReservas::class)))
        ->toThrow(HttpException::class);
});

it('exposes the empty catalog call to action when the client has no reservations', function () {
    $cliente = User::factory()->cliente()->create();

    $this->actingAs($cliente)
        ->withHeaders([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json')),
            'X-Requested-With' => 'XMLHttpRequest',
        ])
        ->get(route('reservas.index'))
        ->assertOk()
        ->assertJsonPath('component', 'Reservas/Index')
        ->assertJsonPath('props.reservations', [])
        ->assertJsonPath('props.catalogoUrl', route('catalogo'));
});

it('lists only the authenticated client reservations', function () {
    $cliente = User::factory()->cliente()->create();
    $otro = User::factory()->cliente()->create();
    $propia = Reserva::factory()->create(['user_id' => $cliente->id]);
    Reserva::factory()->create(['user_id' => $otro->id]);

    $this->actingAs($cliente)
        ->withHeaders([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json')),
            'X-Requested-With' => 'XMLHttpRequest',
        ])
        ->get(route('reservas.index'))
        ->assertOk()
        ->assertJsonPath('component', 'Reservas/Index')
        ->assertJsonPath('props.reservations.0.id', $propia->id)
        ->assertJsonPath('props.reservations.0.vehiclePlate', $propia->vehiculo->placa)
        ->assertJsonCount(1, 'props.reservations');
});

it('lists every reservation for an employee', function () {
    Reserva::factory()->create();
    Reserva::factory()->create();

    autenticarEmpleado();

    $this->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json')),
        'X-Requested-With' => 'XMLHttpRequest',
    ])
        ->get(route('reservas.index'))
        ->assertOk()
        ->assertJsonCount(2, 'props.reservations');
});

it('cancels a pending reservation owned by the client', function () {
    $cliente = User::factory()->cliente()->create();
    $reserva = Reserva::factory()->pendiente()->create(['user_id' => $cliente->id]);

    $this->actingAs($cliente)
        ->post(route('reservas.cancelar', $reserva))
        ->assertRedirectToRoute('reservas.show', $reserva);

    expect($reserva->fresh()->estado)->toBe(ReservaEstado::Cancelada);
});

it('does not cancel a delivered reservation', function () {
    $cliente = User::factory()->cliente()->create();
    $reserva = Reserva::factory()->create([
        'user_id' => $cliente->id,
        'estado' => ReservaEstado::Entregada,
    ]);

    $this->actingAs($cliente)
        ->from(route('reservas.show', $reserva))
        ->post(route('reservas.cancelar', $reserva))
        ->assertRedirect(route('reservas.show', $reserva))
        ->assertSessionHasErrors([
            'reserva' => 'No se puede cancelar una reserva en estado entregada.',
        ]);

    expect($reserva->fresh()->estado)->toBe(ReservaEstado::Entregada);
});
