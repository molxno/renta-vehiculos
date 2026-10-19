<?php

declare(strict_types=1);

use App\Enums\FacturaEstado;
use App\Enums\PagoEstado;
use App\Enums\ReservaEstado;
use App\Enums\UsuarioRol;
use App\Http\Requests\VerActaEntregaRequest;
use App\Models\ActaEntrega;
use App\Models\Factura;
use App\Models\LicenciaConduccion;
use App\Models\Pago;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Routing\Route;

function cabecerasInertiaActa(): array
{
    return [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json')),
        'X-Requested-With' => 'XMLHttpRequest',
    ];
}

function reservaConActaEntrega(): Reserva
{
    $reservation = Reserva::factory()->enVentanaDeEntrega()->create([
        'estado' => ReservaEstado::Entregada,
    ]);
    LicenciaConduccion::factory()->create([
        'user_id' => $reservation->user_id,
        'numero' => 'LIC-ACTA',
        'fecha_vencimiento' => '2028-12-31',
        'categoria' => 'B1',
    ]);
    $coordinador = User::factory()->coordinador()->create(['name' => 'Coordinador Acta']);
    ActaEntrega::factory()->create([
        'reserva_id' => $reservation->id,
        'numero' => null,
        'coordinador_id' => $coordinador->id,
        'entregado_at' => '2026-10-19 09:00:00',
        'firmante_nombre' => $reservation->user->name,
        'firmado_at' => '2026-10-19 09:00:00',
        'licencia_resultado' => 'Licencia LIC-ACTA vigente, categoría B1 válida',
        'checklist' => ['kilometraje' => 12500, 'combustible' => 'lleno'],
    ]);

    return $reservation->fresh(['actaEntrega', 'user']);
}

it('redirects guests from the delivery certificate to login', function () {
    $reservation = reservaConActaEntrega();

    $this->get(route('entregas.acta', $reservation))->assertRedirectToRoute('login');
});

it('allows a coordinator, an administrator and the owner to view the certificate', function (UsuarioRol $rol, bool $esDueno) {
    $reservation = reservaConActaEntrega();
    $usuario = $esDueno
        ? $reservation->user
        : User::factory()->create(['rol' => $rol]);

    $this->actingAs($usuario)
        ->withHeaders(cabecerasInertiaActa())
        ->get(route('entregas.acta', $reservation))
        ->assertOk()
        ->assertJsonPath('component', 'Entregas/Acta')
        ->assertJsonPath('props.acta.numero', 'ACT-'.$reservation->actaEntrega->id)
        ->assertJsonPath('props.aviso', 'Documento generado por RENT-GO (demo)')
        ->assertJsonPath('props.vehiculo.placa', $reservation->vehiculo->placa)
        ->assertJsonPath('props.coordinador', 'Coordinador Acta')
        ->assertJsonPath('props.acta.combustible', 'Lleno');
})->with([
    'coordinador' => [UsuarioRol::Coordinador, false],
    'administrador' => [UsuarioRol::Administrador, false],
    'cliente dueño' => [UsuarioRol::Cliente, true],
]);

it('forbids a cashier, a manager and another client from viewing the certificate', function (UsuarioRol $rol) {
    $reservation = reservaConActaEntrega();

    $this->actingAs(User::factory()->create(['rol' => $rol]))
        ->get(route('entregas.acta', $reservation))
        ->assertForbidden();
})->with([
    'cajero' => UsuarioRol::Cajero,
    'gerente' => UsuarioRol::Gerente,
    'otro cliente' => UsuarioRol::Cliente,
]);

it('returns 404 when the reservation has no delivery certificate', function () {
    $reservation = Reserva::factory()->create(['estado' => ReservaEstado::Entregada]);

    autenticarEmpleado();

    $this->get(route('entregas.acta', $reservation))->assertNotFound();
});

it('falls back to ACT-id when the stored certificate number is empty', function () {
    $reservation = Reserva::factory()->create(['estado' => ReservaEstado::Entregada]);
    $acta = ActaEntrega::factory()->create([
        'reserva_id' => $reservation->id,
        'numero' => '',
        'checklist' => null,
    ]);

    autenticarEmpleado();

    $this->withHeaders(cabecerasInertiaActa())
        ->get(route('entregas.acta', $reservation))
        ->assertOk()
        ->assertJsonPath('props.acta.numero', 'ACT-'.$acta->id);
});

it('renders the certificate when the checklist is not an array', function () {
    $reservation = Reserva::factory()->create(['estado' => ReservaEstado::Entregada]);
    ActaEntrega::factory()->create([
        'reserva_id' => $reservation->id,
        'numero' => 'ACT-VACIA',
        'checklist' => null,
    ]);

    autenticarEmpleado();

    $this->withHeaders(cabecerasInertiaActa())
        ->get(route('entregas.acta', $reservation))
        ->assertOk()
        ->assertJsonPath('props.acta.numero', 'ACT-VACIA')
        ->assertJsonPath('props.acta.kilometraje', null)
        ->assertJsonPath('props.acta.combustible', null);
});

