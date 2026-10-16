<?php

namespace Database\Factories;

use App\Models\Trm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trm>
 */
class TrmFactory extends Factory
{
    protected $model = Trm::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fecha' => fake()->unique()->date(),
            'valor' => fake()->randomFloat(4, 3800, 4500),
        ];
    }
}
