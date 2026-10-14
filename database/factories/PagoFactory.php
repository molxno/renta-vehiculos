<?php

namespace Database\Factories;

use App\Enums\MetodoPago;
use App\Enums\PagoEstado;
use App\Models\Pago;
use App\Models\Reserva;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pago>
 */
class PagoFactory extends Factory
{
    protected $model = Pago::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reserva_id' => Reserva::factory(),
            'estado' => PagoEstado::Pendiente,
            'metodo' => MetodoPago::Tarjeta,
            'monto_centavos' => 53550000,
            'referencia' => fake()->unique()->bothify('PAY-########'),
            'mensaje' => null,
        ];
    }
}
