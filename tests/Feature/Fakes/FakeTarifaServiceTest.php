<?php

declare(strict_types=1);

use App\Contracts\TarifaService;
use App\Models\Convenio;
use App\Models\Tarifa;
use App\Models\Trm;
use App\Models\Vehiculo;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

it('calculates COP totals from the vehicle tariff and active agreement', function () {
    $vehiculo = Vehiculo::factory()->create();
    Tarifa::factory()->create([
        'vehiculo_id' => $vehiculo->id,
        'tipo_vehiculo' => $vehiculo->tipo,
        'valor_diario_centavos' => 15000000,
        'vigente_desde' => '2026-01-01',
    ]);
    $convenio = Convenio::factory()->create([
        'descuento_porcentaje' => 10,
        'activo' => true,
    ]);

    $cotizacion = app(TarifaService::class)->cotizar(
        $vehiculo->id,
        Carbon::parse('2026-11-10 09:00:00'),
        Carbon::parse('2026-11-12 09:00:00'),
        $convenio->id,
    );

    expect($cotizacion)->toBe([
        'subtotal_centavos' => 30000000,
        'descuento_centavos' => 3000000,
        'impuestos_centavos' => 5700000,
        'total_centavos' => 32700000,
        'moneda' => 'COP',
    ]);
});

it('converts a quote to USD using the latest persisted TRM that is not in the future', function () {
    $this->travelTo(Carbon::parse('2026-11-01 10:00:00'));
    $vehiculo = Vehiculo::factory()->create();
    Tarifa::factory()->create([
        'vehiculo_id' => $vehiculo->id,
        'tipo_vehiculo' => $vehiculo->tipo,
        'valor_diario_centavos' => 15000000,
        'vigente_desde' => '2026-01-01',
    ]);
    Trm::factory()->create([
        'fecha' => '2026-10-31',
        'valor' => '4200.2500',
    ]);
    Trm::factory()->create([
        'fecha' => '2026-11-02',
        'valor' => '5000.0000',
    ]);

    $cotizacion = app(TarifaService::class)->cotizar(
        $vehiculo->id,
        Carbon::parse('2026-11-10 09:00:00'),
        Carbon::parse('2026-11-12 09:00:00'),
        null,
        'USD',
    );

    expect($cotizacion)->toBe([
        'subtotal_centavos' => 7142,
        'descuento_centavos' => 0,
        'impuestos_centavos' => 1357,
        'total_centavos' => 8499,
        'moneda' => 'USD',
    ]);
});

it('uses a current type tariff when the vehicle has no specific tariff', function () {
    $vehiculo = Vehiculo::factory()->create();
    Tarifa::factory()->create([
        'vehiculo_id' => null,
        'tipo_vehiculo' => $vehiculo->tipo,
        'valor_diario_centavos' => 10000000,
        'vigente_desde' => '2026-01-01',
    ]);

    $cotizacion = app(TarifaService::class)->cotizar(
        $vehiculo->id,
        Carbon::parse('2026-11-10 09:00:00'),
        Carbon::parse('2026-11-11 09:00:00'),
    );

    expect($cotizacion['subtotal_centavos'])->toBe(10000000)
        ->and($cotizacion['total_centavos'])->toBe(11900000);
});

it('prefers the valid vehicle tariff over its vehicle-type tariff', function () {
    $vehiculo = Vehiculo::factory()->create();
    Tarifa::factory()->create([
        'vehiculo_id' => null,
        'tipo_vehiculo' => $vehiculo->tipo,
        'valor_diario_centavos' => 10000000,
        'vigente_desde' => '2026-01-01',
    ]);
    Tarifa::factory()->create([
        'vehiculo_id' => $vehiculo->id,
        'tipo_vehiculo' => $vehiculo->tipo,
        'valor_diario_centavos' => 15000000,
        'vigente_desde' => '2026-01-01',
    ]);

    $cotizacion = app(TarifaService::class)->cotizar(
        $vehiculo->id,
        Carbon::parse('2026-11-10 09:00:00'),
        Carbon::parse('2026-11-11 09:00:00'),
    );

    expect($cotizacion['subtotal_centavos'])->toBe(15000000);
});

it('charges an additional full day for a rental that extends beyond a day', function () {
    $vehiculo = Vehiculo::factory()->create();
    Tarifa::factory()->create([
        'vehiculo_id' => $vehiculo->id,
        'tipo_vehiculo' => $vehiculo->tipo,
        'valor_diario_centavos' => 10000000,
        'vigente_desde' => '2026-01-01',
    ]);

    $cotizacion = app(TarifaService::class)->cotizar(
        $vehiculo->id,
        Carbon::parse('2026-11-10 09:00:00'),
        Carbon::parse('2026-11-11 09:00:01'),
    );

    expect($cotizacion['subtotal_centavos'])->toBe(20000000)
        ->and($cotizacion['total_centavos'])->toBe(23800000);
});

