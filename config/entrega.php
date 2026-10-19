<?php

/*
|--------------------------------------------------------------------------
| Reglas de la entrega
|--------------------------------------------------------------------------
|
| El anticipo se calcula como porcentaje entero del total de la reserva,
| en centavos. El kilometraje incluido queda en las condiciones del
| contrato y lo usa la devolución.
|
| La ventana de entrega compara fechas de calendario en zona_horaria:
| si ventana_incluye_inicio es true y ventana_incluye_fin es false,
| hoy es entregable cuando fecha_inicio <= hoy < fecha_fin.
|
*/

return [
    'anticipo_porcentaje' => 30,

    'kilometraje_incluido' => 200,

    'categorias_validas' => ['A1', 'A2', 'B1', 'B2', 'C1', 'C2', 'C3'],

    'zona_horaria' => env('ENTREGA_ZONA_HORARIA', 'America/Bogota'),

    'formato_fecha' => 'd/m/Y',

    'ventana_incluye_inicio' => true,

    'ventana_incluye_fin' => false,

    'prefijo_acta' => 'ACT',
];
