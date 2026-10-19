<?php

declare(strict_types=1);

use App\Enums\UsuarioRol;
use App\Models\LicenciaConduccion;
use App\Models\Reserva;
use App\Models\User;
use App\Support\ReglasLicenciaConduccion;
use Database\Seeders\PagoRechazadoSeeder;

describe('registro con licencia', function () {
    it('creates the client license and flashes the created account message', function () {
        $this->from(route('register'))
            ->post(route('register.store'), datosRegistroCliente())
            ->assertRedirect(route('reservas.index'))
            ->assertSessionHas('success', 'Cuenta creada');

        $user = User::query()->where('email', 'ana.nueva@renta.test')->firstOrFail();
        $licencia = $user->licenciaConduccion;

        $this->assertAuthenticatedAs($user);
        expect($licencia)->not->toBeNull()
            ->and($licencia->numero)->toBe('LIC-12345678')
            ->and($licencia->categoria)->toBe('B1')
            ->and($user->necesitaRegistrarLicencia())->toBeFalse();
    });

    it('rejects a registration without driving license fields', function () {
        $this->from(route('register'))
            ->post(route('register.store'), datosRegistroCliente([
                'licencia_numero' => '',
                'licencia_categoria' => '',
                'licencia_vencimiento' => '',
            ]))
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors([
                'licencia_numero',
                'licencia_categoria',
                'licencia_vencimiento',
            ]);

        expect(User::query()->count())->toBe(0);
    });

    it('exposes valid license categories on the registration page', function () {
        $this->withHeaders(cabecerasInertia())
            ->get(route('register'))
            ->assertOk()
            ->assertJsonPath('component', 'Auth/Register')
            ->assertJsonPath('props.categoriasLicencia', ReglasLicenciaConduccion::categorias());
    });
});

describe('aviso de licencia al cliente', function () {
    it('shows the license banner on reservations when the client has none', function () {
        $cliente = User::factory()->cliente()->create();

        $this->actingAs($cliente)
            ->withHeaders(cabecerasInertia())
            ->get(route('reservas.index'))
            ->assertOk()
            ->assertJsonPath('props.avisoLicencia', 'Registra tu licencia para poder recibir tu vehículo')
            ->assertJsonPath('props.cuentaUrl', route('cuenta.show'));
    });

    it('shows the license banner on a reservation when the license is expired', function () {
        $cliente = User::factory()->cliente()->create();
        LicenciaConduccion::factory()->create([
            'user_id' => $cliente->id,
            'fecha_vencimiento' => now()->subDay()->toDateString(),
        ]);
        $reserva = Reserva::factory()->create(['user_id' => $cliente->id]);

        $this->actingAs($cliente)
            ->withHeaders(cabecerasInertia())
            ->get(route('reservas.show', $reserva))
            ->assertOk()
            ->assertJsonPath('props.avisoLicencia', 'Registra tu licencia para poder recibir tu vehículo');
    });

    it('hides the license banner when the client has a valid license', function () {
        $cliente = User::factory()->cliente()->create();
        LicenciaConduccion::factory()->create([
            'user_id' => $cliente->id,
            'fecha_vencimiento' => now()->addYear()->toDateString(),
        ]);

        $this->actingAs($cliente)
            ->withHeaders(cabecerasInertia())
            ->get(route('reservas.index'))
            ->assertOk()
            ->assertJsonPath('props.avisoLicencia', null);
    });

    it('does not show the license banner to guests on a reservation', function () {
        $reserva = Reserva::factory()->create();

        $this->withHeaders(cabecerasInertia())
            ->get(route('reservas.show', $reserva))
            ->assertOk()
            ->assertJsonPath('props.avisoLicencia', null);
    });

    it('does not show the license banner to an employee listing reservations', function () {
        autenticarEmpleado(UsuarioRol::Coordinador);

        $this->withHeaders(cabecerasInertia())
            ->get(route('reservas.index'))
            ->assertOk()
            ->assertJsonPath('props.avisoLicencia', null);
    });
});

