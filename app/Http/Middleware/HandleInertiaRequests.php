<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UsuarioRol;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user instanceof User
                    ? [
                        'id' => $user->id,
                        'name' => $user->name,
                        'role' => $user->rol->value,
                    ]
                    : null,
            ],
            'navigation' => $this->navigationFor($user),
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ];
    }

    /**
     * @return list<array{label: string, href: string}>
     */
    private function navigationFor(?User $user): array
    {
        if (! $user instanceof User) {
            return [];
        }

        return match ($user->rol) {
            UsuarioRol::Cliente => [
                ['label' => 'Mis reservas', 'href' => route('reservas.index')],
            ],
            UsuarioRol::Coordinador => [
                ['label' => 'Reservas', 'href' => route('reservas.index')],
                ['label' => 'Entregas', 'href' => route('entregas.index')],
                ['label' => 'Devoluciones', 'href' => route('devoluciones.index')],
            ],
            UsuarioRol::Cajero => [
                ['label' => 'Pagos', 'href' => route('pagos.index')],
            ],
            UsuarioRol::Administrador => [
                ['label' => 'Tasa de cambio', 'href' => route('admin.trm.edit')],
                ['label' => 'Entregas', 'href' => route('entregas.index')],
                ['label' => 'Devoluciones', 'href' => route('devoluciones.index')],
                ['label' => 'Pagos', 'href' => route('pagos.index')],
            ],
            UsuarioRol::Gerente => [],
        };
    }
}
