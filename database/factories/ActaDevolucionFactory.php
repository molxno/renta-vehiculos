<?php

namespace Database\Factories;

use App\Models\ActaDevolucion;
use App\Models\Reserva;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActaDevolucion>
 */
class ActaDevolucionFactory extends Factory
{
    protected $model = ActaDevolucion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reserva_id' => Reserva::factory(),
            'devuelto_at' => now(),
            'checklist' => ['documentos' => true, 'llantas' => true, 'combustible' => 'medio'],
            'observaciones' => null,
        ];
    }
}