describe('navegacion y fechas del cliente', function () {
    it('includes mis reservas and mi cuenta in the public header for clients', function () {
        $header = (string) file_get_contents(resource_path('js/components/storefront/StorefrontHeader.vue'));

        expect($header)->toContain('Mis reservas')
            ->and($header)->toContain('Mi cuenta')
            ->and($header)->toContain("authUser.value?.role === 'cliente'")
            ->and($header)->toContain('Cerrar sesión');
    });

    it('offers ver mis reservas on the confirmation screen', function () {
        $show = (string) file_get_contents(resource_path('js/pages/Reservas/Show.vue'));

        expect($show)->toContain('Ver mis reservas')
            ->and($show)->toContain('Volver al catálogo')
            ->and($show)->toContain('El anticipo se registra al entregar el vehículo y el saldo')
            ->and($show)->toContain('avisoLicencia');
    });

    it('formats delivery and return periods with formatFecha', function () {
        $utils = (string) file_get_contents(resource_path('js/lib/utils.ts'));
        $entrega = (string) file_get_contents(resource_path('js/pages/Entregas/Create.vue'));
        $devolucion = (string) file_get_contents(resource_path('js/pages/Devoluciones/Create.vue'));

        expect($utils)->toContain('export function formatFecha')
            ->and($utils)->toContain('timeZone: APP_TIMEZONE')
            ->and($entrega)->toContain('formatFecha(reservation.inicio)')
            ->and($entrega)->toContain('formatFecha(reservation.fin)')
            ->and($devolucion)->toContain('formatFecha(reservation.inicio)')
            ->and($devolucion)->toContain('formatFecha(reservation.fin)');
    });
});

describe('alerta de rechazo del cajero', function () {
    it('does not mark a previous rejected attempt as the current rejection', function () {
        $this->seed(PagoRechazadoSeeder::class);
        $reservation = Reserva::query()->where('qr_codigo', 'QR-PAGO-RECHAZADO')->sole();

        autenticarEmpleado(UsuarioRol::Cajero);

        $this->withHeaders(cabecerasInertia())
            ->get(route('pagos.create', $reservation))
            ->assertOk()
            ->assertJsonPath('props.rechazoActual', null)
            ->assertJsonPath('props.lastPayment.estado', 'rechazado')
            ->assertJsonPath('props.lastPayment.mensaje', 'Fondos insuficientes')
            ->assertJsonPath('props.lastPayment.referencia', 'PAY-PAGO-RECHAZADO');
    });

    it('does not treat other payment errors as a current card rejection', function () {
        $this->seed(PagoRechazadoSeeder::class);
        $reservation = Reserva::query()->where('qr_codigo', 'QR-PAGO-RECHAZADO')->sole();

        autenticarEmpleado(UsuarioRol::Cajero);

        $this->from(route('pagos.create', $reservation))
            ->post(route('pagos.store', $reservation), [
                'metodo' => 'efectivo',
                'monto_centavos' => 1,
            ])
            ->assertSessionHasErrors('monto_centavos');

        $this->withHeaders(cabecerasInertia())
            ->get(route('pagos.create', $reservation))
            ->assertOk()
            ->assertJsonPath('props.rechazoActual', null);
    });

    it('shows the current rejection after submitting a declined card', function () {
        $this->seed(PagoRechazadoSeeder::class);
        $reservation = Reserva::query()->where('qr_codigo', 'QR-PAGO-RECHAZADO')->sole();
        $invoice = $reservation->factura;

        autenticarEmpleado(UsuarioRol::Cajero);

        $this->from(route('pagos.create', $reservation))
            ->post(route('pagos.store', $reservation), [
                'metodo' => 'tarjeta',
                'monto_centavos' => $invoice->total_centavos,
                'numero_tarjeta' => '4111111111110000',
            ])
            ->assertRedirect(route('pagos.create', $reservation))
            ->assertSessionHasErrors('numero_tarjeta');

        $this->withHeaders(cabecerasInertia())
            ->get(route('pagos.create', $reservation))
            ->assertOk()
            ->assertJsonPath('props.rechazoActual', 'Pago rechazado');
    });

    it('keeps the rejected payment alert tied to the current submission in vue', function () {
        $create = (string) file_get_contents(resource_path('js/pages/Pagos/Create.vue'));

        expect($create)->toContain('mensajeRechazo')
            ->and($create)->toContain('textoRechazo')
            ->and($create)->not->toContain("lastPayment?.estado === 'rechazado'")
            ->and($create)->toContain('Último intento:');
    });
});

describe('reglas de licencia', function () {
    it('returns no categories when the delivery config is invalid', function () {
        config(['entrega.categorias_validas' => null]);

        expect(ReglasLicenciaConduccion::categorias())->toBe([]);
    });

    it('keeps only string categories from the delivery config', function () {
        config(['entrega.categorias_validas' => ['B1', 12, 'C1']]);

        expect(ReglasLicenciaConduccion::categorias())->toBe(['B1', 'C1']);
    });
});
