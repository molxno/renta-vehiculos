# Evidencia Entrega 2 — main 0a2dced

SHA: `0a2dced533ee0d344565f82ca844696579f46f75`
Extracción en solo lectura.

# 1. RegistrarEntrega

## app/Actions/Entregas/RegistrarEntrega.php

Comando: `git show 0a2dced:app/Actions/Entregas/RegistrarEntrega.php | cat -n`

```
     1	<?php
     2
     3	declare(strict_types=1);
     4
     5	namespace App\Actions\Entregas;
     6
     7	use App\Enums\MetodoPago;
     8	use App\Enums\PagoEstado;
     9	use App\Enums\VehiculoEstado;
    10	use App\Exceptions\TransicionEstadoInvalida;
    11	use App\Models\ActaEntrega;
    12	use App\Models\Reserva;
    13	use Carbon\CarbonInterface;
    14	use Illuminate\Support\Facades\DB;
    15	use Illuminate\Validation\ValidationException;
    16
    17	final class RegistrarEntrega
    18	{
    19	    /**
    20	     * Registra el acta, el contrato firmado, el anticipo y marca el vehículo alquilado.
    21	     *
    22	     * @param  array<array-key, mixed>|null  $checklist
    23	     */
    24	    public function handle(
    25	        Reserva $reserva,
    26	        CarbonInterface $entregadoAt,
    27	        ?array $checklist,
    28	        ?string $observaciones,
    29	    ): ActaEntrega {
    30	        return DB::transaction(function () use ($reserva, $entregadoAt, $checklist, $observaciones): ActaEntrega {
    31	            $condicionesCombustible = is_array($checklist) && is_string($checklist['combustible'] ?? null)
    32	                ? $checklist['combustible']
    33	                : 'lleno';
    34
    35	            $reserva->contrato()->create([
    36	                'numero' => sprintf('CTR-%06d', $reserva->id),
    37	                'condiciones' => [
    38	                    'combustible' => $condicionesCombustible,
    39	                    'kilometraje_incluido' => (int) config('entrega.kilometraje_incluido'),
    40	                ],
    41	                'firmado_at' => $entregadoAt,
    42	            ]);
    43
    44	            $acta = $reserva->actaEntrega()->create([
    45	                'entregado_at' => $entregadoAt,
    46	                'checklist' => $checklist,
    47	                'observaciones' => $observaciones,
    48	            ]);
    49
    50	            $reserva->pagos()->create([
    51	                'estado' => PagoEstado::Aprobado,
    52	                'metodo' => MetodoPago::Efectivo,
    53	                'monto_centavos' => $reserva->anticipoCentavos(),
    54	                'referencia' => sprintf('ANT-%06d', $reserva->id),
    55	                'mensaje' => 'Anticipo de entrega',
    56	            ]);
    57
    58	            try {
    59	                $reserva->entregar();
    60	            } catch (TransicionEstadoInvalida $excepcion) {
    61	                throw ValidationException::withMessages([
    62	                    'reserva' => $excepcion->getMessage(),
    63	                ]);
    64	            }
    65
    66	            $reserva->vehiculo()->update(['estado' => VehiculoEstado::Alquilado]);
    67
    68	            return $acta;
    69	        });
    70	    }
    71	}
```

# 2. RegistrarDevolucion

Comando: `git ls-tree -r --name-only 0a2dced | grep RegistrarDevolucion`

```
app/Actions/Devoluciones/RegistrarDevolucion.php
```

## app/Actions/Devoluciones/RegistrarDevolucion.php

Comando: `git show 0a2dced:app/Actions/Devoluciones/RegistrarDevolucion.php | cat -n`

```
     1	<?php
     2
     3	declare(strict_types=1);
     4
     5	namespace App\Actions\Devoluciones;
     6
     7	use App\Contracts\CalculadoraDevolucion;
     8	use App\Enums\FacturaEstado;
     9	use App\Enums\VehiculoEstado;
    10	use App\Exceptions\TransicionEstadoInvalida;
    11	use App\Models\ActaDevolucion;
    12	use App\Models\Factura;
    13	use App\Models\Reserva;
    14	use Carbon\CarbonInterface;
    15	use Illuminate\Support\Facades\DB;
    16	use Illuminate\Validation\ValidationException;
    17
    18	final class RegistrarDevolucion
    19	{
    20	    public function __construct(private CalculadoraDevolucion $calculadora) {}
    21
    22	    /**
    23	     * Registra el acta de devolución, los daños y la factura pendiente.
    24	     *
    25	     * @param  array<array-key, mixed>|null  $checklist
    26	     * @param  array<int, array{descripcion: string, monto_centavos: int}>  $danos
    27	     */
    28	    public function handle(
    29	        Reserva $reserva,
    30	        CarbonInterface $devueltoAt,
    31	        ?array $checklist,
    32	        ?string $observaciones,
    33	        array $danos = [],
    34	    ): ActaDevolucion {
    35	        return DB::transaction(function () use ($reserva, $devueltoAt, $checklist, $observaciones, $danos): ActaDevolucion {
    36	            foreach ($danos as $dano) {
    37	                $reserva->danos()->create([
    38	                    'descripcion' => $dano['descripcion'],
    39	                    'monto_centavos' => $dano['monto_centavos'],
    40	                ]);
    41	            }
    42
    43	            $acta = $reserva->actaDevolucion()->create([
    44	                'devuelto_at' => $devueltoAt,
    45	                'checklist' => $checklist,
    46	                'observaciones' => $observaciones,
    47	            ]);
    48
    49	            $cobro = $this->calculadora->calcular(
    50	                $reserva->id,
    51	                $devueltoAt,
    52	                is_array($checklist) ? $checklist : [],
    53	            );
    54
    55	            Factura::query()->create([
    56	                'reserva_id' => $reserva->id,
    57	                'numero' => sprintf('FAC-%06d', $reserva->id),
    58	                'estado' => FacturaEstado::Pendiente,
    59	                'subtotal_centavos' => $cobro['subtotal_centavos'],
    60	                'impuestos_centavos' => $cobro['impuestos_centavos'],
    61	                'total_centavos' => $cobro['total_centavos'],
    62	                'moneda' => $reserva->moneda,
    63	                'desglose' => $cobro['desglose'],
    64	            ]);
    65
    66	            try {
    67	                $reserva->devolver();
    68	            } catch (TransicionEstadoInvalida $excepcion) {
    69	                throw ValidationException::withMessages([
    70	                    'reserva' => $excepcion->getMessage(),
    71	                ]);
    72	            }
    73
    74	            $reserva->vehiculo()->update(['estado' => VehiculoEstado::Disponible]);
    75
    76	            return $acta;
    77	        });
    78	    }
    79	}
```

