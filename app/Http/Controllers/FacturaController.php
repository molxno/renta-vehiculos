<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use Inertia\Response;

class FacturaController extends Controller
{
    public function show(Factura $factura): Response
    {
        return inertia('Facturas/Show', [
            'invoice' => $factura,
        ]);
    }
}
