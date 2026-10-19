<?php

declare(strict_types=1);

use App\Enums\ReservaEstado;
use App\Models\LicenciaConduccion;
use App\Models\Reserva;

it('usa fechaHoraLocal con Date.getTime y no la variable ahora', function () {
    $utils = (string) file_get_contents(resource_path('js/lib/utils.ts'));
    $create = (string) file_get_contents(resource_path('js/pages/Entregas/Create.vue'));

    expect($utils)->toContain('export function fechaHoraLocal')
        ->and($utils)->toContain('fecha.getTime()')
        ->and($utils)->not->toContain('ahora')
        ->and($create)->toContain("import { fechaHoraLocal, formatFecha } from '@/lib/utils'")
        ->and($create)->not->toContain('ahora.getTime()')
        ->and($create)->not->toContain('function fechaHoraLocal');
});

it('envía a Entregas/Create las props que el componente declara', function () {
    $reservation = Reserva::factory()->enVentanaDeEntrega()->create([
        'estado' => ReservaEstado::Confirmada,
        'qr_codigo' => 'FAKE-QR-PROPS',
    ]);
    LicenciaConduccion::factory()->create([
        'user_id' => $reservation->user_id,
        'fecha_vencimiento' => now()->addYear()->toDateString(),
        'categoria' => 'B1',
    ]);

    autenticarEmpleado();

    $payload = $this->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json')),
        'X-Requested-With' => 'XMLHttpRequest',
    ])->get(route('entregas.create', $reservation))->assertOk()->json('props');

    expect($payload)->toHaveKeys([
        'reservation',
        'cliente',
        'vehiculo',
        'qrCodigo',
        'checklist',
        'condicionesContrato',
        'licencia',
        'bloqueo',
        'anticipoCents',
        'currency',
        'storeUrl',
        'nivelesCombustible',
    ])->and($payload['cliente'])->toHaveKeys(['nombre', 'documento'])
        ->and($payload['vehiculo'])->toHaveKeys(['label', 'placa'])
        ->and($payload['reservation'])->toHaveKeys(['id', 'estado', 'inicio', 'fin', 'total_centavos', 'moneda']);
});
