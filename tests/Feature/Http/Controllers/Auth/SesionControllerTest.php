<?php

declare(strict_types=1);

use App\Actions\Auth\IniciarSesion;
use App\Http\Controllers\Auth\SesionController;
use App\Http\Requests\IniciarSesionRequest;
use App\Models\User;
use App\Services\AterrizajePorRol;
use Symfony\Component\HttpKernel\Exception\HttpException;

describe('inicio de sesion del controlador', function () {
    it('forbids completing login when the request has no authenticated user', function () {
        $user = User::factory()->create([
            'email' => 'ana@renta.test',
            'password' => 'password',
        ]);

        $request = IniciarSesionRequest::create('/iniciar-sesion', 'POST', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $request->setContainer(app());
        $request->setRedirector(app('redirect'));
        $request->setLaravelSession(app('session.store'));
        $request->setUserResolver(fn (): mixed => null);

        expect(fn () => app(SesionController::class)->store(
            $request,
            app(IniciarSesion::class),
            app(AterrizajePorRol::class),
        ))->toThrow(HttpException::class);
    });
});