it('rejects a quote when no tariff is valid on the rental start date', function () {
    $vehiculo = Vehiculo::factory()->create();
    Tarifa::factory()->create([
        'vehiculo_id' => $vehiculo->id,
        'tipo_vehiculo' => $vehiculo->tipo,
        'vigente_desde' => '2026-11-11',
    ]);

    expect(fn () => app(TarifaService::class)->cotizar(
        $vehiculo->id,
        Carbon::parse('2026-11-10 09:00:00'),
        Carbon::parse('2026-11-12 09:00:00'),
    ))->toThrow(ValidationException::class);
});

it('rejects a USD quote when there is no persisted TRM in effect today', function () {
    $this->travelTo(Carbon::parse('2026-11-01 10:00:00'));
    $vehiculo = Vehiculo::factory()->create();
    Tarifa::factory()->create([
        'vehiculo_id' => $vehiculo->id,
        'tipo_vehiculo' => $vehiculo->tipo,
        'vigente_desde' => '2026-01-01',
    ]);
    Trm::factory()->create([
        'fecha' => '2026-11-02',
        'valor' => '4200.0000',
    ]);

    expect(fn () => app(TarifaService::class)->cotizar(
        $vehiculo->id,
        Carbon::parse('2026-11-10 09:00:00'),
        Carbon::parse('2026-11-12 09:00:00'),
        null,
        'USD',
    ))->toThrow(ValidationException::class);
});

it('rejects a USD quote when the persisted TRM is zero', function () {
    $this->travelTo(Carbon::parse('2026-11-01 10:00:00'));
    $vehiculo = Vehiculo::factory()->create();
    Tarifa::factory()->create([
        'vehiculo_id' => $vehiculo->id,
        'tipo_vehiculo' => $vehiculo->tipo,
        'vigente_desde' => '2026-01-01',
    ]);
    Trm::factory()->create([
        'fecha' => '2026-11-01',
        'valor' => '0.0000',
    ]);

    expect(fn () => app(TarifaService::class)->cotizar(
        $vehiculo->id,
        Carbon::parse('2026-11-10 09:00:00'),
        Carbon::parse('2026-11-12 09:00:00'),
        null,
        'USD',
    ))->toThrow(ValidationException::class);
});

it('rejects an inactive agreement instead of applying its discount', function () {
    $vehiculo = Vehiculo::factory()->create();
    Tarifa::factory()->create([
        'vehiculo_id' => $vehiculo->id,
        'tipo_vehiculo' => $vehiculo->tipo,
        'vigente_desde' => '2026-01-01',
    ]);
    $convenio = Convenio::factory()->create(['activo' => false]);

    expect(fn () => app(TarifaService::class)->cotizar(
        $vehiculo->id,
        Carbon::parse('2026-11-10 09:00:00'),
        Carbon::parse('2026-11-12 09:00:00'),
        $convenio->id,
    ))->toThrow(ValidationException::class);
});

it('rejects a convention discount above one hundred percent', function () {
    $vehiculo = Vehiculo::factory()->create();
    Tarifa::factory()->create([
        'vehiculo_id' => $vehiculo->id,
        'tipo_vehiculo' => $vehiculo->tipo,
        'vigente_desde' => '2026-01-01',
    ]);
    $convenio = Convenio::factory()->create(['descuento_porcentaje' => 101]);

    expect(fn () => app(TarifaService::class)->cotizar(
        $vehiculo->id,
        Carbon::parse('2026-11-10 09:00:00'),
        Carbon::parse('2026-11-12 09:00:00'),
        $convenio->id,
    ))->toThrow(ValidationException::class);
});

it('rejects an unsupported currency and an invalid rental interval', function (string $currency, string $start, string $end) {
    $vehiculo = Vehiculo::factory()->create();

    expect(fn () => app(TarifaService::class)->cotizar(
        $vehiculo->id,
        Carbon::parse($start),
        Carbon::parse($end),
        null,
        $currency,
    ))->toThrow(ValidationException::class);
})->with([
    'unsupported currency' => ['EUR', '2026-11-10 09:00:00', '2026-11-11 09:00:00'],
    'zero length rental' => ['COP', '2026-11-10 09:00:00', '2026-11-10 09:00:00'],
]);
