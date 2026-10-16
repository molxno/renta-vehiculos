<?php

namespace Database\Seeders;

use App\Enums\FacturaEstado;
use App\Enums\ReservaEstado;
use App\Models\ActaDevolucion;
use App\Models\Factura;
use App\Models\Reserva;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Database\Seeder;

class DevolucionFacturadaSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(MinimumCatalogSeeder::class);

        $reserva = Reserva::updateOrCreate(
            ['qr_codigo' => 'QR-DEVOLUCION-FACTURA'],
            [
                'user_id' => User::where('email', 'cliente@renta.test')->value('id'),
                'vehiculo_id' => Vehiculo::where('placa', 'GHI-789')->value('id'),
                'estado' => ReservaEstado::Devuelta,
                'inicio' => '2026-02-10 09:00:00',
                'fin' => '2026-02-13 09:00:00',
                'total_centavos' => 45000000,
                'moneda' => 'COP',
            ],
        );

        ActaDevolucion::updateOrCreate(
            ['reserva_id' => $reserva->id],
            ['devuelto_at' => '2026-02-13 09:00:00', 'checklist' => ['documentos' => true, 'llantas' => true], 'observaciones' => null],
        );

        Factura::updateOrCreate(
            ['reserva_id' => $reserva->id],
            [
                'numero' => 'FAC-DEVOLUCION-PENDIENTE',
                'estado' => FacturaEstado::Pendiente,
                'subtotal_centavos' => 45000000,
                'impuestos_centavos' => 8550000,
                'total_centavos' => 53550000,
                'moneda' => 'COP',
                'desglose' => [['concepto' => 'Renta', 'monto_centavos' => 45000000]],
            ],
        );
    }
}
