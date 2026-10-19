<?php

declare(strict_types=1);

namespace App\Actions\Entregas;

use App\Exceptions\EntregaFueraDeVentana;
use App\Models\Reserva;
use Carbon\CarbonInterface;

final class EvaluarVentanaDeEntrega
{
    public function handle(Reserva $reserva, ?CarbonInterface $ahora = null): ResultadoVentanaDeEntrega
    {
        $zona = $this->zonaHoraria();
        $formato = $this->formatoFecha();
        $hoy = ($ahora ?? now())->copy()->timezone($zona)->startOfDay();
        $inicio = $reserva->inicio->copy()->timezone($zona)->startOfDay();
        $fin = $reserva->fin->copy()->timezone($zona)->startOfDay();
        $fechaInicio = $inicio->format($formato);
        $fechaFin = $fin->format($formato);

        $despuesDeInicio = (bool) config('entrega.ventana_incluye_inicio')
            ? $hoy->gte($inicio)
            : $hoy->gt($inicio);

        $antesDeFin = (bool) config('entrega.ventana_incluye_fin')
            ? $hoy->lte($fin)
            : $hoy->lt($fin);

        if (! $despuesDeInicio) {
            return ResultadoVentanaDeEntrega::antes($fechaInicio);
        }

        if (! $antesDeFin) {
            return ResultadoVentanaDeEntrega::despues($fechaFin);
        }

        return ResultadoVentanaDeEntrega::dentro();
    }

    public function asegurar(Reserva $reserva, ?CarbonInterface $ahora = null): void
    {
        $resultado = $this->handle($reserva, $ahora);

        if ($resultado->puedeEntregar) {
            return;
        }

        throw EntregaFueraDeVentana::desde($resultado);
    }

    private function zonaHoraria(): string
    {
        return (string) config('entrega.zona_horaria');
    }

    private function formatoFecha(): string
    {
        return (string) config('entrega.formato_fecha');
    }
}