it('renders the certificate with a non-enum fuel level and without a license', function () {
    $reservation = Reserva::factory()->create(['estado' => ReservaEstado::Entregada]);
    ActaEntrega::factory()->create([
        'reserva_id' => $reservation->id,
        'numero' => 'ACT-MANUAL',
        'checklist' => ['kilometraje' => 'no-aplica', 'combustible' => 'otro'],
        'coordinador_id' => null,
        'firmante_nombre' => null,
        'licencia_resultado' => null,
        'entregado_at' => '2026-10-19 09:00:00',
    ]);

    autenticarEmpleado();

    $this->withHeaders(cabecerasInertiaActa())
        ->get(route('entregas.acta', $reservation))
        ->assertOk()
        ->assertJsonPath('props.acta.numero', 'ACT-MANUAL')
        ->assertJsonPath('props.acta.kilometraje', null)
        ->assertJsonPath('props.acta.combustible', 'otro')
        ->assertJsonPath('props.licencia', null)
        ->assertJsonPath('props.coordinador', null);
});

it('denies authorization when the route has no reservation', function () {
    $sinUsuario = VerActaEntregaRequest::create('/acta', 'GET');
    expect($sinUsuario->authorize())->toBeFalse();

    $request = VerActaEntregaRequest::create('/acta', 'GET');
    $request->setUserResolver(fn (): User => User::factory()->coordinador()->create());
    $route = new Route('GET', '/', []);
    $route->bind($request);
    $request->setRouteResolver(fn () => $route);

    expect($request->authorize())->toBeFalse()
        ->and($request->rules())->toBe([]);
});

it('shows the certificate link and number after a successful delivery', function () {
    $reservation = Reserva::factory()->enVentanaDeEntrega()->create(['estado' => ReservaEstado::Confirmada]);
    LicenciaConduccion::factory()->create([
        'user_id' => $reservation->user_id,
        'fecha_vencimiento' => now()->addYear()->toDateString(),
        'categoria' => 'B1',
    ]);

    autenticarEmpleado();

    $this->post(route('entregas.store', $reservation), [
        'entregado_at' => now()->toDateTimeString(),
        'checklist' => ['kilometraje' => '1000', 'combustible' => 'lleno'],
        'aceptacion' => '1',
    ])->assertRedirectToRoute('entregas.show', $reservation);

    $acta = $reservation->fresh()->actaEntrega;

    $this->withHeaders(cabecerasInertiaActa())
        ->get(route('entregas.show', $reservation))
        ->assertOk()
        ->assertJsonPath('props.acta.numero', 'ACT-'.$acta->id)
        ->assertJsonPath('props.acta.actaUrl', route('entregas.acta', $reservation));
});

it('exposes the reservation timeline and certificate link to the client', function () {
    $reservation = reservaConActaEntrega();
    Factura::factory()->create([
        'reserva_id' => $reservation->id,
        'estado' => FacturaEstado::Pagada,
    ]);
    Pago::factory()->create([
        'reserva_id' => $reservation->id,
        'estado' => PagoEstado::Aprobado,
    ]);
    $reservation->update(['estado' => ReservaEstado::Pagada]);

    $this->actingAs($reservation->user)
        ->withHeaders(cabecerasInertiaActa())
        ->get(route('reservas.show', $reservation))
        ->assertOk()
        ->assertJsonPath('props.actaUrl', route('entregas.acta', $reservation))
        ->assertJsonPath('props.timeline.0.done', true)
        ->assertJsonPath('props.timeline.1.done', true)
        ->assertJsonPath('props.timeline.2.done', true)
        ->assertJsonPath('props.timeline.3.done', true);
});

it('marks pending timeline steps for a pending reservation', function () {
    $reservation = Reserva::factory()->pendiente()->create();

    $this->withHeaders(cabecerasInertiaActa())
        ->get(route('reservas.show', $reservation))
        ->assertOk()
        ->assertJsonPath('props.actaUrl', null)
        ->assertJsonPath('props.timeline.0.done', false)
        ->assertJsonPath('props.timeline.1.done', false)
        ->assertJsonPath('props.timeline.2.done', false)
        ->assertJsonPath('props.timeline.3.done', false)
        ->assertJsonPath('props.timeline.3.at', null);
});

it('marks delivered, billed and paid steps from the reservation status alone', function () {
    $devuelta = Reserva::factory()->create(['estado' => ReservaEstado::Devuelta]);
    $facturada = Reserva::factory()->create(['estado' => ReservaEstado::Facturada]);
    $pagada = Reserva::factory()->create(['estado' => ReservaEstado::Pagada]);

    $this->withHeaders(cabecerasInertiaActa())
        ->get(route('reservas.show', $devuelta))
        ->assertJsonPath('props.timeline.1.done', true)
        ->assertJsonPath('props.timeline.2.done', false);

    $this->withHeaders(cabecerasInertiaActa())
        ->get(route('reservas.show', $facturada))
        ->assertJsonPath('props.timeline.2.done', true)
        ->assertJsonPath('props.timeline.3.done', false)
        ->assertJsonPath('props.timeline.3.at', null);

    $this->withHeaders(cabecerasInertiaActa())
        ->get(route('reservas.show', $pagada))
        ->assertJsonPath('props.timeline.3.done', true)
        ->assertJsonPath('props.timeline.3.at', null);
});

it('uses the invoice timestamp when the reservation is paid without an approved payment', function () {
    $reservation = Reserva::factory()->create(['estado' => ReservaEstado::Pagada]);
    Factura::factory()->create([
        'reserva_id' => $reservation->id,
        'estado' => FacturaEstado::Pagada,
    ]);

    $this->withHeaders(cabecerasInertiaActa())
        ->get(route('reservas.show', $reservation))
        ->assertOk()
        ->assertJsonPath('props.timeline.3.done', true);

    expect($this->withHeaders(cabecerasInertiaActa())
        ->get(route('reservas.show', $reservation))
        ->json('props.timeline.3.at'))->not->toBeNull();
});