# 3. DevolucionController

## app/Http/Controllers/DevolucionController.php

Comando: `git show 0a2dced:app/Http/Controllers/DevolucionController.php | cat -n`

```
     1	<?php
     2
     3	declare(strict_types=1);
     4
     5	namespace App\Http\Controllers;
     6
     7	use App\Actions\Devoluciones\RegistrarDevolucion;
     8	use App\Contracts\CalculadoraDevolucion;
     9	use App\Enums\NivelCombustible;
    10	use App\Http\Requests\StoreActaDevolucionRequest;
    11	use App\Models\Reserva;
    12	use Illuminate\Http\RedirectResponse;
    13	use Illuminate\Http\Request;
    14	use Illuminate\Support\Facades\Date;
    15	use Inertia\Response;
    16
    17	final class DevolucionController extends Controller
    18	{
    19	    public function create(Request $request, Reserva $reserva, CalculadoraDevolucion $calculadora): Response
    20	    {
    21	        $reserva->load(['actaEntrega', 'contrato', 'multas', 'danos']);
    22
    23	        $devueltoEn = $request->filled('devuelto_at')
    24	            ? Date::parse($request->string('devuelto_at')->toString())
    25	            : Date::now();
    26
    27	        $kilometraje = $request->input('kilometraje', $request->input('checklist.kilometraje'));
    28	        $combustible = $request->input('combustible', $request->input('checklist.combustible'));
    29	        $condiciones = [];
    30
    31	        if ($kilometraje !== null && $kilometraje !== '') {
    32	            $condiciones['kilometraje'] = $kilometraje;
    33	        }
    34
    35	        if (is_string($combustible) && $combustible !== '') {
    36	            $condiciones['combustible'] = $combustible;
    37	        }
    38
    39	        $cobro = $calculadora->calcular($reserva->id, $devueltoEn, $condiciones);
    40
    41	        return inertia('Devoluciones/Create', [
    42	            'reservation' => $reserva,
    43	            'checklist' => $reserva->checklistEntrega(),
    44	            'estimatedChargesCents' => $cobro['cargos_centavos'],
    45	            'cobro' => $cobro,
    46	            'actaEntrega' => $reserva->actaEntrega,
    47	            'storeUrl' => route('devoluciones.store', $reserva),
    48	            'createUrl' => route('devoluciones.create', $reserva),
    49	            'filters' => [
    50	                'devuelto_at' => $request->string('devuelto_at')->toString(),
    51	                'kilometraje' => is_scalar($kilometraje) ? (string) $kilometraje : '',
    52	                'combustible' => is_string($combustible) ? $combustible : '',
    53	            ],
    54	            'nivelesCombustible' => array_map(
    55	                fn (NivelCombustible $nivel): array => [
    56	                    'value' => $nivel->value,
    57	                    'label' => $nivel->etiqueta(),
    58	                ],
    59	                NivelCombustible::cases(),
    60	            ),
    61	        ]);
    62	    }
    63
    64	    public function store(
    65	        StoreActaDevolucionRequest $request,
    66	        Reserva $reserva,
    67	        RegistrarDevolucion $registrarDevolucion,
    68	    ): RedirectResponse {
    69	        $registrarDevolucion->handle(
    70	            $reserva,
    71	            $request->devueltoAt(),
    72	            $request->checklist(),
    73	            $request->observaciones(),
    74	            $request->danos(),
    75	        );
    76
    77	        return to_route('reservas.show', $reserva);
    78	    }
    79	}
```

# 4. CrearReserva y ConfirmarReserva

## app/Actions/Reservas/CrearReserva.php

Comando: `git show 0a2dced:app/Actions/Reservas/CrearReserva.php | cat -n`

