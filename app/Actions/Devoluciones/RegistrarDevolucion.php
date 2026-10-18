<?php

declare(strict_types=1);

namespace App\Actions\Devoluciones;

use App\Contracts\CalculadoraDevolucion;
use App\Enums\FacturaEstado;
use App\Enums\ReservaEstado;
use App\Models\ActaDevolucion;
use App\Models\Factura;
use App\Models\Reserva;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final class RegistrarDevolucion
{
    public function __construct(private CalculadoraDevolucion $calculadora) {}

    /**
     * Registra el acta de devolución, los daños y la factura pendiente.
     *
     * @param  array<array-key, mixed>|null  $checklist
     * @param  array<int, array{descripcion: string, monto_centavos: int}>  $danos
     */
    public function handle(
        Reserva $reserva,
        CarbonInterface $devueltoAt,
        ?array $checklist,
        ?string $observaciones,
        array $danos = [],
    ): ActaDevolucion {
        return DB::transaction(function () use ($reserva, $devueltoAt, $checklist, $observaciones, $danos): ActaDevolucion {
            foreach ($danos as $dano) {
                $reserva->danos()->create([
                    'descripcion' => $dano['descripcion'],
                    'monto_centavos' => $dano['monto_centavos'],
                ]);
            }

            $acta = $reserva->actaDevolucion()->create([
                'devuelto_at' => $devueltoAt,
                'checklist' => $checklist,
                'observaciones' => $observaciones,
            ]);

            $cobro = $this->calculadora->calcular(
                $reserva->id,
                $devueltoAt,
                is_array($checklist) ? $checklist : [],
            );

            Factura::query()->create([
                'reserva_id' => $reserva->id,
                'numero' => sprintf('FAC-%06d', $reserva->id),
                'estado' => FacturaEstado::Pendiente,
                'subtotal_centavos' => $cobro['subtotal_centavos'],
                'impuestos_centavos' => $cobro['impuestos_centavos'],
                'total_centavos' => $cobro['total_centavos'],
                'moneda' => $reserva->moneda,
                'desglose' => $cobro['desglose'],
            ]);

            $reserva->update(['estado' => ReservaEstado::Devuelta]);
            $reserva->update(['estado' => ReservaEstado::Facturada]);

            return $acta;
        });
    }
}
