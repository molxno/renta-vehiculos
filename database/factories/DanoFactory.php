<?php

namespace Database\Factories;

use App\Models\Dano;
use App\Models\Reserva;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dano>
 */
class DanoFactory extends Factory
{
    protected $model = Dano::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reserva_id' => Reserva::factory(),
            'descripcion' => fake()->sentence(),
            'monto_centavos' => fake()->numberBetween(500000, 5000000),
        ];
    }
}
