<?php

namespace Database\Factories;

use App\Enums\VehiculoEstado;
use App\Models\Vehiculo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehiculo>
 */
class VehiculoFactory extends Factory
{
    protected $model = Vehiculo::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'placa' => strtoupper(fake()->unique()->bothify('???-###')),
            'marca' => fake()->randomElement(['Toyota', 'Renault', 'Kia']),
            'modelo' => fake()->randomElement(['Corolla', 'Duster', 'Picanto']),
            'anio' => fake()->numberBetween(2020, 2026),
            'tipo' => fake()->randomElement(['sedan', 'camioneta', 'compacto']),
            'estado' => VehiculoEstado::Disponible,
        ];
    }
}
