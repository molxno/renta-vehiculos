<?php

namespace Database\Seeders;

use App\Enums\FacturaEstado;
use App\Enums\MetodoPago;
use App\Enums\PagoEstado;
use App\Enums\ReservaEstado;
use App\Models\Factura;
use App\Models\Pago;
use App\Models\Reserva;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Database\Seeder;

class PagoRechazadoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(MinimumCatalogSeeder::class);

        $reserva = Reserva::updateOrCreate(
            ['qr_codigo' => 'QR-PAGO-RECHAZADO'],
            [
                'user_id' => User::where('email', 'cliente@renta.test')->value('id'),
                'vehiculo_id' => Vehiculo::where('placa', 'ABC-123')->value('id'),
                'estado' => ReservaEstado::Facturada,
                'inicio' => '2026-02-15 09:00:00',
                'fin' => '2026-02-18 09:00:00',
                'total_centavos' => 45000000,
                'moneda' => 'COP',
            ],
        );

        Factura::updateOrCreate(
            ['reserva_id' => $reserva->id],
            [
                'numero' => 'FAC-PAGO-RECHAZADO',
                'estado' => FacturaEstado::Pendiente,
                'subtotal_centavos' => 45000000,
                'impuestos_centavos' => 8550000,
                'total_centavos' => 53550000,
                'moneda' => 'COP',
                'desglose' => [['concepto' => 'Renta', 'monto_centavos' => 45000000]],
            ],
        );

        Pago::updateOrCreate(
            ['referencia' => 'PAY-PAGO-RECHAZADO'],
            [
                'reserva_id' => $reserva->id,
                'estado' => PagoEstado::Rechazado,
                'metodo' => MetodoPago::Tarjeta,
                'monto_centavos' => 53550000,
                'mensaje' => 'Fondos insuficientes',
            ],
        );
    }
}
