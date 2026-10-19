<?php

declare(strict_types=1);

namespace App\Actions\Entregas;

final readonly class ResultadoVentanaDeEntrega
{
    public function __construct(
        public bool $puedeEntregar,
        public ?string $motivoListado,
        public ?string $mensajeError,
    ) {}

    public static function dentro(): self
    {
        return new self(true, null, null);
    }

    public static function antes(string $fechaInicio): self
    {
        return new self(
            false,
            'Disponible desde '.$fechaInicio,
            'La reserva inicia el '.$fechaInicio.'; aún no se puede entregar',
        );
    }

    public static function despues(string $fechaFin): self
    {
        return new self(
            false,
            'Vencida',
            'La reserva venció el '.$fechaFin.'; no se puede entregar',
        );
    }
}
