<?php

use App\Enums\FacturaEstado;
use App\Models\Factura;

it('renders the invoice HTML page with the documented breakdown', function () {
    $invoice = Factura::factory()->create([
        'numero' => 'FAC-HTML-001',
        'estado' => FacturaEstado::Pendiente,
        'subtotal_centavos' => 45000000,
        'impuestos_centavos' => 8550000,
        'total_centavos' => 53550000,
        'moneda' => 'COP',
        'desglose' => [['concepto' => 'Renta', 'monto_centavos' => 45000000]],
    ]);

    $this->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json')),
        'X-Requested-With' => 'XMLHttpRequest',
    ])
        ->get(route('facturas.show', $invoice))
        ->assertOk()
        ->assertJsonPath('component', 'Facturas/Show')
        ->assertJsonPath('props.invoice.id', $invoice->id)
        ->assertJsonPath('props.invoice.reservationId', $invoice->reserva_id)
        ->assertJsonPath('props.invoice.status', FacturaEstado::Pendiente->value)
        ->assertJsonPath('props.invoice.totalCents', 53550000)
        ->assertJsonPath('props.invoice.currency', 'COP')
        ->assertJsonPath('props.invoice.numero', 'FAC-HTML-001')
        ->assertJsonPath('props.invoice.desglose.0.concepto', 'Renta');
});
