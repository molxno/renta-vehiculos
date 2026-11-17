<?php

declare(strict_types=1);

namespace App\Services\Pagos;

final readonly class ResultadoPago
{
    public function __construct(
        public bool $aprobado,
        public ?string $referencia,
        public string $mensaje,
        public string $codigo,
    ) {}

    /**
     * @param  array{aprobado: bool, referencia: string|null, mensaje: string, codigo: string}  $resultado
     */
    public static function desdePasarela(array $resultado): self
    {
        return new self(
            $resultado['aprobado'],
            $resultado['referencia'],
            $resultado['mensaje'],
            $resultado['codigo'],
        );
    }
}
