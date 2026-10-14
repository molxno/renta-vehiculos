<?php

namespace Database\Factories;

use App\Models\Poliza;
use App\Models\Vehiculo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Poliza>
 */
class PolizaFactory extends Factory
{
    protected $model = Poliza::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vehiculo_id' => Vehiculo::factory(),
            'numero' => fake()->unique()->bothify('POL-########'),
            'aseguradora' => fake()->randomElement(['SURA', 'Mapfre', 'Allianz']),
            'vigencia_desde' => now()->startOfYear()->toDateString(),
            'vigencia_hasta' => now()->addYear()->endOfYear()->toDateString(),
        ];
    }
}
