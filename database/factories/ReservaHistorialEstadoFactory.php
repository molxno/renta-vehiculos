<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ReservaEstado;
use App\Models\Reserva;
use App\Models\ReservaHistorialEstado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReservaHistorialEstado>
 */
class ReservaHistorialEstadoFactory extends Factory
{
    protected $model = ReservaHistorialEstado::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reserva_id' => Reserva::factory(),
            'estado_anterior' => ReservaEstado::Pendiente,
            'estado_nuevo' => ReservaEstado::Confirmada,
            'ocurrio_at' => now(),
        ];
    }
}
