<?php

use App\Contracts\CalculadoraDevolucion;
use App\Contracts\DisponibilidadService;
use App\Contracts\GeneradorQr;
use App\Contracts\PasarelaPago;
use App\Contracts\TarifaService;
use App\Fakes\FakeCalculadoraDevolucion;
use App\Fakes\FakeDisponibilidadService;
use App\Fakes\FakeGeneradorQr;
use App\Fakes\FakePasarelaPago;
use App\Fakes\FakeTarifaService;

return [
    'bindings' => [
        CalculadoraDevolucion::class => FakeCalculadoraDevolucion::class,
        DisponibilidadService::class => FakeDisponibilidadService::class,
        GeneradorQr::class => FakeGeneradorQr::class,
        PasarelaPago::class => FakePasarelaPago::class,
        TarifaService::class => FakeTarifaService::class,
    ],
];
