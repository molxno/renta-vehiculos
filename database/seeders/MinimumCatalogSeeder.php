<?php

namespace Database\Seeders;

use App\Enums\UsuarioRol;
use App\Enums\VehiculoEstado;
use App\Models\Convenio;
use App\Models\LicenciaConduccion;
use App\Models\Poliza;
use App\Models\Tarifa;
use App\Models\Trm;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Database\Seeder;

class MinimumCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Cliente Demo', 'email' => 'cliente@renta.test', 'rol' => UsuarioRol::Cliente, 'documento' => '1000000001'],
            ['name' => 'Coordinador Demo', 'email' => 'coordinador@renta.test', 'rol' => UsuarioRol::Coordinador, 'documento' => '1000000002'],
            ['name' => 'Cajero Demo', 'email' => 'cajero@renta.test', 'rol' => UsuarioRol::Cajero, 'documento' => '1000000003'],
            ['name' => 'Administrador Demo', 'email' => 'administrador@renta.test', 'rol' => UsuarioRol::Administrador, 'documento' => '1000000004'],
            ['name' => 'Gerente Demo', 'email' => 'gerente@renta.test', 'rol' => UsuarioRol::Gerente, 'documento' => '1000000005'],
        ];

        foreach ($users as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'tipo_documento' => 'CC',
                    'numero_documento' => $data['documento'],
                    'telefono' => '3000000001',
                    'rol' => $data['rol'],
                    'password' => 'password',
                ],
            );

            if ($data['rol'] === UsuarioRol::Cliente) {
                LicenciaConduccion::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'numero' => 'LIC-DEMO-001',
                        'fecha_vencimiento' => '2028-12-31',
                        'categoria' => 'B1',
                    ],
                );
            }
        }

        $vehicles = [
            ['placa' => 'ABC-123', 'marca' => 'Toyota', 'modelo' => 'Corolla', 'anio' => 2024, 'tipo' => 'sedan'],
            ['placa' => 'DEF-456', 'marca' => 'Renault', 'modelo' => 'Duster', 'anio' => 2023, 'tipo' => 'camioneta'],
            ['placa' => 'GHI-789', 'marca' => 'Kia', 'modelo' => 'Picanto', 'anio' => 2022, 'tipo' => 'compacto'],
        ];

        foreach ($vehicles as $data) {
            $vehicle = Vehiculo::updateOrCreate(
                ['placa' => $data['placa']],
                [...$data, 'estado' => VehiculoEstado::Disponible],
            );

            Poliza::updateOrCreate(
                ['numero' => 'POL-'.$vehicle->placa],
                [
                    'vehiculo_id' => $vehicle->id,
                    'aseguradora' => 'SURA',
                    'vigencia_desde' => '2026-01-01',
                    'vigencia_hasta' => '2026-12-31',
                ],
            );

            Tarifa::updateOrCreate(
                ['vehiculo_id' => $vehicle->id, 'vigente_desde' => '2026-01-01'],
                [
                    'tipo_vehiculo' => $vehicle->tipo,
                    'valor_diario_centavos' => $vehicle->tipo === 'camioneta' ? 22000000 : 15000000,
                    'vigente_hasta' => null,
                ],
            );
        }

        Convenio::updateOrCreate(
            ['nombre' => 'Convenio corporativo demo'],
            ['descuento_porcentaje' => 10, 'activo' => true],
        );

        $trm = Trm::whereDate('fecha', '2026-01-01')->first() ?? new Trm;
        $trm->fecha = '2026-01-01';
        $trm->valor = 4200.0000;
        $trm->save();
    }
}