```
     1	<?php
     2
     3	declare(strict_types=1);
     4
     5	namespace App\Actions\Reservas;
     6
     7	use App\Contracts\DisponibilidadService;
     8	use App\Contracts\TarifaService;
     9	use App\Enums\ReservaEstado;
    10	use App\Models\Reserva;
    11	use App\Models\User;
    12	use App\Models\Vehiculo;
    13	use DateTimeInterface;
    14	use Illuminate\Support\Facades\DB;
    15	use Illuminate\Validation\ValidationException;
    16
    17	final class CrearReserva
    18	{
    19	    public function __construct(
    20	        private DisponibilidadService $disponibilidad,
    21	        private TarifaService $tarifa,
    22	    ) {}
    23
    24	    public function handle(
    25	        User $cliente,
    26	        int $vehiculoId,
    27	        ?int $convenioId,
    28	        DateTimeInterface $inicio,
    29	        DateTimeInterface $fin,
    30	        ?string $moneda = null,
    31	    ): Reserva {
    32	        return DB::transaction(function () use ($cliente, $vehiculoId, $convenioId, $inicio, $fin, $moneda): Reserva {
    33	            Vehiculo::query()->whereKey($vehiculoId)->lockForUpdate()->firstOrFail();
    34
    35	            $vehiculoDisponible = false;
    36	            $motivoNoDisponible = null;
    37
    38	            foreach ($this->disponibilidad->consultar($inicio, $fin, $vehiculoId) as $resultado) {
    39	                if ($resultado['vehiculo_id'] === $vehiculoId) {
    40	                    $vehiculoDisponible = $resultado['disponible'];
    41	                    $motivoNoDisponible = $resultado['motivo'];
    42
    43	                    break;
    44	                }
    45	            }
    46
    47	            if (! $vehiculoDisponible) {
    48	                throw ValidationException::withMessages([
    49	                    'vehiculo_id' => $motivoNoDisponible !== null && $motivoNoDisponible !== ''
    50	                        ? $motivoNoDisponible
    51	                        : 'El vehículo no está disponible para las fechas seleccionadas.',
    52	                ]);
    53	            }
    54
    55	            $cotizacion = $this->tarifa->cotizar($vehiculoId, $inicio, $fin, $convenioId, $moneda);
    56
    57	            return Reserva::query()->create([
    58	                'user_id' => $cliente->id,
    59	                'vehiculo_id' => $vehiculoId,
    60	                'convenio_id' => $convenioId,
    61	                'estado' => ReservaEstado::Pendiente,
    62	                'inicio' => $inicio,
    63	                'fin' => $fin,
    64	                'total_centavos' => $cotizacion['total_centavos'],
    65	                'moneda' => $cotizacion['moneda'],
    66	            ]);
    67	        });
    68	    }
    69	}
```

## app/Actions/Reservas/ConfirmarReserva.php

Comando: `git show 0a2dced:app/Actions/Reservas/ConfirmarReserva.php | cat -n`

```
     1	<?php
     2
     3	declare(strict_types=1);
     4
     5	namespace App\Actions\Reservas;
     6
     7	use App\Contracts\GeneradorQr;
     8	use App\Exceptions\TransicionEstadoInvalida;
     9	use App\Models\Reserva;
    10	use Illuminate\Support\Facades\DB;
    11	use Illuminate\Validation\ValidationException;
    12
    13	final class ConfirmarReserva
    14	{
    15	    public function __construct(private GeneradorQr $generadorQr) {}
    16
    17	    public function handle(Reserva $reserva): Reserva
    18	    {
    19	        return DB::transaction(function () use ($reserva): Reserva {
    20	            $reserva = Reserva::query()
    21	                ->whereKey($reserva->id)
    22	                ->lockForUpdate()
    23	                ->firstOrFail();
    24
    25	            try {
    26	                $reserva->confirmar();
    27	            } catch (TransicionEstadoInvalida $excepcion) {
    28	                throw ValidationException::withMessages([
    29	                    'reserva' => $excepcion->getMessage(),
    30	                ]);
    31	            }
    32
    33	            $reserva->update([
    34	                'qr_codigo' => $this->generadorQr->generar($reserva->id),
    35	            ]);
    36
    37	            return $reserva;
    38	        });
    39	    }
    40	}
```

# 5. docs/contratos.md

## docs/contratos.md

Comando: `git show 0a2dced:docs/contratos.md | cat -n`

