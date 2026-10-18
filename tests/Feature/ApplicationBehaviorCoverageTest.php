<?php

declare(strict_types=1);

use App\Enums\FacturaEstado;
use App\Enums\MetodoPago;
use App\Enums\NivelCombustible;
use App\Enums\PagoEstado;
use App\Enums\ReservaEstado;
use App\Enums\UsuarioRol;
use App\Enums\VehiculoEstado;
use App\Http\Requests\FiltrarCatalogoRequest;
use App\Http\Requests\PrepararReservaRequest;
use App\Http\Requests\StoreActaDevolucionRequest;
use App\Http\Requests\StoreActaEntregaRequest;
use App\Http\Requests\StorePagoRequest;
use App\Http\Requests\StoreReservaRequest;
use App\Models\ActaDevolucion;
use App\Models\ActaEntrega;
use App\Models\Contrato;
use App\Models\Convenio;
use App\Models\Dano;
use App\Models\Factura;
use App\Models\LicenciaConduccion;
use App\Models\Multa;
use App\Models\Pago;
use App\Models\Poliza;
use App\Models\Reserva;
use App\Models\Tarifa;
use App\Models\User;
use App\Models\Vehiculo;
use App\Providers\AppServiceProvider;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Rules\Password;

test('all documented pages expose their Inertia contracts', function () {
    $user = User::factory()->cliente()->create();
    $reservation = Reserva::factory()->create();
    $invoice = Factura::factory()->create();
    $headers = [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json')),
        'X-Requested-With' => 'XMLHttpRequest',
    ];

    $this->actingAs($user)->withHeaders($headers)->get(route('reservas.create'))
        ->assertOk()
        ->assertJsonPath('component', 'Reservas/Create')
        ->assertJsonStructure(['props' => ['vehicles', 'quote']]);

    $this->withHeaders($headers)->get(route('entregas.create', $reservation))
        ->assertOk()
        ->assertJsonPath('component', 'Entregas/Create')
        ->assertJsonPath('props.reservation.id', $reservation->id);

    $this->withHeaders($headers)->get(route('devoluciones.create', $reservation))
        ->assertOk()
        ->assertJsonPath('component', 'Devoluciones/Create')
        ->assertJsonPath('props.estimatedChargesCents', 0);

    $this->withHeaders($headers)->get(route('pagos.create', $reservation))
        ->assertOk()
        ->assertJsonPath('component', 'Pagos/Create')
        ->assertJsonPath('props.currency', 'COP');

    $this->withHeaders($headers)->get(route('facturas.show', $invoice))
        ->assertOk()
        ->assertJsonPath('component', 'Facturas/Show')
        ->assertJsonPath('props.invoice.id', $invoice->id);
});

