<?php

namespace Database\Factories;

use App\Models\Multa;
use App\Models\Reserva;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Multa>
 */
class MultaFactory extends Factory
{
    protected $model = Multa::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reserva_id' => Reserva::factory(),
            'concepto' => fake()->sentence(),
            'monto_centavos' => fake()->numberBetween(500000, 5000000),
        ];
    }
}
