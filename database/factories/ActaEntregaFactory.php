<?php

namespace Database\Factories;

use App\Models\ActaEntrega;
use App\Models\Reserva;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActaEntrega>
 */
class ActaEntregaFactory extends Factory
{
    protected $model = ActaEntrega::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reserva_id' => Reserva::factory(),
            'entregado_at' => now(),
            'checklist' => ['documentos' => true, 'llantas' => true, 'combustible' => 'lleno'],
            'observaciones' => null,
        ];
    }
}