test('all documented write routes validate and redirect', function () {
    $user = User::factory()->cliente()->create();
    LicenciaConduccion::factory()->create(['user_id' => $user->id]);
    $availableVehicle = Vehiculo::factory()->create();
    Tarifa::factory()->create([
        'vehiculo_id' => $availableVehicle->id,
        'tipo_vehiculo' => $availableVehicle->tipo,
        'vigente_desde' => now()->startOfYear()->toDateString(),
    ]);
    $reservation = Reserva::factory()->create([
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)->post(route('reservas.store'), [
        'vehiculo_id' => $availableVehicle->id,
        'inicio' => now()->addDay()->toDateTimeString(),
        'fin' => now()->addDays(2)->toDateTimeString(),
    ])->assertRedirectToRoute(
        'reservas.show',
        Reserva::query()->where('vehiculo_id', $availableVehicle->id)->sole(),
    );

    $reservaPendiente = Reserva::query()->where('vehiculo_id', $availableVehicle->id)->sole();
    $this->post(route('reservas.confirmar', $reservaPendiente))
        ->assertRedirectToRoute('reservas.show', $reservaPendiente);

    $this->post(route('reservas.cancelar', $reservation))
        ->assertRedirectToRoute('reservas.show', $reservation);

    $this->post(route('entregas.store', $reservation), [
        'entregado_at' => now()->toDateTimeString(),
        'aceptacion' => '1',
    ])->assertRedirectToRoute('reservas.show', $reservation);

    $reservation->update(['estado' => ReservaEstado::Entregada]);

    $this->post(route('devoluciones.store', $reservation), [
        'devuelto_at' => now()->toDateTimeString(),
    ])->assertRedirectToRoute('reservas.show', $reservation);

    $reservation->refresh();
    $reservation->update(['estado' => ReservaEstado::Facturada]);
    $invoice = $reservation->factura ?? Factura::factory()->create([
        'reserva_id' => $reservation->id,
        'total_centavos' => 100,
    ]);

    $this->post(route('pagos.store', $reservation), [
        'metodo' => MetodoPago::Efectivo->value,
        'monto_centavos' => $invoice->total_centavos,
    ])->assertRedirectToRoute('reservas.show', $reservation);
});

test('requests expose authorization and validation contracts', function () {
    $client = User::factory()->cliente()->create();
    $prepararReservaRequest = new PrepararReservaRequest;
    $prepararReservaRequest->setUserResolver(fn (): User => $client);
    $storeReservaRequest = new StoreReservaRequest;
    $storeReservaRequest->setUserResolver(fn (): User => $client);

    $requests = [
        [new StoreActaDevolucionRequest, [
            'devuelto_at',
            'checklist',
            'checklist.kilometraje',
            'checklist.combustible',
            'observaciones',
            'danos',
            'danos.*.descripcion',
            'danos.*.monto_centavos',
            'evidencia',
        ]],
        [new StoreActaEntregaRequest, [
            'entregado_at',
            'checklist',
            'checklist.kilometraje',
            'checklist.combustible',
            'observaciones',
            'aceptacion',
            'foto',
        ]],
        [new StorePagoRequest, ['metodo', 'monto_centavos', 'numero_tarjeta']],
        [$prepararReservaRequest, ['vehiculo_id', 'inicio', 'fin']],
        [$storeReservaRequest, ['vehiculo_id', 'convenio_id', 'inicio', 'fin']],
        [new FiltrarCatalogoRequest, ['category', 'startDate', 'endDate', 'location']],
    ];

    foreach ($requests as [$request, $fields]) {
        expect($request->authorize())->toBeTrue()
            ->and(array_keys($request->rules()))->toEqual($fields);
    }
});

test('models expose their casts and relationships', function () {
    $models = [
        [new ActaDevolucion, ['devuelto_at', 'checklist'], ['reserva' => BelongsTo::class]],
        [new ActaEntrega, ['entregado_at', 'checklist'], ['reserva' => BelongsTo::class]],
        [new Contrato, ['condiciones', 'firmado_at'], ['reserva' => BelongsTo::class]],
        [new Convenio, ['descuento_porcentaje', 'activo'], ['reservas' => HasMany::class]],
        [new Dano, ['monto_centavos'], ['reserva' => BelongsTo::class]],
        [new Factura, ['estado', 'subtotal_centavos', 'impuestos_centavos', 'total_centavos', 'desglose'], ['reserva' => BelongsTo::class]],
        [new LicenciaConduccion, ['fecha_vencimiento'], ['user' => BelongsTo::class]],
        [new Multa, ['monto_centavos'], ['reserva' => BelongsTo::class]],
        [new Pago, ['estado', 'metodo', 'monto_centavos'], ['reserva' => BelongsTo::class]],
        [new Poliza, ['vigencia_desde', 'vigencia_hasta'], ['vehiculo' => BelongsTo::class]],
        [new Reserva, ['estado', 'inicio', 'fin', 'total_centavos'], [
            'user' => BelongsTo::class,
            'vehiculo' => BelongsTo::class,
            'convenio' => BelongsTo::class,
            'contrato' => HasOne::class,
            'actaEntrega' => HasOne::class,
            'actaDevolucion' => HasOne::class,
            'factura' => HasOne::class,
            'pagos' => HasMany::class,
            'multas' => HasMany::class,
            'danos' => HasMany::class,
        ]],
        [new Tarifa, ['valor_diario_centavos', 'vigente_desde', 'vigente_hasta'], ['vehiculo' => BelongsTo::class]],
        [new User, ['email_verified_at', 'password', 'rol'], [
            'licenciaConduccion' => HasOne::class,
            'reservas' => HasMany::class,
        ]],
        [new Vehiculo, ['estado', 'anio'], [
            'polizas' => HasMany::class,
            'reservas' => HasMany::class,
            'tarifas' => HasMany::class,
        ]],
    ];

    foreach ($models as [$model, $casts, $relationships]) {
        expect(array_diff($casts, array_keys($model->getCasts())))->toBeEmpty();

        foreach ($relationships as $method => $relationClass) {
            expect($model->{$method}())->toBeInstanceOf($relationClass);
        }
    }
});

test('enums preserve the domain vocabulary', function () {
    expect(ReservaEstado::cases())->toHaveCount(7)
        ->and(FacturaEstado::cases())->toHaveCount(3)
        ->and(PagoEstado::cases())->toHaveCount(3)
        ->and(UsuarioRol::cases())->toHaveCount(5)
        ->and(VehiculoEstado::cases())->toHaveCount(4)
        ->and(MetodoPago::cases())->toHaveCount(3)
        ->and(NivelCombustible::cases())->toHaveCount(5);
});

test('production provider defaults are configured', function () {
    $environment = app()->environment();
    app()->instance('env', 'production');

    $method = new ReflectionMethod(AppServiceProvider::class, 'configureDefaults');
    $provider = new AppServiceProvider(app());
    $method->invoke($provider);

    expect(Date::now())->toBeInstanceOf(CarbonImmutable::class)
        ->and(Password::defaults())->toBeInstanceOf(Password::class);

    app()->instance('env', $environment);
    $method->invoke($provider);
});