````
     1	# Contratos de Fase 0
     2
     3	Este documento define la superficie estable que necesitan los equipos de
     4	historias. PR0 publica nombres, rutas, props y firmas; no implementa reglas de
     5	negocio.
     6
     7	## Decisiones de base
     8
     9	- El modelo de autenticación conserva el nombre `User` por compatibilidad con
    10	  el starter kit.
    11	- El dominio usa nombres en español para tablas y modelos nuevos, excepto
    12	  `User`.
    13	- No se crea una tabla `metodos_pago`: el método de pago será un enum/cast en
    14	  `pagos`.
    15	- No se crea una tabla propia de notificaciones: se usará el mecanismo estándar
    16	  de Laravel si una historia lo necesita.
    17	- No se crea `lineas_factura`: `facturas.desglose` será una columna JSON.
    18	- No existe una entidad `Alquiler`. `Reserva` conserva la máquina de estados:
    19	  `pendiente`, `confirmada`, `entregada`, `devuelta`, `facturada`, `pagada` y
    20	  `cancelada`.
    21	- `ActaEntrega` y `ActaDevolucion` son entidades separadas relacionadas con una
    22	  reserva.
    23	- El archivo Figma Make no expuso un archivo CSS de tokens legible mediante el
    24	  conector. Se usará el tema provisional azul marino `#1E2761` y ámbar
    25	  `#E8A33D`, con Instrument Sans como tipografía disponible en el starter.
    26
    27	## Estados y transiciones permitidas
    28
    29	### Reserva
    30
    31	```text
    32	pendiente  -> confirmada
    33	confirmada -> entregada
    34	entregada  -> devuelta
    35	devuelta   -> facturada
    36	facturada  -> pagada
    37	confirmada -> cancelada
    38	```
    39
    40	La creación deja la reserva pendiente. Solo el cliente propietario puede
    41	confirmarla; al confirmarse se genera y persiste el QR. Una reserva cancelada
    42	es terminal. No se permiten saltos de estado ni transiciones inversas en la
    43	capa de aplicación. Las rutas de creación y confirmación requieren un cliente
    44	autenticado. La ruta `reservas.qr` localiza la reserva por su código y redirige
    45	a su página de consulta.
    46
    47	### Vehículo
    48
    49	Los estados son `disponible`, `alquilado`, `contingencia` y `mantenimiento`.
    50
    51	```text
    52	disponible   -> alquilado
    53	alquilado    -> disponible
    54	disponible   -> contingencia
    55	contingencia -> disponible
    56	disponible   -> mantenimiento
    57	mantenimiento -> disponible
    58	```
    59
    60	Las reservas confirmadas no deben inventar disponibilidad; esa decisión
    61	pertenece a `DisponibilidadService`.
    62
    63	## Rutas y páginas Inertia
    64
    65	Las rutas siguientes son el contrato HTTP de las historias. PR0 solo las
    66	documenta; serán registradas con controladores placeholder en PR3a.
    67
    68	| Nombre                | Método | URI                              | Página                         |
    69	| --------------------- | ------ | -------------------------------- | ------------------------------ |
    70	| `home`                | GET    | `/`                              | `Welcome`                      |
    71	| `catalogo`            | GET    | `/catalogo`                      | `Catalogo/Index`               |
    72	| `reservas.index`      | GET    | `/reservas`                      | `Reservas/Index`               |
    73	| `reservas.create`     | GET    | `/reservas/crear`                | `Reservas/Create`              |
    74	| `reservas.store`      | POST   | `/reservas`                      | redirección a `reservas.show`  |
    75	| `reservas.qr`         | GET    | `/reservas/qr/{qrCodigo}`        | redirección a `reservas.show`  |
    76	| `reservas.show`       | GET    | `/reservas/{reserva}`            | `Reservas/Show`                |
    77	| `reservas.confirmar`  | POST   | `/reservas/{reserva}/confirmar`  | redirección                    |
    78	| `reservas.cancelar`   | POST   | `/reservas/{reserva}/cancelar`   | redirección                    |
    79	| `entregas.create`     | GET    | `/reservas/{reserva}/entrega`    | `Entregas/Create`              |
    80	| `entregas.store`      | POST   | `/reservas/{reserva}/entrega`    | redirección                    |
    81	| `devoluciones.create` | GET    | `/reservas/{reserva}/devolucion` | `Devoluciones/Create`          |
    82	| `devoluciones.store`  | POST   | `/reservas/{reserva}/devolucion` | redirección                    |
    83	| `facturas.show`       | GET    | `/facturas/{factura}`            | `Facturas/Show`                |
    84	| `pagos.create`        | GET    | `/reservas/{reserva}/pago`       | `Pagos/Create`                 |
    85	| `pagos.store`         | POST   | `/reservas/{reserva}/pago`       | redirección                    |
    86	| `admin.trm.edit`      | GET    | `/administracion/trm`            | `Administracion/Trm/Edit`      |
    87	| `admin.trm.update`    | PUT    | `/administracion/trm`            | redirección a `admin.trm.edit` |
    88
    89	### Props comunes
    90
    91	```ts
    92	export type UserRole =
    93	    'cliente' | 'coordinador' | 'cajero' | 'administrador' | 'gerente';
    94
    95	export interface AuthUser {
    96	    id: number;
    97	    name: string;
    98	    role: UserRole;
    99	}
   100
   101	export interface SharedPageProps {
   102	    auth: {
   103	        user: AuthUser | null;
   104	    };
   105	    flash?: {
   106	        success?: string;
   107	        error?: string;
   108	    };
   109	}
   110	```
   111
   112	### Props por página
   113
   114	```ts
   115	export interface CatalogVehicle {
   116	    id: number;
   117	    label: string;
   118	    brand: string;
   119	    model: string;
   120	    year: number;
   121	    category: string;
   122	    status: 'disponible' | 'alquilado' | 'contingencia' | 'mantenimiento';
   123	    dailyRateCents: number;
   124	}
   125
   126	export interface CatalogIndexProps extends SharedPageProps {
   127	    vehicles: CatalogVehicle[];
   128	    categories: string[];
   129	    filters: {
   130	        category: string;
   131	        startDate: string;
   132	        endDate: string;
   133	        location: string;
   134	    };
   135	}
   136
   137	export interface ReservationSummary {
   138	    id: number;
   139	    status:
   140	        | 'pendiente'
   141	        | 'confirmada'
   142	        | 'entregada'
   143	        | 'devuelta'
   144	        | 'facturada'
   145	        | 'pagada'
   146	        | 'cancelada';
   147	    vehicleId: number;
   148	    startsAt: string;
   149	    endsAt: string;
   150	    totalCents: number;
   151	    currency: string;
   152	}
   153
   154	export interface ReservationsIndexProps extends SharedPageProps {
   155	    reservations: ReservationSummary[];
   156	    filters: {
   157	        status?: ReservationSummary['status'];
   158	        page: number;
   159	    };
   160	}
   161
   162	export interface ReservationsCreateProps extends SharedPageProps {
   163	    vehicles: CatalogVehicle[];
   164	    filters: {
   165	        vehiculoId: number | null;
   166	        inicio: string;
   167	        fin: string;
   168	    };
   169	    trm: string | null;
   170	    quote: {
   171	        dailyRateCents: number;
   172	        subtotalCents: number;
   173	        discountCents: number;
   174	        taxesCents: number;
   175	        totalCents: number;
   176	        currency: string;
   177	    } | null;
   178	}
   179
   180	export interface ReservationShowProps extends SharedPageProps {
   181	    reservation: ReservationSummary & {
   182	        vehicleLabel: string;
   183	        vehiclePlate: string;
   184	        qrCode: string | null;
   185	    };
   186	}
   187
   188	export interface DeliveryCreateProps extends SharedPageProps {
   189	    reservation: ReservationSummary;
   190	    checklist: string[];
   191	    licencia: {
   192	        numero: string;
   193	        fecha_vencimiento: string;
   194	        categoria: string;
   195	        vigente: boolean;
   196	    } | null;
   197	    bloqueo: string | null;
   198	    anticipoCents: number;
   199	    currency: string;
   200	    condicionesContrato: Record<string, unknown>;
   201	}
   202
   203	export interface ReturnCreateProps extends SharedPageProps {
   204	    reservation: ReservationSummary;
   205	    checklist: string[];
   206	    estimatedChargesCents: number;
   207	}
   208
   209	export interface InvoiceShowProps extends SharedPageProps {
   210	    invoice: {
   211	        id: number;
   212	        reservationId: number;
   213	        status: 'pendiente' | 'pagada' | 'anulada';
   214	        totalCents: number;
   215	        currency: string;
   216	        desglose: Array<{
   217	            concepto: string;
   218	            montoCents: number;
   219	        }>;
   220	    };
   221	}
   222
   223	export interface PaymentCreateProps extends SharedPageProps {
   224	    reservation: ReservationSummary;
   225	    amountCents: number;
   226	    currency: string;
   227	    methods: Array<'tarjeta' | 'transferencia' | 'efectivo'>;
   228	}
   229	```
   230
   231	`reservas.create` y `reservas.store` requieren un cliente autenticado. El
   232	formulario recibe solo vehículos disponibles para las fechas solicitadas; la
   233	cotización y la TRM provienen de los servicios y registros configurados en el
   234	servidor. `reservas.store` acepta `moneda` opcional (`COP` o `USD`); en USD
   235	la cotización usa la última TRM persistida con fecha no futura. Las rutas
   236	`admin.trm.*` son exclusivas del administrador y no consultan proveedores
   237	externos. La respuesta de `reservas.show` incluye los datos del vehículo y el
   238	código QR de demostración.
   239
   240	## Interfaces de aplicación
   241
   242	Las interfaces PHP de `app/Contracts` son los límites de aplicación. Las
   243	implementaciones reales y falsas se incorporarán después, mediante inyección
   244	del contenedor; este PR no registra implementaciones.
   245
   246	### Formato monetario
   247
   248	Todos los contratos expresan importes en centavos enteros y declaran la moneda
   249	en el resultado cuando corresponde. Esto evita ambigüedad de redondeo entre
   250	PHP, la base de datos y TypeScript.
   251
   252	### Disponibilidad por fechas
   253
   254	`DisponibilidadService::consultar` devuelve los vehículos filtrados por ID
   255	cuando se proporciona y determina disponibilidad con intervalos semiabiertos:
   256	una reserva existente bloquea si su inicio es anterior al fin de la consulta y
   257	su fin es posterior al inicio. Las reservas canceladas no bloquean; los
   258	vehículos cuyo estado no sea `disponible` tampoco se ofrecen. Si ninguno de
   259	los vehículos consultados está disponible, el motivo recomienda modificar las
   260	fechas o seleccionar otro vehículo. El fixture independiente puede cargarse
   261	con `php artisan db:seed --class=DisponibilidadSeeder`.
   262
   263	### FormRequests
   264
   265	- `FiltrarCatalogoRequest`
   266	- `PrepararReservaRequest`
   267	- `StoreReservaRequest`
   268	- `UpdateTrmRequest`
   269	- `ConfirmarReservaRequest`
   270	- `StoreActaEntregaRequest`
   271	- `StoreActaDevolucionRequest`
   272	- `StorePagoRequest`
   273
   274	### Próximos límites
   275
   276	Las interfaces no cambian por una implementación fake. Las historias pueden
   277	construir sus casos contra estos contratos y sustituir el binding del
   278	contenedor en tests.
   279
   280	## Implementaciones configurables
   281
   282	Los bindings de contratos se declaran en `config/servicios.php`, con una línea
   283	por interfaz. Para cambiar una implementación, cada desarrollador modifica
   284	únicamente la línea del contrato que necesita y no `AppServiceProvider`, lo que
   285	reduce conflictos entre historias. El proveedor registra automáticamente ese
   286	mapa al iniciar la aplicación.
   287
   288	## Estado del panel inicial
   289
   290	El panel de reservas de la interfaz inicial es únicamente un placeholder
   291	visual. Sus métricas, filas y búsqueda son datos estáticos de demostración; no
   292	ejecuta lógica de negocio ni consulta datos reales. Las historias posteriores
   293	deben sustituirlo por props provenientes de los controladores contractuales.
   294
   295	## Mapa de historias, rutas y props
   296
   297	| Historia                         | Ruta(s)                                                                | Página Inertia                                       | Props principales                                             |
   298	| -------------------------------- | ---------------------------------------------------------------------- | ---------------------------------------------------- | ------------------------------------------------------------- |
   299	| Consultar catálogo               | `catalogo`                                                             | `Catalogo/Index`                                     | `vehicles`, `categories`, `filters`                           |
   300	| Crear y consultar reservas       | `reservas.index`, `reservas.create`, `reservas.store`, `reservas.show` | `Reservas/Index`, `Reservas/Create`, `Reservas/Show` | `reservations`, `filters`, `vehicles`, `quote`, `reservation` |
   301	| Confirmar o cancelar una reserva | `reservas.confirmar`, `reservas.cancelar`                              | redirección a `Reservas/Show`                        | `reservation`                                                 |
   302	| Registrar entrega                | `entregas.create`, `entregas.store`                                    | `Entregas/Create`                                    | `reservation`, `checklist`                                    |
   303	| Registrar devolución             | `devoluciones.create`, `devoluciones.store`                            | `Devoluciones/Create`                                | `reservation`, `checklist`, `estimatedChargesCents`           |
   304	| Consultar factura                | `facturas.show`                                                        | `Facturas/Show`                                      | `invoice`                                                     |
   305	| Registrar pago                   | `pagos.create`, `pagos.store`                                          | `Pagos/Create` y redirección                         | `reservation`, `amountCents`, `currency`, `methods`           |
   306
   307	Las páginas de catálogo y creación/detalle de reservas están implementadas con
   308	props tipadas. El listado de reservas continúa pendiente; otros flujos usan los
   309	contratos y fakes enlazados por el contenedor según su estado de desarrollo.
