<?php

namespace App\Models;

use Database\Factories\TrmFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trm extends Model
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
