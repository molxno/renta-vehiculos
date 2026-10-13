<?php

namespace App\Models;

use App\Enums\MetodoPago;
use App\Enums\PagoEstado;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
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
