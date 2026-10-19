<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Actions\Entregas\ResultadoVentanaDeEntrega;
use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

final class EntregaFueraDeVentana extends RuntimeException implements ShouldntReport
{
    public static function desde(ResultadoVentanaDeEntrega $resultado): self
    {
        return new self((string) $resultado->mensajeError);
    }

    public function render(): RedirectResponse
    {
        return to_route('entregas.index')->with('error', $this->getMessage());
    }
}
