<?php

declare(strict_types=1);

namespace App\Enums;

enum UsuarioRol: string
{
    case Cliente = 'cliente';
    case Coordinador = 'coordinador';
    case Cajero = 'cajero';
    case Administrador = 'administrador';
    case Gerente = 'gerente';

    public function rutaAterrizaje(): string
    {
        return match ($this) {
            self::Cliente, self::Gerente => 'home',
            self::Administrador => 'reservas.index',
            self::Coordinador => 'entregas.index',
            self::Cajero => 'pagos.index',
        };
    }

    public function urlAterrizaje(): string
    {
        return route($this->rutaAterrizaje());
    }

    /**
     * @return list<array{label: string, href: string}>
     */
    public static function enlacesPublicos(): array
    {
        return [
            ['label' => 'Inicio', 'href' => route('home')],
            ['label' => 'Catálogo', 'href' => route('catalogo')],
            ['label' => 'Cómo funciona', 'href' => route('home').'#como-funciona'],
            ['label' => 'Contacto', 'href' => route('home').'#contacto'],
        ];
    }

    /**
     * @return list<array{label: string, href: string}>
     */
    public function enlacesNavegacion(): array
    {
        return [
            ...self::enlacesPublicos(),
            ...$this->enlacesDeRol(),
        ];
    }

    /**
     * @return list<array{label: string, href: string}>
     */
    private function enlacesDeRol(): array
    {
        return match ($this) {
            self::Cliente => [
                ['label' => 'Mis reservas', 'href' => route('reservas.index')],
                ['label' => 'Mi cuenta', 'href' => route('cuenta.show')],
            ],
            self::Coordinador => [
                ['label' => 'Reservas', 'href' => route('reservas.index')],
                ['label' => 'Entregas', 'href' => route('entregas.index')],
                ['label' => 'Devoluciones', 'href' => route('devoluciones.index')],
            ],
            self::Cajero => [
                ['label' => 'Pagos', 'href' => route('pagos.index')],
            ],
            self::Administrador => [
                ['label' => 'Tasa de cambio', 'href' => route('admin.trm.edit')],
                ['label' => 'Entregas', 'href' => route('entregas.index')],
                ['label' => 'Devoluciones', 'href' => route('devoluciones.index')],
                ['label' => 'Pagos', 'href' => route('pagos.index')],
            ],
            self::Gerente => [],
        };
    }
}
