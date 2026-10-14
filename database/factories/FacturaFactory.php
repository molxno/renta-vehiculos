<?php

namespace Database\Factories;

use App\Enums\FacturaEstado;
use App\Models\Factura;
use App\Models\Reserva;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Factura>
 */
class FacturaFactory extends Factory
{
    protected $model = Factura::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reserva_id' => Reserva::factory(),
            'numero' => fake()->unique()->bothify('FAC-########'),
            'estado' => FacturaEstado::Pendiente,
            'subtotal_centavos' => 45000000,
            'impuestos_centavos' => 8550000,
            'total_centavos' => 53550000,
            'moneda' => 'COP',
            'desglose' => [['concepto' => 'Renta', 'monto_centavos' => 45000000]],
        ];
    }
}
