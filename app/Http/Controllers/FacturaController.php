<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Factura;
use Inertia\Response;

final class FacturaController extends Controller
{
    public function show(Factura $factura): Response
    {
        return inertia('Facturas/Show', [
            'invoice' => [
                'id' => $factura->id,
                'reservationId' => $factura->reserva_id,
                'status' => $factura->estado->value,
                'totalCents' => $factura->total_centavos,
                'currency' => $factura->moneda,
                'desglose' => $factura->desglose,
                'numero' => $factura->numero,
                'subtotalCents' => $factura->subtotal_centavos,
                'taxCents' => $factura->impuestos_centavos,
            ],
        ]);
    }
}
