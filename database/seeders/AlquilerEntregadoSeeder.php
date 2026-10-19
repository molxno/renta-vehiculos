<?php

namespace Database\Seeders;

use App\Enums\ReservaEstado;
use App\Models\ActaEntrega;
use App\Models\Contrato;
use App\Models\Reserva;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Database\Seeder;

class AlquilerEntregadoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(MinimumCatalogSeeder::class);

        $reserva = Reserva::updateOrCreate(
            ['qr_codigo' => 'QR-ALQUILER-ENTREGADO'],
            [
                'user_id' => User::where('email', 'cliente@renta.test')->value('id'),
                'vehiculo_id' => Vehiculo::where('placa', 'DEF-456')->value('id'),
                'estado' => ReservaEstado::Entregada,
                'inicio' => '2026-02-05 09:00:00',
                'fin' => '2026-02-08 09:00:00',
                'total_centavos' => 66000000,
                'moneda' => 'COP',
            ],
        );

        Contrato::updateOrCreate(
            ['reserva_id' => $reserva->id],
            ['numero' => 'CTR-ALQUILER-ENTREGADO', 'condiciones' => ['combustible' => 'lleno', 'kilometraje_incluido' => 300], 'firmado_at' => '2026-02-05 08:30:00'],
        );

        $acta = ActaEntrega::updateOrCreate(
            ['reserva_id' => $reserva->id],
            [
                'entregado_at' => '2026-02-05 09:00:00',
                'checklist' => ['documentos' => true, 'llantas' => true, 'kilometraje' => 10000, 'combustible' => 'lleno'],
                'observaciones' => null,
                'coordinador_id' => User::where('email', 'coordinador@renta.test')->value('id'),
                'firmante_nombre' => 'Cliente Demo',
                'firmado_at' => '2026-02-05 09:00:00',
                'licencia_resultado' => 'Licencia LIC-DEMO-001 vigente, categoría B1 válida',
            ],
        );
        $acta->update([
            'numero' => sprintf('%s-%d', (string) config('entrega.prefijo_acta'), $acta->id),
        ]);
    }
}
