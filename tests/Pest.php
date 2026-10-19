<?php

use App\Enums\UsuarioRol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function autenticarEmpleado(UsuarioRol $rol = UsuarioRol::Coordinador): User
{
    $empleado = User::factory()->create(['rol' => $rol]);
    test()->actingAs($empleado);

    return $empleado;
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function datosRegistroCliente(array $overrides = []): array
{
    return [
        'name' => 'Ana Cliente',
        'email' => 'ana.nueva@renta.test',
        'tipo_documento' => 'CC',
        'numero_documento' => '1098765432',
        'telefono' => '3001234567',
        'password' => 'password',
        'password_confirmation' => 'password',
        'licencia_numero' => 'LIC-12345678',
        'licencia_categoria' => 'B1',
        'licencia_vencimiento' => now()->addYear()->toDateString(),
        ...$overrides,
    ];
}

function cabecerasInertia(): array
{
    return [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json')),
        'X-Requested-With' => 'XMLHttpRequest',
    ];
}
