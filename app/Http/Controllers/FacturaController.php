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
            'invoice' => $factura,
        ]);
    }
}
