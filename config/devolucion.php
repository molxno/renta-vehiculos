<?php

/*
|--------------------------------------------------------------------------
| Reglas de cobro de la devolución
|--------------------------------------------------------------------------
|
| Valores simulados y deterministas para calcular el costo final de una
| devolución. Los importes se expresan en centavos. El retraso no se
| configura aquí: sigue la regla A1 de CU-004 sobre la tarifa diaria.
|
*/

return [
    'iva_porcentaje' => 19,

    'kilometraje_incluido' => 300,

    'kilometro_extra_centavos' => 150000,

    'cuarto_combustible_centavos' => 3500000,
];
