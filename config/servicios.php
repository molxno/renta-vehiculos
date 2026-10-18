<?php

use App\Contracts\CalculadoraDevolucion;
use App\Contracts\DisponibilidadService;
use App\Contracts\GeneradorQr;
use App\Contracts\PasarelaPago;
use App\Contracts\TarifaService;
use App\Fakes\FakeDisponibilidadService;
use App\Fakes\FakeGeneradorQr;
use App\Services\Adapters\PasarelaPagoAdapter;
use App\Services\CalculadoraDevolucionEstandar;
use App\Services\Devolucion\Strategies\CombustibleCargoStrategy;
use App\Services\Devolucion\Strategies\DanoCargoStrategy;
use App\Services\Devolucion\Strategies\KilometrajeCargoStrategy;
use App\Services\Devolucion\Strategies\RetrasoCargoStrategy;
use App\Services\TarifaServiceEstandar;

return [
    'bindings' => [
        CalculadoraDevolucion::class => CalculadoraDevolucionEstandar::class,
        DisponibilidadService::class => FakeDisponibilidadService::class,
        GeneradorQr::class => FakeGeneradorQr::class,
        PasarelaPago::class => PasarelaPagoAdapter::class,
        TarifaService::class => TarifaServiceEstandar::class,
    ],
    'cargos_devolucion' => [
        RetrasoCargoStrategy::class,
        KilometrajeCargoStrategy::class,
        CombustibleCargoStrategy::class,
        DanoCargoStrategy::class,
    ],
];
