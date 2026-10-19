<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Middleware\EnsureUserHasRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use ReflectionMethod;
use ReflectionNamedType;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class AterrizajePorRol
{
    public function urlPara(User $user): string
    {
        return $user->rol->urlAterrizaje();
    }

    public function urlTrasAutenticacion(User $user, Request $request): string
    {
        $intended = $request->session()->pull('url.intended');

        if (is_string($intended) && $intended !== '' && $this->elRolPuedeAcceder($user, $intended)) {
            return $intended;
        }

        return $this->urlPara($user);
    }

    public function elRolPuedeAcceder(User $user, string $url): bool
    {
        if ($url === '' || ! $this->esUrlLocal($url)) {
            return false;
        }

        $request = Request::create($url, 'GET');

        try {
            $route = RouteFacade::getRoutes()->match($request);
        } catch (NotFoundHttpException|MethodNotAllowedHttpException) {
            return false;
        }

        $request->setRouteResolver(static fn (): Route => $route);
        $request->setUserResolver(static fn (): User => $user);

        foreach (array_filter($route->gatherMiddleware(), is_string(...)) as $middleware) {
            if ($middleware === 'guest' || str_starts_with($middleware, 'guest:')) {
                return false;
            }

            $roles = $this->rolesPermitidos($middleware);

            if ($roles !== null && ! in_array($user->rol->value, $roles, true)) {
                return false;
            }
        }

        return $this->formularioAutoriza($user, $route, $request);
    }

    private function esUrlLocal(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return true;
        }

        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        return is_string($appHost) && $host === $appHost;
    }

    /**
     * @return list<string>|null
     */
    private function rolesPermitidos(string $middleware): ?array
    {
        if (str_starts_with($middleware, 'rol:')) {
            return explode(',', substr($middleware, 4));
        }

        $prefijo = EnsureUserHasRole::class.':';

        if (str_starts_with($middleware, $prefijo)) {
            return explode(',', substr($middleware, strlen($prefijo)));
        }

        return null;
    }

    private function formularioAutoriza(User $user, Route $route, Request $request): bool
    {
        $controlador = $route->getControllerClass();
        $metodo = $route->getActionMethod();

        if (! is_string($controlador) || ! method_exists($controlador, $metodo)) {
            return true;
        }

        foreach ((new ReflectionMethod($controlador, $metodo))->getParameters() as $parameter) {
            $type = $parameter->getType();

            if (! $type instanceof ReflectionNamedType || $type->isBuiltin() || ! is_a($type->getName(), FormRequest::class, true)) {
                continue;
            }

            $className = $type->getName();

            /** @var FormRequest $formRequest */
            $formRequest = $className::createFrom($request);
            $formRequest->setContainer(app());
            $formRequest->setUserResolver(static fn (): User => $user);

            $autoriza = new ReflectionMethod($formRequest, 'authorize');

            if ($autoriza->invoke($formRequest) !== true) {
                return false;
            }
        }

        return true;
    }
}
