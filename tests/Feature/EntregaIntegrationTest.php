<?php

use App\Enums\MetodoPago;
use App\Enums\PagoEstado;
use App\Enums\ReservaEstado;
use App\Enums\VehiculoEstado;
use App\Http\Requests\StoreActaEntregaRequest;
use App\Models\ActaEntrega;
use App\Models\Contrato;
use App\Models\LicenciaConduccion;
use App\Models\Pago;
use App\Models\Reserva;
use Database\Seeders\AlquilerEntregadoSeeder;
use Database\Seeders\ReservaConfirmadaSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

describe('registro de entrega', function () {
    beforeEach(function (): void {
        autenticarEmpleado();
    });

    it('stores the delivery record, contract and deposit and advances the reservation', function () {
        $this->seed(ReservaConfirmadaSeeder::class);
        $reservation = Reserva::query()->where('qr_codigo', 'QR-RESERVA-CONFIRMADA')->sole();

        $this->post(route('entregas.store', $reservation), [
            'entregado_at' => '2026-02-01 09:00:00',
            'checklist' => [
                'documentos' => true,
                'llantas' => true,
                'kilometraje' => '10000',
                'combustible' => 'lleno',
            ],
            'observaciones' => 'Entrega sin novedades',
            'aceptacion' => '1',
        ])->assertRedirectToRoute('entregas.show', $reservation);

        $record = ActaEntrega::query()->where('reserva_id', $reservation->id)->sole();
        $contract = Contrato::query()->where('reserva_id', $reservation->id)->sole();
        $deposit = Pago::query()->where('reserva_id', $reservation->id)->sole();
        $reservation = $reservation->fresh(['vehiculo']);

        expect($record->observaciones)->toBe('Entrega sin novedades')
            ->and($record->checklist)->toBe([
                'documentos' => true,
                'llantas' => true,
                'kilometraje' => 10000,
                'combustible' => 'lleno',
            ])
            ->and($record->entregado_at->toDateTimeString())->toBe('2026-02-01 09:00:00')
            ->and($record->numero)->toBe('ACT-'.$record->id)
            ->and($record->firmante_nombre)->toBe($reservation->user->name)
            ->and($record->licencia_resultado)->toContain('LIC-DEMO-001')
            ->and($contract->numero)->toBe(sprintf('CTR-%06d', $reservation->id))
            ->and($contract->firmado_at?->toDateTimeString())->toBe('2026-02-01 09:00:00')
            ->and($contract->condiciones)->toBe([
                'combustible' => 'lleno',
                'kilometraje_incluido' => 200,
            ])
            ->and($deposit->estado)->toBe(PagoEstado::Aprobado)
            ->and($deposit->metodo)->toBe(MetodoPago::Efectivo)
            ->and($deposit->monto_centavos)->toBe(13500000)
            ->and($deposit->referencia)->toBe(sprintf('ANT-%06d', $reservation->id))
            ->and($deposit->mensaje)->toBe('Anticipo de entrega')
            ->and($reservation->estado)->toBe(ReservaEstado::Entregada)
            ->and($reservation->vehiculo->estado)->toBe(VehiculoEstado::Alquilado);
    });

    it('rejects delivery when the driving license is missing', function () {
        $reservation = Reserva::factory()->create(['estado' => ReservaEstado::Confirmada]);

        $this->from(route('entregas.create', $reservation))
            ->post(route('entregas.store', $reservation), [
                'entregado_at' => '2026-02-01 09:00:00',
                'aceptacion' => '1',
            ])
            ->assertRedirectToRoute('entregas.create', $reservation)
            ->assertSessionHasErrors(['licencia' => 'La licencia de conducción no está registrada.']);

        expect(ActaEntrega::query()->where('reserva_id', $reservation->id)->exists())->toBeFalse()
            ->and(Contrato::query()->where('reserva_id', $reservation->id)->exists())->toBeFalse()
            ->and(Pago::query()->where('reserva_id', $reservation->id)->exists())->toBeFalse()
            ->and($reservation->fresh()->estado)->toBe(ReservaEstado::Confirmada);
    });

    it('rejects delivery when the driving license is expired', function () {
        $this->travelTo('2026-02-01 09:00:00');

        $reservation = reservaConfirmadaConLicencia(['fecha_vencimiento' => '2026-01-31']);

        $this->from(route('entregas.create', $reservation))
            ->post(route('entregas.store', $reservation), [
                'entregado_at' => '2026-02-01 09:00:00',
                'aceptacion' => '1',
            ])
            ->assertRedirectToRoute('entregas.create', $reservation)
            ->assertSessionHasErrors(['licencia' => 'La licencia de conducción está vencida.']);

        expect(ActaEntrega::query()->where('reserva_id', $reservation->id)->exists())->toBeFalse()
            ->and($reservation->fresh()->estado)->toBe(ReservaEstado::Confirmada);
    });

    it('rejects a second delivery for the same reservation without failing', function () {
        $this->seed(AlquilerEntregadoSeeder::class);
        $reservation = Reserva::query()->where('qr_codigo', 'QR-ALQUILER-ENTREGADO')->sole();

        $this->from(route('entregas.create', $reservation))
            ->post(route('entregas.store', $reservation), [
                'entregado_at' => '2026-02-06 09:00:00',
                'aceptacion' => '1',
            ])
            ->assertRedirectToRoute('entregas.create', $reservation)
            ->assertSessionHasErrors(['reserva' => 'La reserva ya tiene una entrega registrada.']);

        expect(ActaEntrega::query()->where('reserva_id', $reservation->id)->count())->toBe(1)
            ->and($reservation->fresh()->estado)->toBe(ReservaEstado::Entregada);
    });

    it('rejects the delivery of a reservation that is not confirmed', function (ReservaEstado $estado) {
        $reservation = Reserva::factory()->create(['estado' => $estado]);
        LicenciaConduccion::factory()->create(['user_id' => $reservation->user_id]);

        $this->post(route('entregas.store', $reservation), [
            'entregado_at' => '2026-02-01 09:00:00',
            'aceptacion' => '1',
        ])->assertSessionHasErrors(['reserva' => 'Solo se puede registrar la entrega de una reserva confirmada.']);

        expect(ActaEntrega::query()->where('reserva_id', $reservation->id)->exists())->toBeFalse()
            ->and(Pago::query()->where('reserva_id', $reservation->id)->exists())->toBeFalse();
    })->with([
        'cancelada' => [ReservaEstado::Cancelada],
        'entregada' => [ReservaEstado::Entregada],
    ]);

    it('rejects delivery without contract acceptance', function () {
        $reservation = reservaConfirmadaConLicencia();

        $this->post(route('entregas.store', $reservation), [
            'entregado_at' => '2026-02-01 09:00:00',
            'aceptacion' => '0',
        ])->assertSessionHasErrors(['aceptacion' => 'Debe aceptar las condiciones del contrato.']);

        expect(ActaEntrega::query()->where('reserva_id', $reservation->id)->exists())->toBeFalse()
            ->and(Contrato::query()->where('reserva_id', $reservation->id)->exists())->toBeFalse();
    });

    it('validates the delivery checklist', function () {
        $reservation = reservaConfirmadaConLicencia();

        $this->post(route('entregas.store', $reservation), [
            'entregado_at' => '2026-02-01 09:00:00',
            'aceptacion' => '1',
            'checklist' => ['kilometraje' => -1, 'combustible' => 'rebosado'],
        ])->assertSessionHasErrors([
            'checklist.kilometraje',
            'checklist.combustible',
        ]);

        expect(ActaEntrega::query()->where('reserva_id', $reservation->id)->exists())->toBeFalse();
    });

    it('does not persist anything when the input is invalid', function () {
        $reservation = reservaConfirmadaConLicencia();

        $this->post(route('entregas.store', $reservation), [
            'observaciones' => 'Sin fecha',
            'aceptacion' => '1',
        ])->assertSessionHasErrors('entregado_at');

        expect(ActaEntrega::query()->where('reserva_id', $reservation->id)->exists())->toBeFalse()
            ->and($reservation->fresh()->estado)->toBe(ReservaEstado::Confirmada);
    });

    it('shows the license, deposit and blocks an expired license', function () {
        $this->travelTo('2026-02-01 09:00:00');

        $reservation = reservaConfirmadaConLicencia([
            'numero' => 'LIC-VENCIDA',
            'fecha_vencimiento' => '2026-01-31',
            'categoria' => 'B1',
        ]);

        $this->withHeaders(inertiaHeaders())
            ->get(route('entregas.create', $reservation))
            ->assertOk()
            ->assertJsonPath('component', 'Entregas/Create')
            ->assertJsonPath('props.anticipoCents', 13500000)
            ->assertJsonPath('props.currency', 'COP')
            ->assertJsonPath('props.licencia.numero', 'LIC-VENCIDA')
            ->assertJsonPath('props.licencia.vigente', false)
            ->assertJsonPath('props.bloqueo', 'La licencia de conducción está vencida.')
            ->assertJsonPath('props.nivelesCombustible.4.value', 'lleno');
    });

    it('shows a valid license without blocking a confirmed reservation', function () {
        $this->travelTo('2026-02-01 09:00:00');

        $reservation = reservaConfirmadaConLicencia([
            'numero' => 'LIC-VIGENTE',
            'fecha_vencimiento' => '2028-12-31',
        ]);

        $this->withHeaders(inertiaHeaders())
            ->get(route('entregas.create', $reservation))
            ->assertOk()
            ->assertJsonPath('props.licencia.numero', 'LIC-VIGENTE')
            ->assertJsonPath('props.licencia.vigente', true)
            ->assertJsonPath('props.bloqueo', null)
            ->assertJsonPath('props.condicionesContrato', [])
            ->assertJsonPath('props.storeUrl', route('entregas.store', $reservation));
    });

    it('blocks delivery when the driving license is not registered', function () {
        $reservation = Reserva::factory()->create(['estado' => ReservaEstado::Confirmada]);

        $this->withHeaders(inertiaHeaders())
            ->get(route('entregas.create', $reservation))
            ->assertOk()
            ->assertJsonPath('props.licencia', null)
            ->assertJsonPath('props.bloqueo', 'La licencia de conducción no está registrada.');
    });

    it('blocks delivery when the reservation is not confirmed', function () {
        $reservation = Reserva::factory()->create(['estado' => ReservaEstado::Cancelada]);
        LicenciaConduccion::factory()->create(['user_id' => $reservation->user_id]);

        $this->withHeaders(inertiaHeaders())
            ->get(route('entregas.create', $reservation))
            ->assertOk()
            ->assertJsonPath('props.bloqueo', 'Solo se puede registrar la entrega de una reserva confirmada.');
    });

    it('blocks delivery when the reservation already has a delivery record', function () {
        $this->seed(AlquilerEntregadoSeeder::class);
        $reservation = Reserva::query()->where('qr_codigo', 'QR-ALQUILER-ENTREGADO')->sole();

        $this->withHeaders(inertiaHeaders())
            ->get(route('entregas.create', $reservation))
            ->assertOk()
            ->assertJsonPath('props.bloqueo', 'La reserva ya tiene una entrega registrada.')
            ->assertJsonPath('props.condicionesContrato.kilometraje_incluido', 300)
            ->assertJsonPath('props.condicionesContrato.combustible', 'lleno');
    });

    it('returns empty delivery and contract details when the stored JSON is null', function () {
        $reservation = Reserva::factory()->create(['estado' => ReservaEstado::Confirmada]);
        ActaEntrega::factory()->create([
            'reserva_id' => $reservation->id,
            'checklist' => null,
        ]);
        Contrato::factory()->create([
            'reserva_id' => $reservation->id,
            'condiciones' => null,
        ]);

        expect($reservation->checklistEntrega())->toBe([])
            ->and($reservation->condicionesContrato())->toBe([]);
    });

    it('stores an optional photo in the delivery checklist', function () {
        Storage::fake('public');
        $reservation = reservaConfirmadaConLicencia();
        $file = UploadedFile::fake()->create('salida.jpg', 120, 'image/jpeg');

        $this->post(route('entregas.store', $reservation), [
            'entregado_at' => '2026-02-01 09:00:00',
            'aceptacion' => '1',
            'foto' => $file,
        ])->assertRedirectToRoute('entregas.show', $reservation);

        $record = ActaEntrega::query()->where('reserva_id', $reservation->id)->sole();

        expect($record->checklist)->toHaveKey('foto');
        Storage::disk('public')->assertExists($record->checklist['foto']);
    });

    it('skips reservation rules when the route parameter is not a reservation', function () {
        $request = StoreActaEntregaRequest::create('/', 'POST');
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

/**
 * @param  array<string, mixed>  $licencia
 */
function reservaConfirmadaConLicencia(array $licencia = []): Reserva
{
    $reservation = Reserva::factory()->enVentanaDeEntrega()->create([
        'estado' => ReservaEstado::Confirmada,
        'total_centavos' => 45000000,
        'moneda' => 'COP',
    ]);

    LicenciaConduccion::factory()->create([
        'user_id' => $reservation->user_id,
        ...$licencia,
    ]);

    return $reservation;
}

/** @return array<string, string> */
function inertiaHeaders(): array
{
    return [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json')),
        'X-Requested-With' => 'XMLHttpRequest',
    ];
}