````

# 6. VehiculoEstado::

Comando: `git grep -n "VehiculoEstado::" 0a2dced -- app`

```
0a2dced:app/Actions/Catalogo/ListarVehiculosDestacados.php:21:            ->where('estado', VehiculoEstado::Disponible)
0a2dced:app/Actions/Devoluciones/RegistrarDevolucion.php:74:            $reserva->vehiculo()->update(['estado' => VehiculoEstado::Disponible]);
0a2dced:app/Actions/Entregas/RegistrarEntrega.php:66:            $reserva->vehiculo()->update(['estado' => VehiculoEstado::Alquilado]);
0a2dced:app/Fakes/FakeDisponibilidadService.php:35:                $estaDisponible = $vehiculo->estado === VehiculoEstado::Disponible && ! $estaOcupado;
0a2dced:app/Fakes/FakeDisponibilidadService.php:42:                        $vehiculo->estado !== VehiculoEstado::Disponible => 'El vehículo no está disponible para renta.',
0a2dced:app/Models/Vehiculo.php:33:        return ['estado' => VehiculoEstado::class, 'anio' => 'integer'];
```

# 7. git show --stat 05f4684

Comando: `git show --stat 05f4684`

```
commit 05f46849e5204f5c4748f9cf3a695c1cbafc20d7
Author: molxno <s.molano0818@gmail.com>
Date:   Sun Oct 18 15:14:29 2026 -0500

    Aplicar Strategy, State y Adapter, extraer TRM y cerrar huecos de la demo.

    Co-authored-by: Cursor <cursoragent@cursor.com>

 app/Actions/Devoluciones/RegistrarDevolucion.php   |  15 ++-
 app/Actions/Entregas/RegistrarEntrega.php          |  12 +-
 app/Actions/Pagos/RegistrarPago.php                |   6 +-
 app/Actions/Reservas/ConfirmarReserva.php          |   9 +-
 app/Actions/Reservas/ListarReservas.php            |  48 +++++++
 app/Contracts/CargoDevolucionStrategy.php          |  21 ++++
 app/Contracts/ReservaState.php                     |  20 +++
 app/Exceptions/TransicionEstadoInvalida.php        |  33 +++++
 app/Http/Controllers/DevolucionController.php      |  27 +++-
 app/Http/Controllers/ReservaController.php         |  23 +++-
 app/Models/Reserva.php                             |  44 +++++++
 app/Providers/AppServiceProvider.php               |  14 +++
 app/Services/Adapters/PasarelaPagoAdapter.php      |  37 ++++++
 app/Services/CalculadoraDevolucionEstandar.php     | 140 ++-------------------
 app/Services/ConvertidorTrm.php                    |  24 ++++
 .../Strategies/CombustibleCargoStrategy.php        |  41 ++++++
 .../Devolucion/Strategies/DanoCargoStrategy.php    |  40 ++++++
 .../Strategies/KilometrajeCargoStrategy.php        |  53 ++++++++
 .../Devolucion/Strategies/RetrasoCargoStrategy.php |  42 +++++++
 .../ExternalPaymentSdk/BankPaymentGateway.php      |  26 ++++
 .../TarifaServiceEstandar.php}                     |  30 ++---
 app/States/Reserva/AbstractReservaState.php        |  37 ++++++
 app/States/Reserva/CanceladaState.php              |   7 ++
 app/States/Reserva/ConfirmadaState.php             |  21 ++++
 app/States/Reserva/EntregadaState.php              |  16 +++
 app/States/Reserva/FacturadaState.php              |  16 +++
 app/States/Reserva/PagadaState.php                 |   7 ++
 app/States/Reserva/PendienteState.php              |  21 ++++
 config/servicios.php                               |  18 ++-
 resources/js/layouts/AppLayout.vue                 |   4 +-
 resources/js/pages/Devoluciones/Create.vue         |  35 +++++-
 resources/js/pages/Reservas/Index.vue              | 136 ++++++++++++++++++++
 resources/js/types/reservas.ts                     |  11 ++
 routes/web.php                                     |  21 ++--
 .../Actions/TransicionesReservaActionTest.php      |  36 ++++++
 tests/Feature/Adapters/PasarelaPagoAdapterTest.php |  43 +++++++
 tests/Feature/ApplicationBehaviorCoverageTest.php  |  27 ++--
 tests/Feature/ContractRoutesTest.php               |  11 +-
 .../Devolucion/CargoDevolucionStrategyTest.php     |  69 ++++++++++
 tests/Feature/DevolucionIntegrationTest.php        |  74 ++++++++++-
 tests/Feature/EntregaIntegrationTest.php           |   4 +
 tests/Feature/FacturaHtmlTest.php                  |   1 +
 .../Feature/Http/Controllers/ReservaIndexTest.php  |  81 ++++++++++++
 tests/Feature/MvpSmokeTest.php                     |  20 ++-
 tests/Feature/PagoTest.php                         |   4 +
 tests/Feature/RutasOperativasAutorizadasTest.php   |  45 +++++++
 tests/Feature/States/ReservaStateTest.php          |  61 +++++++++
 tests/Pest.php                                     |   9 +-
 tests/Unit/Services/ConvertidorTrmTest.php         |  25 ++++
 49 files changed, 1357 insertions(+), 208 deletions(-)
```

