<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TrmFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $fecha
 * @property string $valor
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
}
