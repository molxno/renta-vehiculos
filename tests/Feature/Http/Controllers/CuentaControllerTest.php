<?php

declare(strict_types=1);

use App\Actions\Cuenta\GuardarLicenciaCliente;
use App\Enums\UsuarioRol;
use App\Http\Controllers\CuentaController;
use App\Http\Requests\ActualizarLicenciaRequest;
use App\Models\LicenciaConduccion;
use App\Models\User;
use App\Support\ReglasLicenciaConduccion;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

describe('cuenta del cliente', function () {
    it('redirects guests from the account page to login', function () {
        $this->get(route('cuenta.show'))->assertRedirectToRoute('login');
        $this->put(route('cuenta.update'), [])->assertRedirectToRoute('login');
    });

    it('rejects updating the license when there is no authenticated user', function () {
        expect((new ActualizarLicenciaRequest)->authorize())->toBeFalse();
    });

    it('forbids showing the account when the request has no authenticated user', function () {
        expect(fn () => app(CuentaController::class)->show(Request::create('/cuenta')))
            ->toThrow(HttpException::class);
    });

    it('forbids updating the license when the request has no authenticated user', function () {
        $request = ActualizarLicenciaRequest::create('/cuenta/licencia', 'PUT', [
            'licencia_numero' => 'LIC-X',
            'licencia_categoria' => 'B1',
            'licencia_vencimiento' => now()->addYear()->toDateString(),
        ]);
        $request->setContainer(app());
        $request->setRedirector(app('redirect'));

        expect(fn () => app(CuentaController::class)->update($request, app(GuardarLicenciaCliente::class)))
            ->toThrow(HttpException::class);
    });

    it('shows the profile as read-only and an empty license form', function () {
        $user = User::factory()->cliente()->create([
            'name' => 'Ana Cliente',
            'email' => 'ana.cuenta@renta.test',
            'tipo_documento' => 'CC',
            'numero_documento' => '1098000111',
            'telefono' => '3009998888',
        ]);

        $this->actingAs($user)
            ->withHeaders(cabecerasInertia())
            ->get(route('cuenta.show'))
            ->assertOk()
            ->assertJsonPath('component', 'Cuenta/Show')
            ->assertJsonPath('props.perfil.name', 'Ana Cliente')
            ->assertJsonPath('props.perfil.email', 'ana.cuenta@renta.test')
            ->assertJsonPath('props.perfil.numero_documento', '1098000111')
            ->assertJsonPath('props.licencia', null)
            ->assertJsonPath('props.categoriasLicencia', ReglasLicenciaConduccion::categorias())
            ->assertJsonPath('props.updateUrl', route('cuenta.update'));
    });

    it('shows the stored license when the client already has one', function () {
        $user = User::factory()->cliente()->create();
        $vencimiento = now()->addYears(2)->toDateString();
        LicenciaConduccion::factory()->create([
            'user_id' => $user->id,
            'numero' => 'LIC-VIGENTE-1',
            'categoria' => 'B1',
            'fecha_vencimiento' => $vencimiento,
        ]);

        $this->actingAs($user)
            ->withHeaders(cabecerasInertia())
            ->get(route('cuenta.show'))
            ->assertOk()
            ->assertJsonPath('component', 'Cuenta/Show')
            ->assertJsonPath('props.licencia.numero', 'LIC-VIGENTE-1')
            ->assertJsonPath('props.licencia.categoria', 'B1')
            ->assertJsonPath('props.licencia.fecha_vencimiento', $vencimiento)
            ->assertJsonPath('props.licencia.vigente', true);
    });

    it('registers a license for the authenticated user', function () {
        $user = User::factory()->cliente()->create();
        $vencimiento = now()->addYears(2)->toDateString();

        $this->actingAs($user)
            ->from(route('cuenta.show'))
            ->put(route('cuenta.update'), [
                'licencia_numero' => 'LIC-CUENTA-1',
                'licencia_categoria' => 'B1',
                'licencia_vencimiento' => $vencimiento,
            ])
            ->assertRedirectToRoute('cuenta.show')
            ->assertSessionHas('success', 'Licencia actualizada');

        $licencia = $user->fresh()->licenciaConduccion;

        expect($licencia)->not->toBeNull()
            ->and($licencia->numero)->toBe('LIC-CUENTA-1')
            ->and($licencia->categoria)->toBe('B1')
            ->and($licencia->fecha_vencimiento->toDateString())->toBe($vencimiento);
    });

    it('updates an existing license', function () {
        $user = User::factory()->cliente()->create();
        LicenciaConduccion::factory()->create([
            'user_id' => $user->id,
            'numero' => 'LIC-VIEJA',
            'categoria' => 'A1',
            'fecha_vencimiento' => now()->addYear()->toDateString(),
        ]);
        $vencimiento = now()->addYears(4)->toDateString();

        $this->actingAs($user)
            ->put(route('cuenta.update'), [
                'licencia_numero' => 'LIC-NUEVA',
                'licencia_categoria' => 'C1',
                'licencia_vencimiento' => $vencimiento,
            ])
            ->assertRedirectToRoute('cuenta.show');

        expect(LicenciaConduccion::query()->where('user_id', $user->id)->count())->toBe(1)
            ->and($user->fresh()->licenciaConduccion->numero)->toBe('LIC-NUEVA')
            ->and($user->fresh()->licenciaConduccion->categoria)->toBe('C1');
    });

    it('rejects an expired or invalid license with spanish messages', function () {
        $user = User::factory()->cliente()->create();

        $this->actingAs($user)
            ->from(route('cuenta.show'))
            ->put(route('cuenta.update'), [
                'licencia_numero' => '',
                'licencia_categoria' => 'ZZ',
                'licencia_vencimiento' => now()->toDateString(),
            ])
            ->assertRedirect(route('cuenta.show'))
            ->assertSessionHasErrors([
                'licencia_numero' => 'Indica el número de la licencia de conducción.',
                'licencia_categoria' => 'La categoría de la licencia no es válida.',
                'licencia_vencimiento' => 'La fecha de vencimiento de la licencia debe ser futura.',
            ]);

        expect($user->fresh()->licenciaConduccion)->toBeNull();
    });

    it('allows an employee to open the account page', function () {
        autenticarEmpleado(UsuarioRol::Cajero);

        $this->withHeaders(cabecerasInertia())
            ->get(route('cuenta.show'))
            ->assertOk()
            ->assertJsonPath('component', 'Cuenta/Show');
    });
});
