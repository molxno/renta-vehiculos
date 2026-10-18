<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\ReservaEstado;
use RuntimeException;

final class TransicionEstadoInvalida extends RuntimeException
{
    public function __construct(
        public readonly string $operacion,
        public readonly ReservaEstado $estado,
        string $mensaje,
    ) {
        parent::__construct($mensaje);
    }

    public static function para(string $operacion, ReservaEstado $estado): self
    {
        $mensaje = match ($operacion) {
            'confirmar' => 'Solo se pueden confirmar reservas pendientes.',
            'entregar' => 'Solo se puede registrar la entrega de una reserva confirmada.',
            'devolver' => 'Solo se puede registrar la devolución de una reserva entregada.',
            'pagar' => 'Solo se puede pagar una reserva facturada.',
            'cancelar' => 'No se puede cancelar una reserva en estado '.$estado->value.'.',
            default => 'No se puede '.$operacion.' una reserva en estado '.$estado->value.'.',
        };

        return new self($operacion, $estado, $mensaje);
    }
}
