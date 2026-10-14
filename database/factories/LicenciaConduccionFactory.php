<?php

namespace Database\Factories;

use App\Models\LicenciaConduccion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LicenciaConduccion>
 */
class LicenciaConduccionFactory extends Factory
{
    protected $model = LicenciaConduccion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'numero' => fake()->unique()->bothify('LIC-########'),
            'fecha_vencimiento' => now()->addYears(3)->toDateString(),
            'categoria' => 'B1',
        ];
    }
}
