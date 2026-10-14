<?php

namespace Database\Factories;

use App\Models\Contrato;
use App\Models\Reserva;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contrato>
 */
class ContratoFactory extends Factory
{
    protected $model = Contrato::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reserva_id' => Reserva::factory(),
            'numero' => fake()->unique()->bothify('CTR-########'),
            'condiciones' => ['combustible' => 'lleno', 'kilometraje_incluido' => 200],
            'firmado_at' => now(),
        ];
    }
}
