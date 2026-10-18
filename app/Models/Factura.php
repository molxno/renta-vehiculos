<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FacturaEstado;
use Database\Factories\FacturaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $reserva_id
 * @property string $numero
 * @property FacturaEstado $estado
 * @property int $subtotal_centavos
 * @property int $impuestos_centavos
 * @property int $total_centavos
 * @property string $moneda
 * @property array<int, array{concepto: string, monto_centavos: int}> $desglose
 */
final class Factura extends Model
{
    /** @use HasFactory<FacturaFactory> */
    use HasFactory;

    protected $table = 'facturas';

    protected $fillable = [
        'reserva_id', 'numero', 'estado', 'subtotal_centavos',
        'impuestos_centavos', 'total_centavos', 'moneda', 'desglose',
    ];

    protected function casts(): array
    {
        return [
            'estado' => FacturaEstado::class,
            'subtotal_centavos' => 'integer',
            'impuestos_centavos' => 'integer',
            'total_centavos' => 'integer',
            'desglose' => 'array',
        ];
    }

    /** @return BelongsTo<Reserva, $this> */
    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class);
    }
}
