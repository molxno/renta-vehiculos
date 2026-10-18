<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MetodoPago;
use App\Enums\PagoEstado;
use Database\Factories\PagoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $reserva_id
 * @property PagoEstado $estado
 * @property MetodoPago $metodo
 * @property int $monto_centavos
 * @property string|null $referencia
 * @property string|null $mensaje
 */
final class Pago extends Model
{
    /** @use HasFactory<PagoFactory> */
    use HasFactory;

    protected $table = 'pagos';

    protected $fillable = [
        'reserva_id', 'estado', 'metodo', 'monto_centavos', 'referencia', 'mensaje',
    ];

    protected function casts(): array
    {
        return [
            'estado' => PagoEstado::class,
            'metodo' => MetodoPago::class,
            'monto_centavos' => 'integer',
        ];
    }

    /** @return BelongsTo<Reserva, $this> */
    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class);
    }
}