# 8. Historial completo

Comando: `git log --format='%h %an %ad %s' --date=short 0a2dced`

```
0a2dced Santiago Molano 2026-10-18 Merge pull request #61 from molxno/renta-vehiculos
7359315 molxno 2026-10-18 Merge branch main into qa to resolve promotion conflicts.
cc11b27 Santiago Molano 2026-10-18 Release Entrega 2 - Patrones GoF, SOLID y Demo Fixes
8aef1ac molxno 2026-10-18 Merge branch qa into dev to resolve promotion conflicts.
05f4684 molxno 2026-10-18 Aplicar Strategy, State y Adapter, extraer TRM y cerrar huecos de la demo.
9320450 Santiago Molano 2026-10-18 Promoción a main: detalle de reserva y sede en el hero (#59)
ec798a1 Santiago Molano 2026-10-18 Alinear el detalle de reserva con Figma y mostrar la sede de recogida en el hero. (#57)
cd32e3c Santiago Molano 2026-10-18 PR Final Sprint 1 MVP - Cierre SCRUM-44 Formulario Reserva QR (#56)
2d4403e Santiago Molano 2026-10-18 Promoción a main: SCRUM-44, SCRUM-45, SCRUM-58 (#47)
cc69733 Santiago Molano 2026-10-18 Promoción a QA: SCRUM-45 (#48)
0613995 Emanuel Atencia 2026-10-18 Feature/scrum 45 trm tarifa base (#45)
9bce74e Santiago Molano 2026-10-17 Promoción a QA: SCRUM-44, SCRUM-58 (#46)
c22398c Santiago Molano 2026-10-17 SCRUM-58: alinear el registro con el split screen de Figma (#44)
519a824 Emanuel Atencia 2026-10-18 SCRUM-44: crear formulario de reserva y mostrar QR (#43)
6ebfaf6 Santiago Molano 2026-10-17 Promoción a QA: SCRUM-42, SCRUM-43
80bdef1 molxno 2026-10-17 Promoción a QA: SCRUM-42, SCRUM-43
f5fb4c3 Emanuel Atencia 2026-10-18 SCRUM-42: crear y confirmar reservas con QR (#40)
d856d44 Santiago Molano 2026-10-17 PR Final Sprint 1 MVP - Cierre de Flujo Completo (#38)
4a261d8 Santiago Molano 2026-10-17 SCRUM-39: completar el humo HTTP del flujo MVP (#37)
8b36b6c Santiago Molano 2026-10-17 Promoción a QA: SCRUM-40, SCRUM-39 (#36)
1289674 Emanuel Atencia 2026-10-18 SCRUM-40: implementar disponibilidad por fechas (#33)
a5b5ebc Santiago Molano 2026-10-17 SCRUM-51: documentar el recorrido de demo del MVP (#34)
88e2f31 Santiago Molano 2026-10-17 Promoción a QA: SCRUM-37, SCRUM-51 (#35)
03026e5 Santiago Molano 2026-10-17 SCRUM-37: publicar el catálogo Vue y fijar el layout a pantalla completa (#32)
06a388e Santiago Molano 2026-10-17 Promoción a QA: SCRUM-29, 30, 31, 32, 33, 34, 35, 36, 38, 41, 47, 54 (#31)
8037855 Emanuel Atencia 2026-10-18 Feature/scrum 30 exportar pantallas y tokens de diseño (#27)
f1b9cf2 Santiago Molano 2026-10-17 Evitar el TypeError de SSR al resolver login desde la ruta nombrada, no desde el diccionario de SesionController::create. (#30)
004d8fa Santiago Molano 2026-10-17 SCRUM-56: alinear el login con el split screen de Figma (#29)
cf58cf2 Santiago Molano 2026-10-17 SCRUM-54: reemplazar el panel operativo de / por la landing pública RENT-GO (#28)
0307197 Santiago Molano 2026-10-17 SCRUM-32, SCRUM-34, SCRUM-38: pasarela falsa, reintento y factura HTML (#26)
3e71dcc Santiago Molano 2026-10-17 SCRUM-31: habilitar login, registro y navegación por rol (#24)
c1a91a4 Santiago Molano 2026-10-17 SCRUM-36, SCRUM-35, SCRUM-33, SCRUM-41: registrar entrega con licencia, acta, contrato y anticipo (#25)
95c09df Santiago Molano 2026-10-17 SCRUM-29: calcular el costo final de la devolución y mostrar la vista (#23)
909e63c Santiago Molano 2026-10-16 Promoción a QA: SCRUM-48, SCRUM-49 (#22)
ce3a8e3 Santiago Molano 2026-10-16 Promoción a QA: SCRUM-22, 23, 24, 25, 26, 27, 28 (#16) (#21)
9f50250 Santiago Molano 2026-10-16 SCRUM-49: persistir devolución y rechazar duplicados (#19)
4638cfa Santiago Molano 2026-10-16 SCRUM-48: verificar flujo MVP integrado (#18)
791e2e2 dependabot[bot] 2026-10-16 Bump the github-actions group with 2 updates (#17)
75f1db3 Santiago Molano 2026-10-15 Promoción a QA: SCRUM-22, 23, 24, 25, 26, 27, 28 (#16)
5590a0e Santiago Molano 2026-10-14 SCRUM-27: documentar README y agentes de IA (#15)
b1878ed Santiago Molano 2026-10-14 SCRUM-27: hacer reproducible el onboarding en Windows (#14)
b74fc1c Santiago Molano 2026-10-14 SCRUM-27: checks de seguridad, deploy y arquitectura (#13)
8fa4c34 Santiago Molano 2026-10-14 SCRUM-27: medir y elevar cobertura en CI (#12)
9e2b501 Santiago Molano 2026-10-14 SCRUM-27: estándar de código, flujo de trabajo y reglas para agentes (#11)
fbb864b Santiago Molano 2026-10-14 SCRUM-28: preparar despliegue Docker en Render (#10)
0c46e21 Santiago Molano 2026-10-14 SCRUM-26: cubrir contratos y flujo completo (#9)
e71a22b Santiago Molano 2026-10-14 SCRUM-25: ajustar contratos y roles del Gate (#8)
b86a117 Santiago Molano 2026-10-14 SCRUM-25: crear esqueleto de interfaz (#7)
f4aed01 Santiago Molano 2026-10-14 SCRUM-25: publicar rutas y contratos HTTP (#6)
59d1061 Santiago Molano 2026-10-14 SCRUM-27: agregar CODEOWNERS (#5)
7420ef5 Santiago Molano 2026-10-13 SCRUM-24/23: seeders y fixtures reproducibles (#4)
e01675b Santiago Molano 2026-10-13 SCRUM-27: guardas de calidad local y CI (#3)
6d819b2 Santiago Molano 2026-10-13 SCRUM-25: publicar contratos base (#1)
1cc7089 Santiago Molano 2026-10-13 SCRUM-22: preparar dominio base (#2)
5fc90e1 molxno 2026-10-08 first commit
```

