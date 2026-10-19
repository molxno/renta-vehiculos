<?php

use App\Enums\FacturaEstado;
use App\Enums\ReservaEstado;
use App\Enums\VehiculoEstado;
use App\Http\Requests\StoreActaDevolucionRequest;
use App\Models\ActaDevolucion;
use App\Models\ActaEntrega;
use App\Models\Dano;
use App\Models\Factura;
use App\Models\Multa;
use App\Models\Reserva;
use Database\Seeders\AlquilerEntregadoSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

describe('registro de devolución', function () {
    beforeEach(function (): void {
        autenticarEmpleado();
    });

    it('stores the return record and advances the reservation state', function () {
        $reservation = Reserva::factory()->create([
            'estado' => ReservaEstado::Entregada,
        ]);

        $this->post(route('devoluciones.store', $reservation), [
            'devuelto_at' => '2026-10-16 10:00:00',
            'checklist' => ['documentos' => true, 'llantas' => true],
            'observaciones' => 'Sin novedades',
        ])->assertRedirectToRoute('devoluciones.show', $reservation);

        $record = ActaDevolucion::query()->where('reserva_id', $reservation->id)->sole();

        expect($record->observaciones)->toBe('Sin novedades')
            ->and($record->checklist)->toBe(['documentos' => true, 'llantas' => true])
            ->and($record->devuelto_at->toDateTimeString())->toBe('2026-10-16 10:00:00')
            ->and($reservation->fresh()->estado)->toBe(ReservaEstado::Facturada);
    });

    it('generates the pending invoice from the delivery fixture with every charge', function () {
        $this->seed(AlquilerEntregadoSeeder::class);
        $reservation = Reserva::query()->where('qr_codigo', 'QR-ALQUILER-ENTREGADO')->sole();

        $this->post(route('devoluciones.store', $reservation), [
            'devuelto_at' => '2026-02-09 10:00:00',
            'checklist' => ['kilometraje' => '10450', 'combustible' => 'cuarto'],
            'danos' => [
                ['descripcion' => 'Rayón en puerta', 'monto_centavos' => 5000000],
            ],
        ])->assertRedirectToRoute('devoluciones.show', $reservation);

        $invoice = Factura::query()->where('reserva_id', $reservation->id)->sole();

        expect($invoice->estado)->toBe(FacturaEstado::Pendiente)
            ->and($invoice->numero)->toBe(sprintf('FAC-%06d', $reservation->id))
            ->and($invoice->moneda)->toBe('COP')
            ->and($invoice->desglose)->toBe([
                ['concepto' => 'Renta base', 'monto_centavos' => 66000000],
                ['concepto' => 'Retraso de 2 día(s)', 'monto_centavos' => 66000000],
                ['concepto' => 'Exceso de kilometraje (150 km)', 'monto_centavos' => 22500000],
                ['concepto' => 'Combustible faltante (3 cuarto(s) de tanque)', 'monto_centavos' => 10500000],
                ['concepto' => 'Daño: Rayón en puerta', 'monto_centavos' => 5000000],
            ])
            ->and($invoice->subtotal_centavos)->toBe(170000000)
            ->and($invoice->impuestos_centavos)->toBe(32300000)
            ->and($invoice->total_centavos)->toBe(202300000)
            ->and(Dano::query()->where('reserva_id', $reservation->id)->pluck('descripcion')->all())->toBe(['Rayón en puerta'])
            ->and($reservation->fresh()->actaDevolucion->checklist)->toBe(['kilometraje' => 10450, 'combustible' => 'cuarto'])
            ->and($reservation->fresh()->estado)->toBe(ReservaEstado::Facturada)
            ->and($reservation->fresh()->vehiculo->estado)->toBe(VehiculoEstado::Disponible);
    });

    it('rejects a second return for the same reservation without failing', function () {
        $reservation = Reserva::factory()->create([
            'estado' => ReservaEstado::Devuelta,
        ]);
        ActaDevolucion::factory()->create([
            'reserva_id' => $reservation->id,
            'observaciones' => 'Original',
        ]);

        $this->from(route('devoluciones.create', $reservation))
            ->post(route('devoluciones.store', $reservation), [
                'devuelto_at' => '2026-10-16 11:00:00',
                'observaciones' => 'Duplicada',
            ])
            ->assertRedirectToRoute('devoluciones.create', $reservation)
            ->assertSessionHasErrors(['reserva' => 'La reserva ya tiene una devolución registrada.']);

        expect(ActaDevolucion::query()->where('reserva_id', $reservation->id)->count())->toBe(1)
            ->and(ActaDevolucion::query()->where('reserva_id', $reservation->id)->value('observaciones'))->toBe('Original');
    });

    it('rejects the return of a reservation that was not delivered', function (ReservaEstado $estado) {
        $reservation = Reserva::factory()->create(['estado' => $estado]);

        $this->post(route('devoluciones.store', $reservation), [
            'devuelto_at' => '2026-10-16 11:00:00',
        ])->assertSessionHasErrors(['reserva' => 'Solo se puede registrar la devolución de una reserva entregada.']);

        expect(ActaDevolucion::query()->where('reserva_id', $reservation->id)->exists())->toBeFalse()
            ->and(Factura::query()->where('reserva_id', $reservation->id)->exists())->toBeFalse();
    })->with([
        'confirmada' => [ReservaEstado::Confirmada],
        'cancelada' => [ReservaEstado::Cancelada],
    ]);

    it('rejects a final mileage lower than the delivery mileage', function () {
        $reservation = Reserva::factory()->create(['estado' => ReservaEstado::Entregada]);
        ActaEntrega::factory()->create([
            'reserva_id' => $reservation->id,
            'checklist' => ['kilometraje' => 10000, 'combustible' => 'lleno'],
        ]);

        $this->post(route('devoluciones.store', $reservation), [
            'devuelto_at' => '2026-10-16 11:00:00',
            'checklist' => ['kilometraje' => 9999],
        ])->assertSessionHasErrors([
            'checklist.kilometraje' => 'El kilometraje final no puede ser menor al de la entrega (10000 km).',
        ]);

        expect(ActaDevolucion::query()->where('reserva_id', $reservation->id)->exists())->toBeFalse();
    });

    it('validates the return conditions and damages', function () {
        $reservation = Reserva::factory()->create(['estado' => ReservaEstado::Entregada]);

        $this->post(route('devoluciones.store', $reservation), [
            'devuelto_at' => '2026-10-16 11:00:00',
            'checklist' => ['kilometraje' => -1, 'combustible' => 'rebosado'],
            'danos' => [['descripcion' => '', 'monto_centavos' => -5]],
        ])->assertSessionHasErrors([
            'checklist.kilometraje',
            'checklist.combustible',
            'danos.0.descripcion',
            'danos.0.monto_centavos',
        ]);

        expect(ActaDevolucion::query()->where('reserva_id', $reservation->id)->exists())->toBeFalse();
    });

    it('does not persist anything when the input is invalid', function () {
        $reservation = Reserva::factory()->create([
            'estado' => ReservaEstado::Entregada,
        ]);

        $this->post(route('devoluciones.store', $reservation), [
            'observaciones' => 'Sin fecha',
        ])->assertSessionHasErrors('devuelto_at');

        expect(ActaDevolucion::query()->where('reserva_id', $reservation->id)->exists())->toBeFalse()
            ->and($reservation->fresh()->estado)->toBe(ReservaEstado::Entregada);
    });

    it('shows the delivery comparison, fines and estimated final cost', function () {
        $reservation = Reserva::factory()->create(['estado' => ReservaEstado::Entregada]);
        ActaEntrega::factory()->create([
            'reserva_id' => $reservation->id,
            'checklist' => ['kilometraje' => 10000, 'combustible' => 'lleno'],
        ]);
        Multa::factory()->create([
            'reserva_id' => $reservation->id,
            'concepto' => 'Fotomulta',
            'monto_centavos' => 3000000,
        ]);

        $this->withHeaders([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json')),
            'X-Requested-With' => 'XMLHttpRequest',
        ])->get(route('devoluciones.create', $reservation))
            ->assertOk()
            ->assertJsonPath('component', 'Devoluciones/Create')
            ->assertJsonPath('props.checklist.kilometraje', 10000)
            ->assertJsonPath('props.estimatedChargesCents', 3000000)
            ->assertJsonPath('props.cobro.desglose.1.concepto', 'Multa: Fotomulta')
            ->assertJsonPath('props.nivelesCombustible.0.value', 'vacio')
            ->assertJsonPath('props.nivelesCombustible.0.label', 'Vacío')
            ->assertJsonPath('props.nivelesCombustible.4.label', 'Lleno');
    });

    it('recalculates the return cost from the submitted date and vehicle condition', function () {
        $this->seed(AlquilerEntregadoSeeder::class);
        $reservation = Reserva::query()->where('qr_codigo', 'QR-ALQUILER-ENTREGADO')->sole();

        $this->withHeaders([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json')),
            'X-Requested-With' => 'XMLHttpRequest',
        ])->get(route('devoluciones.create', [
            'reserva' => $reservation,
            'devuelto_at' => '2026-02-09 10:00:00',
            'kilometraje' => '10450',
            'combustible' => 'cuarto',
        ]))
            ->assertOk()
            ->assertJsonPath('props.filters.devuelto_at', '2026-02-09 10:00:00')
            ->assertJsonPath('props.filters.kilometraje', '10450')
            ->assertJsonPath('props.filters.combustible', 'cuarto')
            ->assertJsonPath('props.cobro.desglose.1.concepto', 'Retraso de 2 día(s)')
            ->assertJsonPath('props.cobro.desglose.2.concepto', 'Exceso de kilometraje (150 km)')
            ->assertJsonPath('props.cobro.desglose.3.concepto', 'Combustible faltante (3 cuarto(s) de tanque)')
            ->assertJsonPath('props.cobro.cargos_centavos', 99000000);
    });

    it('falls back to nested checklist query values for the cost preview', function () {
        $reservation = Reserva::factory()->create(['estado' => ReservaEstado::Entregada]);
        ActaEntrega::factory()->create([
            'reserva_id' => $reservation->id,
            'checklist' => ['kilometraje' => 10000, 'combustible' => 'lleno'],
        ]);

        $this->withHeaders([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json')),
            'X-Requested-With' => 'XMLHttpRequest',
        ])->get(route('devoluciones.create', $reservation).'?'.http_build_query([
            'checklist' => ['kilometraje' => '10300', 'combustible' => 'lleno'],
        ]))
            ->assertOk()
            ->assertJsonPath('props.filters.kilometraje', '10300')
            ->assertJsonPath('props.filters.combustible', 'lleno')
            ->assertJsonPath('props.estimatedChargesCents', 0);
    });

    it('reads nested checklist query parameters when calculating the preview', function () {
        $reservation = Reserva::factory()->create(['estado' => ReservaEstado::Entregada]);
        ActaEntrega::factory()->create([
            'reserva_id' => $reservation->id,
            'checklist' => ['kilometraje' => 10000, 'combustible' => 'lleno'],
        ]);

        $this->withHeaders([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json')),
            'X-Requested-With' => 'XMLHttpRequest',
        ])->get(route('devoluciones.create', $reservation).'?'.http_build_query([
            'checklist' => ['kilometraje' => '10300', 'combustible' => 'lleno'],
            'kilometraje' => ['x'],
            'combustible' => ['lleno'],
        ]))
            ->assertOk()
            ->assertJsonPath('props.filters.kilometraje', '')
            ->assertJsonPath('props.filters.combustible', '')
            ->assertJsonPath('props.estimatedChargesCents', 0);
    });

    it('stores an optional evidence photo in the return checklist', function () {
        Storage::fake('public');
        $reservation = Reserva::factory()->create(['estado' => ReservaEstado::Entregada]);
        $file = UploadedFile::fake()->create('regreso.jpg', 120, 'image/jpeg');

        $this->post(route('devoluciones.store', $reservation), [
            'devuelto_at' => '2026-10-16 10:00:00',
            'evidencia' => $file,
        ])->assertRedirectToRoute('devoluciones.show', $reservation);

        $record = ActaDevolucion::query()->where('reserva_id', $reservation->id)->sole();

        expect($record->checklist)->toHaveKey('evidencia');
        Storage::disk('public')->assertExists($record->checklist['evidencia']);
    });

    it('normalizes damage rows and ignores values that are not arrays', function () {
        $request = StoreActaDevolucionRequest::create('/', 'POST', [
            'danos' => ['x', ['descripcion' => 'Rayón', 'monto_centavos' => '5000'], []],
        ]);

        expect($request->danos())->toBe([
            ['descripcion' => 'Rayón', 'monto_centavos' => 5000],
            ['descripcion' => '', 'monto_centavos' => 0],
        ]);
    });

    it('skips reservation rules when the route parameter is not a reservation', function () {
        $request = StoreActaDevolucionRequest::create('/', 'POST');
        $route = new Route('POST', '/', []);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);
        $validator = Validator::make([], []);

        foreach ($request->after() as $callback) {
            $callback($validator);
        }

        expect($validator->errors()->isEmpty())->toBeTrue();
    });
});
