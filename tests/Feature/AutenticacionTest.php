<?php

use App\Enums\UsuarioRol;
use App\Models\User;
use Database\Seeders\MinimumCatalogSeeder;
use Illuminate\Support\Facades\RateLimiter;

function authInertiaHeaders(): array
{
    return [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json')),
        'X-Requested-With' => 'XMLHttpRequest',
    ];
}

describe('inicio de sesión', function () {
    it('renders the login page', function (string $path) {
        $this->withHeaders(authInertiaHeaders())
            ->get($path)
            ->assertOk()
            ->assertJsonPath('component', 'Auth/Login');
    })->with([
        'ruta en español' => '/iniciar-sesion',
        'alias en inglés' => '/login',
    ]);

    it('authenticates a seeded user by role', function (string $email, UsuarioRol $rol, string $rutaAterrizaje) {
        $this->seed(MinimumCatalogSeeder::class);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => $email,
                'password' => 'password',
            ])
            ->assertRedirect(route($rutaAterrizaje));

        $user = User::query()->where('email', $email)->firstOrFail();

        $this->assertAuthenticatedAs($user);
        expect($user->rol)->toBe($rol);
    })->with([
        'cliente' => ['cliente@renta.test', UsuarioRol::Cliente, 'reservas.index'],
        'coordinador' => ['coordinador@renta.test', UsuarioRol::Coordinador, 'entregas.index'],
        'cajero' => ['cajero@renta.test', UsuarioRol::Cajero, 'pagos.index'],
        'administrador' => ['administrador@renta.test', UsuarioRol::Administrador, 'reservas.index'],
        'gerente' => ['gerente@renta.test', UsuarioRol::Gerente, 'home'],
    ]);

    it('does not return 403 on the landing page of each role', function (UsuarioRol $rol, string $rutaAterrizaje) {
        $user = User::factory()->create(['rol' => $rol]);

        $this->actingAs($user)
            ->get(route($rutaAterrizaje))
            ->assertOk();
    })->with([
        'cliente' => [UsuarioRol::Cliente, 'reservas.index'],
        'coordinador' => [UsuarioRol::Coordinador, 'entregas.index'],
        'cajero' => [UsuarioRol::Cajero, 'pagos.index'],
        'administrador' => [UsuarioRol::Administrador, 'reservas.index'],
        'gerente' => [UsuarioRol::Gerente, 'home'],
    ]);

    it('honors an intended url the role can access', function () {
        $user = User::factory()->cajero()->create();

        $this->get(route('pagos.index'))->assertRedirect(route('login'));

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('pagos.index'));
    });

    it('falls back to the role landing when the intended url is forbidden', function () {
        $user = User::factory()->cajero()->create();

        $this->get(route('entregas.index'))->assertRedirect(route('login'));

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('pagos.index'));
    });

    it('does not send a coordinator to the client reservation form after login', function () {
        $user = User::factory()->coordinador()->create();

        $this->get(route('reservas.create'))->assertRedirect(route('login'));

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('entregas.index'));
    });

    it('rejects invalid credentials without authenticating', function () {
        $user = User::factory()->create([
            'email' => 'ana@renta.test',
            'password' => 'password',
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'clave-incorrecta',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'email' => 'Las credenciales no coinciden con nuestros registros.',
            ]);

        $this->assertGuest();
    });

    it('rejects a login without the required fields', function () {
        $this->from(route('login'))
            ->post(route('login.store'), [])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email', 'password']);

        $this->assertGuest();
    });

    it('locks further attempts after five failed logins', function () {
        $user = User::factory()->create([
            'email' => 'bloqueo@renta.test',
            'password' => 'password',
        ]);

        RateLimiter::clear(mb_strtolower($user->email).'|127.0.0.1');

        foreach (range(1, 5) as $ignored) {
            $this->from(route('login'))
                ->post(route('login.store'), [
                    'email' => $user->email,
                    'password' => 'clave-incorrecta',
                ]);
        }

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    });

    it('shares the authenticated role on the home page', function () {
        $user = User::factory()->coordinador()->create();

        $this->actingAs($user)
            ->withHeaders(authInertiaHeaders())
            ->get(route('home'))
            ->assertOk()
            ->assertJsonPath('props.auth.user.id', $user->id)
            ->assertJsonPath('props.auth.user.name', $user->name)
            ->assertJsonPath('props.auth.user.role', UsuarioRol::Coordinador->value);
    });
});

describe('cierre de sesión', function () {
    it('logs out an authenticated user', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
    });

    it('redirects guests who try to log out', function () {
        $this->post(route('logout'))->assertRedirect(route('login'));
    });
});

describe('registro mínimo', function () {
    it('renders the registration page', function (string $path) {
        $this->withHeaders(authInertiaHeaders())
            ->get($path)
            ->assertOk()
            ->assertJsonPath('component', 'Auth/Register');
    })->with([
        'ruta en español' => '/registro',
        'alias en inglés' => '/register',
    ]);

    it('creates a client and starts the session', function () {
        $this->from(route('register'))
            ->post(route('register.store'), datosRegistroCliente([
                'rol' => UsuarioRol::Administrador->value,
            ]))
            ->assertRedirect(route('reservas.index'))
            ->assertSessionHas('success', 'Cuenta creada');

        $user = User::query()->where('email', 'ana.nueva@renta.test')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        expect($user->name)->toBe('Ana Cliente')
            ->and($user->tipo_documento)->toBe('CC')
            ->and($user->numero_documento)->toBe('1098765432')
            ->and($user->telefono)->toBe('3001234567')
            ->and($user->rol)->toBe(UsuarioRol::Cliente)
            ->and($user->licenciaConduccion?->numero)->toBe('LIC-12345678')
            ->and($user->licenciaConduccion?->categoria)->toBe('B1');
    });

    it('rejects a registration without the required fields', function () {
        $this->from(route('register'))
            ->post(route('register.store'), [])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors([
                'name',
                'email',
                'tipo_documento',
                'numero_documento',
                'telefono',
                'password',
                'licencia_numero',
                'licencia_categoria',
                'licencia_vencimiento',
            ]);

        expect(User::query()->count())->toBe(0);
    });

    it('rejects a duplicate email', function () {
        User::factory()->create(['email' => 'existente@renta.test']);

        $this->from(route('register'))
            ->post(route('register.store'), datosRegistroCliente([
                'name' => 'Otra Persona',
                'email' => 'existente@renta.test',
                'numero_documento' => '1987654321',
                'telefono' => '3007654321',
            ]))
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('email');

        expect(User::query()->where('email', 'existente@renta.test')->count())->toBe(1);
        $this->assertGuest();
    });

    it('redirects an authenticated user away from guest pages', function (string $path, UsuarioRol $rol, string $rutaAterrizaje) {
        $user = User::factory()->create(['rol' => $rol]);

        $this->actingAs($user)
            ->get($path)
            ->assertRedirect(route($rutaAterrizaje));
    })->with([
        'cliente en iniciar sesión' => ['/iniciar-sesion', UsuarioRol::Cliente, 'reservas.index'],
        'coordinador en alias login' => ['/login', UsuarioRol::Coordinador, 'entregas.index'],
        'cajero en registro' => ['/registro', UsuarioRol::Cajero, 'pagos.index'],
        'administrador en alias register' => ['/register', UsuarioRol::Administrador, 'reservas.index'],
        'gerente en iniciar sesión' => ['/iniciar-sesion', UsuarioRol::Gerente, 'home'],
    ]);
});
