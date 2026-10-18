<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TrmFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;

/**
 * @property Carbon $fecha
 * @property string $valor
 *
 * @method static Builder<static> vigente()
 */
final class Trm extends Model
{
    /** @use HasFactory<TrmFactory> */
    use HasFactory;

    protected $table = 'trm';

    protected $fillable = ['fecha', 'valor'];

    protected function casts(): array
    {
        return ['fecha' => 'date', 'valor' => 'decimal:4'];
    }

    /** @param  Builder<static>  $query */
    public function scopeVigente(Builder $query): void
    {
        $query
            ->whereDate('fecha', '<=', Date::today())
            ->orderByDesc('fecha')
            ->orderByDesc('id');
    }
}
