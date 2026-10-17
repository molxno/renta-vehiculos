<?php

use App\Enums\ReservaEstado;
use App\Models\Factura;
use App\Models\Reserva;

test('seeded MVP exposes the complete smoke flow', function () {
    $this->seed();

    $reservation = Reserva::where('estado', ReservaEstado::Confirmada)->firstOrFail();
    $invoice = Factura::query()->firstOrFail();
    $headers = [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => hash_file(
            'xxh128',
            public_path('build/manifest.json'),
        ),
        'X-Requested-With' => 'XMLHttpRequest',
    ];

    $this->withHeaders($headers)
        ->get(route('reservas.index'))
        ->assertOk()
        ->assertJsonPath('component', 'Reservas/Index');

    $this->withHeaders($headers)
        ->get(route('reservas.show', $reservation))
        ->assertOk()
        ->assertJsonPath('component', 'Reservas/Show')
        ->assertJsonPath('props.reservation.id', $reservation->id);

    $this->withHeaders($headers)
        ->get(route('entregas.create', $reservation))
        ->assertOk()
        ->assertJsonPath('component', 'Entregas/Create');

    $this->withHeaders($headers)
        ->get(route('devoluciones.create', $reservation))
        ->assertOk()
        ->assertJsonPath('component', 'Devoluciones/Create');

    $this->withHeaders($headers)
        ->get(route('pagos.create', $reservation))
        ->assertOk()
        ->assertJsonPath('component', 'Pagos/Create');

    $this->withHeaders($headers)
        ->get(route('facturas.show', $invoice))
        ->assertOk()
        ->assertJsonPath('component', 'Facturas/Show');
});
