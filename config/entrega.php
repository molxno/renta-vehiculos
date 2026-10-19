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
*/

return [
    'anticipo_porcentaje' => 30,

    'kilometraje_incluido' => 200,

    'categorias_validas' => ['A1', 'A2', 'B1', 'B2', 'C1', 'C2', 'C3'],
];