# 9. Tests de States, Strategies y PasarelaPagoAdapter

Comando: `git grep -n -E "^[[:space:]]*(it|test)\(" 0a2dced -- tests | grep -i -E "state|estado|strategy|cargo|adapter|pasarela|transicion"`

```
0a2dced:tests/Feature/Actions/TransicionesReservaActionTest.php:12:it('rejects delivering a reservation that is not confirmed from the action', function () {
0a2dced:tests/Feature/Actions/TransicionesReservaActionTest.php:25:it('rejects returning a reservation that was not delivered from the action', function () {
0a2dced:tests/Feature/Adapters/PasarelaPagoAdapterTest.php:10:it('translates an approved bank transaction into the payment port', function () {
0a2dced:tests/Feature/Adapters/PasarelaPagoAdapterTest.php:21:it('translates a declined bank transaction into the payment port', function () {
0a2dced:tests/Feature/Adapters/PasarelaPagoAdapterTest.php:33:it('keeps the original fake gateway for isolated bindings', function () {
0a2dced:tests/Feature/Devolucion/CargoDevolucionStrategyTest.php:18:it('is composed of the four return charge strategies', function () {
0a2dced:tests/Feature/Devolucion/CargoDevolucionStrategyTest.php:29:it('lets each strategy calculate its own charge line independently', function () {
0a2dced:tests/Feature/DevolucionIntegrationTest.php:24:    it('stores the return record and advances the reservation state', function () {
0a2dced:tests/Feature/DevolucionIntegrationTest.php:97:    it('rejects the return of a reservation that was not delivered', function (ReservaEstado $estado) {
0a2dced:tests/Feature/EntregaIntegrationTest.php:119:    it('rejects the delivery of a reservation that is not confirmed', function (ReservaEstado $estado) {
0a2dced:tests/Feature/States/ReservaStateTest.php:9:it('allows the legal reservation transitions', function (ReservaEstado $desde, string $operacion, ReservaEstado $hacia) {
0a2dced:tests/Feature/States/ReservaStateTest.php:24:it('rejects an illegal reservation transition', function (ReservaEstado $estado, string $operacion) {
0a2dced:tests/Feature/States/ReservaStateTest.php:58:it('describes an unknown transition with the current status', function () {
```

