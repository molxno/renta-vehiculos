<?php

declare(strict_types=1);

use App\Enums\UsuarioRol;
use App\Http\Middleware\EnsureUserHasRole;
use App\Models\User;
use App\Services\AterrizajePorRol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

it('returns the landing url for each role', function (UsuarioRol $rol, string $ruta) {
    $user = User::factory()->create(['rol' => $rol]);

    expect((new AterrizajePorRol)->urlPara($user))->toBe(route($ruta));
})->with([
    'cliente' => [UsuarioRol::Cliente, 'reservas.index'],
    'coordinador' => [UsuarioRol::Coordinador, 'entregas.index'],
    'cajero' => [UsuarioRol::Cajero, 'pagos.index'],
    'administrador' => [UsuarioRol::Administrador, 'reservas.index'],
    'gerente' => [UsuarioRol::Gerente, 'home'],
]);

it('keeps an intended url the role can open', function () {
    $user = User::factory()->cajero()->create();
    $request = requestConIntended(route('pagos.index'));

    expect((new AterrizajePorRol)->urlTrasAutenticacion($user, $request))
        ->toBe(route('pagos.index'));
});

it('falls back to the landing when the intended url is empty', function () {
    $user = User::factory()->gerente()->create();
    $request = requestConIntended('');

    expect((new AterrizajePorRol)->urlTrasAutenticacion($user, $request))
        ->toBe(route('home'));
});

it('rejects an intended guest page', function () {
    $user = User::factory()->cliente()->create();

    expect((new AterrizajePorRol)->elRolPuedeAcceder($user, route('login')))
        ->toBeFalse();
});

it('rejects an intended url that does not exist', function () {
    $user = User::factory()->cliente()->create();

    expect((new AterrizajePorRol)->elRolPuedeAcceder($user, url('/no-existe')))
        ->toBeFalse();
});

it('rejects an intended url on another host', function () {
    $user = User::factory()->cajero()->create();

    expect((new AterrizajePorRol)->elRolPuedeAcceder($user, 'https://evil.example/pagos'))
        ->toBeFalse();
});

it('rejects an intended post-only url requested as get', function () {
    Route::post('/solo-post', fn (): string => 'ok');
    $user = User::factory()->cliente()->create();

    expect((new AterrizajePorRol)->elRolPuedeAcceder($user, url('/solo-post')))
        ->toBeFalse();
});

it('allows a public closure route without a controller', function () {
    Route::get('/ruta-cierre', fn (): string => 'ok');
    $user = User::factory()->gerente()->create();

    expect((new AterrizajePorRol)->elRolPuedeAcceder($user, url('/ruta-cierre')))
        ->toBeTrue();
});

it('reads role middleware when it is registered as a class name', function () {
    Route::middleware(['web', EnsureUserHasRole::class.':cajero'])
        ->get('/prueba-clase-rol', fn (): string => 'ok');

    $cajero = User::factory()->cajero()->create();
    $coordinador = User::factory()->coordinador()->create();
    $aterrizaje = new AterrizajePorRol;

    expect($aterrizaje->elRolPuedeAcceder($cajero, url('/prueba-clase-rol')))->toBeTrue()
        ->and($aterrizaje->elRolPuedeAcceder($coordinador, url('/prueba-clase-rol')))->toBeFalse();
});

it('rejects a guest guard specific middleware', function () {
    Route::middleware('guest:web')
        ->get('/solo-invitado', fn (): string => 'ok');

    $user = User::factory()->cliente()->create();

    expect((new AterrizajePorRol)->elRolPuedeAcceder($user, url('/solo-invitado')))
        ->toBeFalse();
});

it('allows a relative local path the role can open', function () {
    $user = User::factory()->cajero()->create();

    expect((new AterrizajePorRol)->elRolPuedeAcceder($user, '/pagos'))
        ->toBeTrue();
});

function requestConIntended(string $url): Request
{
    $request = Request::create('/iniciar-sesion', 'POST');
    $request->setLaravelSession(session()->driver());
    $request->session()->put('url.intended', $url);

    return $request;
}
