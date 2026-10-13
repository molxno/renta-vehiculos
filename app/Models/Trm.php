<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trm extends Model
{
    protected $table = 'trm';

    protected $fillable = ['fecha', 'valor'];

    protected function casts(): array
    {
        return ['fecha' => 'date', 'valor' => 'decimal:4'];
    }
}