# 10. Títulos de docs/flujo-de-trabajo.md y AGENTS.md

## docs/flujo-de-trabajo.md

Comando: `git show 0a2dced:docs/flujo-de-trabajo.md | grep "^#"`

```
# Flujo de trabajo
## 0. Preparar
## 1. Leer el ticket
## 2. Crear la rama
## 3. Plan
## 4. UI
## 5. Implementar
## 6. Verificar
## 7. HANDOFF para prueba local
## 8. Commit y push
## 9. Abrir el PR
## 10. Revisión
## 11. Promoción a QA
## 12. Promoción a main
## 13. Cierre
## Si algo falla
```

## AGENTS.md

Comando: `git show 0a2dced:AGENTS.md | grep "^#"`

```
# Laravel Boost Guidelines
## Foundational Context
## Skills Activation
## Conventions
## Verification Scripts
## Application Structure & Architecture
## Frontend Bundling
## Documentation Files
# Laravel Boost
## Tools
## Searching Documentation (IMPORTANT)
### Search Syntax
## Project Rules
## Artisan
## Tinker
# PHP
# Deployment
# Inertia
# Inertia v3
# Do Things the Laravel Way
### Model Creation
## APIs & Eloquent Resources
## URL Generation
## Testing
# Laravel Wayfinder
# Laravel Pint Code Formatter
# Pest
## Running Tests
# Inertia + Vue
# Guía única para agentes
## Disparador y pausas
## Límites duros
## Herramientas
```
