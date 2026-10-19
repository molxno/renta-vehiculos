# Evidencia SOLID y patrones — main 0a2dced

SHA: `0a2dced533ee0d344565f82ca844696579f46f75`
Extracción en solo lectura. Rama de trabajo no modificada.

# A) SOLID — archivos completos

## app/Contracts/TarifaService.php

Comando: `git show 0a2dced:app/Contracts/TarifaService.php | cat -n`

```
     1	<?php
     2
     3	declare(strict_types=1);
     4
     5	namespace App\Contracts;
     6
     7	use DateTimeInterface;
     8
     9	interface TarifaService
    10	{
    11	    /**
    12	     * @return array{
    13	     *     subtotal_centavos: int,
    14	     *     descuento_centavos: int,
    15	     *     impuestos_centavos: int,
    16	     *     total_centavos: int,
    17	     *     moneda: string
    18	     * }
    19	     */
    20	    public function cotizar(
    21	        int $vehiculoId,
    22	        DateTimeInterface $inicio,
    23	        DateTimeInterface $fin,
    24	        ?int $convenioId = null,
    25	        ?string $moneda = null,
    26	    ): array;
    27	}
```

## app/Services/TarifaServiceEstandar.php

Comando: `git show 0a2dced:app/Services/TarifaServiceEstandar.php | cat -n`

```
     1	<?php
     2
     3	declare(strict_types=1);
     4
     5	namespace App\Services;
     6
     7	use App\Contracts\TarifaService;
     8	use App\Models\Convenio;
     9	use App\Models\Tarifa;
    10	use App\Models\Trm;
    11	use App\Models\Vehiculo;
    12	use DateTimeInterface;
    13	use Illuminate\Database\Eloquent\Builder;
    14	use Illuminate\Validation\ValidationException;
    15
    16	final class TarifaServiceEstandar implements TarifaService
    17	{
    18	    private const int IMPUESTO_PORCENTAJE = 19;
    19
    20	    public function __construct(private ConvertidorTrm $convertidor) {}
    21
    22	    public function cotizar(
    23	        int $vehiculoId,
    24	        DateTimeInterface $inicio,
    25	        DateTimeInterface $fin,
    26	        ?int $convenioId = null,
    27	        ?string $moneda = null,
    28	    ): array {
    29	        $moneda = strtoupper($moneda ?? 'COP');
    30
    31	        if (! in_array($moneda, ['COP', 'USD'], true)) {
    32	            throw ValidationException::withMessages([
    33	                'moneda' => 'La moneda seleccionada no es válida.',
    34	            ]);
    35	        }
    36
    37	        $duracionSegundos = $fin->getTimestamp() - $inicio->getTimestamp();
    38
    39	        if ($duracionSegundos <= 0) {
    40	            throw ValidationException::withMessages([
    41	                'fin' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
    42	            ]);
    43	        }
    44
    45	        $vehiculo = Vehiculo::query()->findOrFail($vehiculoId);
    46	        $tarifa = $this->tarifaVigente($vehiculo, $inicio);
    47
    48	        if ($tarifa === null) {
    49	            throw ValidationException::withMessages([
    50	                'vehiculo_id' => 'No hay una tarifa vigente para el vehículo en la fecha seleccionada.',
    51	            ]);
    52	        }
    53
    54	        $dias = intdiv($duracionSegundos + 86399, 86400);
    55	        $subtotalCentavos = $tarifa->valor_diario_centavos * $dias;
    56	        $descuentoPorcentaje = 0;
    57
    58	        if ($convenioId !== null) {
    59	            $convenio = Convenio::query()
    60	                ->whereKey($convenioId)
    61	                ->where('activo', true)
    62	                ->first();
    63
    64	            if ($convenio === null) {
    65	                throw ValidationException::withMessages([
    66	                    'convenio_id' => 'El convenio seleccionado no está activo.',
    67	                ]);
    68	            }
    69
    70	            if ($convenio->descuento_porcentaje > 100) {
    71	                throw ValidationException::withMessages([
    72	                    'convenio_id' => 'El descuento del convenio no es válido.',
    73	                ]);
    74	            }
    75
    76	            $descuentoPorcentaje = $convenio->descuento_porcentaje;
    77	        }
    78
    79	        $descuentoCentavos = intdiv(($subtotalCentavos * $descuentoPorcentaje) + 50, 100);
    80	        $impuestosCentavos = intdiv(($subtotalCentavos * self::IMPUESTO_PORCENTAJE) + 50, 100);
    81	        $totalCentavos = $subtotalCentavos - $descuentoCentavos + $impuestosCentavos;
    82
    83	        if ($moneda === 'USD') {
    84	            $trm = Trm::query()->vigente()->first();
    85
    86	            if ($trm === null) {
    87	                throw ValidationException::withMessages([
    88	                    'moneda' => 'No hay una TRM vigente para cotizar en USD.',
    89	                ]);
    90	            }
    91
    92	            $trmEscalada = $this->convertidor->escalarDecimal($trm->valor);
    93
    94	            if ($trmEscalada <= 0) {
    95	                throw ValidationException::withMessages([
    96	                    'moneda' => 'No hay una TRM válida para cotizar en USD.',
    97	                ]);
    98	            }
    99
   100	            $subtotalCentavos = $this->convertidor->convertirACentavosUsd($subtotalCentavos, $trmEscalada);
   101	            $descuentoCentavos = $this->convertidor->convertirACentavosUsd($descuentoCentavos, $trmEscalada);
   102	            $impuestosCentavos = $this->convertidor->convertirACentavosUsd($impuestosCentavos, $trmEscalada);
   103	            $totalCentavos = $subtotalCentavos - $descuentoCentavos + $impuestosCentavos;
   104	        }
   105
   106	        return [
   107	            'subtotal_centavos' => $subtotalCentavos,
   108	            'descuento_centavos' => $descuentoCentavos,
   109	            'impuestos_centavos' => $impuestosCentavos,
   110	            'total_centavos' => $totalCentavos,
   111	            'moneda' => $moneda,
   112	        ];
   113	    }
   114
   115	    private function tarifaVigente(Vehiculo $vehiculo, DateTimeInterface $inicio): ?Tarifa
   116	    {
   117	        $fechaInicio = $inicio->format('Y-m-d');
   118	        $vigencia = static function (Builder $query) use ($fechaInicio): void {
   119	            $query->whereDate('vigente_desde', '<=', $fechaInicio)
   120	                ->where(static function (Builder $query) use ($fechaInicio): void {
   121	                    $query->whereNull('vigente_hasta')
   122	                        ->orWhereDate('vigente_hasta', '>=', $fechaInicio);
   123	                })
   124	                ->orderByDesc('vigente_desde')
   125	                ->orderByDesc('id');
   126	        };
   127
   128	        return Tarifa::query()
   129	            ->where('vehiculo_id', $vehiculo->id)
   130	            ->tap($vigencia)
   131	            ->first()
   132	            ?? Tarifa::query()
   133	                ->whereNull('vehiculo_id')
   134	                ->where('tipo_vehiculo', $vehiculo->tipo)
   135	                ->tap($vigencia)
   136	                ->first();
   137	    }
   138	}
```

## app/Services/ConvertidorTrm.php

Comando: `git show 0a2dced:app/Services/ConvertidorTrm.php | cat -n`

```
     1	<?php
     2
     3	declare(strict_types=1);
     4
     5	namespace App\Services;
     6
     7	final class ConvertidorTrm
     8	{
     9	    public function escalarDecimal(string $valor): int
    10	    {
    11	        [$entero, $decimal] = array_pad(explode('.', $valor, 2), 2, '');
    12
    13	        return ((int) $entero * 10000) + (int) str_pad(substr($decimal, 0, 4), 4, '0');
    14	    }
    15
    16	    public function convertirACentavosUsd(int $centavosCop, int $trmEscalada): int
    17	    {
    18	        $unidadesEnteras = intdiv($centavosCop, $trmEscalada);
    19	        $resto = $centavosCop % $trmEscalada;
    20	        $centavosFraccionarios = intdiv(($resto * 10000) + intdiv($trmEscalada, 2), $trmEscalada);
    21
    22	        return ($unidadesEnteras * 10000) + $centavosFraccionarios;
    23	    }
    24	}
```

## app/Models/Trm.php

Comando: `git show 0a2dced:app/Models/Trm.php | cat -n`

```
     1	<?php
     2
     3	declare(strict_types=1);
     4
     5	namespace App\Models;
     6
     7	use Database\Factories\TrmFactory;
     8	use Illuminate\Database\Eloquent\Builder;
     9	use Illuminate\Database\Eloquent\Factories\HasFactory;
    10	use Illuminate\Database\Eloquent\Model;
    11	use Illuminate\Support\Carbon;
    12	use Illuminate\Support\Facades\Date;
    13
    14	/**
    15	 * @property Carbon $fecha
    16	 * @property string $valor
    17	 *
    18	 * @method static Builder<static> vigente()
    19	 */
    20	final class Trm extends Model
    21	{
    22	    /** @use HasFactory<TrmFactory> */
    23	    use HasFactory;
    24
    25	    protected $table = 'trm';
    26
    27	    protected $fillable = ['fecha', 'valor'];
    28
    29	    protected function casts(): array
    30	    {
    31	        return ['fecha' => 'date', 'valor' => 'decimal:4'];
    32	    }
    33
    34	    /** @param  Builder<static>  $query */
    35	    public function scopeVigente(Builder $query): void
    36	    {
    37	        $query
    38	            ->whereDate('fecha', '<=', Date::today())
    39	            ->orderByDesc('fecha')
    40	            ->orderByDesc('id');
    41	    }
    42	}
```

## config/servicios.php

Comando: `git show 0a2dced:config/servicios.php | cat -n`

```
     1	<?php
     2
     3	use App\Contracts\CalculadoraDevolucion;
     4	use App\Contracts\DisponibilidadService;
     5	use App\Contracts\GeneradorQr;
     6	use App\Contracts\PasarelaPago;
     7	use App\Contracts\TarifaService;
     8	use App\Fakes\FakeDisponibilidadService;
     9	use App\Fakes\FakeGeneradorQr;
    10	use App\Services\Adapters\PasarelaPagoAdapter;
    11	use App\Services\CalculadoraDevolucionEstandar;
    12	use App\Services\Devolucion\Strategies\CombustibleCargoStrategy;
    13	use App\Services\Devolucion\Strategies\DanoCargoStrategy;
    14	use App\Services\Devolucion\Strategies\KilometrajeCargoStrategy;
    15	use App\Services\Devolucion\Strategies\RetrasoCargoStrategy;
    16	use App\Services\TarifaServiceEstandar;
    17
    18	return [
    19	    'bindings' => [
    20	        CalculadoraDevolucion::class => CalculadoraDevolucionEstandar::class,
    21	        DisponibilidadService::class => FakeDisponibilidadService::class,
    22	        GeneradorQr::class => FakeGeneradorQr::class,
    23	        PasarelaPago::class => PasarelaPagoAdapter::class,
    24	        TarifaService::class => TarifaServiceEstandar::class,
    25	    ],
    26	    'cargos_devolucion' => [
    27	        RetrasoCargoStrategy::class,
    28	        KilometrajeCargoStrategy::class,
    29	        CombustibleCargoStrategy::class,
    30	        DanoCargoStrategy::class,
    31	    ],
    32	];
```

# B) SOLID — uso y pruebas

## 6. Uso de TarifaService y ConvertidorTrm

Comando: `git grep -n "TarifaService\|ConvertidorTrm" 0a2dced -- app tests config routes`

```
0a2dced:app/Actions/Reservas/CrearReserva.php:8:use App\Contracts\TarifaService;
0a2dced:app/Actions/Reservas/CrearReserva.php:21:        private TarifaService $tarifa,
0a2dced:app/Contracts/TarifaService.php:9:interface TarifaService
0a2dced:app/Http/Controllers/ReservaController.php:11:use App\Contracts\TarifaService;
0a2dced:app/Http/Controllers/ReservaController.php:44:        TarifaService $tarifa,
0a2dced:app/Services/ConvertidorTrm.php:7:final class ConvertidorTrm
0a2dced:app/Services/TarifaServiceEstandar.php:7:use App\Contracts\TarifaService;
0a2dced:app/Services/TarifaServiceEstandar.php:16:final class TarifaServiceEstandar implements TarifaService
0a2dced:app/Services/TarifaServiceEstandar.php:20:    public function __construct(private ConvertidorTrm $convertidor) {}
0a2dced:config/servicios.php:7:use App\Contracts\TarifaService;
0a2dced:config/servicios.php:16:use App\Services\TarifaServiceEstandar;
0a2dced:config/servicios.php:24:        TarifaService::class => TarifaServiceEstandar::class,
0a2dced:tests/Feature/ContractRoutesTest.php:9:use App\Contracts\TarifaService;
0a2dced:tests/Feature/ContractRoutesTest.php:16:use App\Services\TarifaServiceEstandar;
0a2dced:tests/Feature/ContractRoutesTest.php:50:        ->and(app(TarifaService::class))->toBeInstanceOf(TarifaServiceEstandar::class)
0a2dced:tests/Feature/Fakes/FakeTarifaServiceTest.php:5:use App\Contracts\TarifaService;
0a2dced:tests/Feature/Fakes/FakeTarifaServiceTest.php:26:    $cotizacion = app(TarifaService::class)->cotizar(
0a2dced:tests/Feature/Fakes/FakeTarifaServiceTest.php:60:    $cotizacion = app(TarifaService::class)->cotizar(
0a2dced:tests/Feature/Fakes/FakeTarifaServiceTest.php:86:    $cotizacion = app(TarifaService::class)->cotizar(
0a2dced:tests/Feature/Fakes/FakeTarifaServiceTest.php:111:    $cotizacion = app(TarifaService::class)->cotizar(
0a2dced:tests/Feature/Fakes/FakeTarifaServiceTest.php:129:    $cotizacion = app(TarifaService::class)->cotizar(
0a2dced:tests/Feature/Fakes/FakeTarifaServiceTest.php:147:    expect(fn () => app(TarifaService::class)->cotizar(
0a2dced:tests/Feature/Fakes/FakeTarifaServiceTest.php:167:    expect(fn () => app(TarifaService::class)->cotizar(
0a2dced:tests/Feature/Fakes/FakeTarifaServiceTest.php:189:    expect(fn () => app(TarifaService::class)->cotizar(
0a2dced:tests/Feature/Fakes/FakeTarifaServiceTest.php:207:    expect(fn () => app(TarifaService::class)->cotizar(
0a2dced:tests/Feature/Fakes/FakeTarifaServiceTest.php:224:    expect(fn () => app(TarifaService::class)->cotizar(
0a2dced:tests/Feature/Fakes/FakeTarifaServiceTest.php:235:    expect(fn () => app(TarifaService::class)->cotizar(
0a2dced:tests/Feature/ReservationFlowTest.php:7:use App\Contracts\TarifaService;
0a2dced:tests/Feature/ReservationFlowTest.php:45:    $quote = app(TarifaService::class)->cotizar(
0a2dced:tests/Unit/Services/ConvertidorTrmTest.php:5:use App\Services\ConvertidorTrm;
0a2dced:tests/Unit/Services/ConvertidorTrmTest.php:9:    $convertidor = new ConvertidorTrm;
0a2dced:tests/Unit/Services/ConvertidorTrmTest.php:16:    expect((new ConvertidorTrm)->convertirACentavosUsd(30000000, 42002500))->toBe(7142);
```

## 7. Archivos de prueba que cubren tarifa o TRM

Comando: `git grep -l -i -E "tarifa|trm|ConvertidorTrm|TarifaService" 0a2dced -- tests`

```
0a2dced:tests/Feature/ApplicationBehaviorCoverageTest.php
0a2dced:tests/Feature/ContractRoutesTest.php
0a2dced:tests/Feature/Fakes/FakeTarifaServiceTest.php
0a2dced:tests/Feature/HomeTest.php
0a2dced:tests/Feature/Http/Controllers/CatalogoControllerTest.php
0a2dced:tests/Feature/Http/Controllers/ReservaControllerTest.php
0a2dced:tests/Feature/Http/Controllers/TrmControllerTest.php
0a2dced:tests/Feature/MvpSmokeTest.php
0a2dced:tests/Feature/ReservationFlowTest.php
0a2dced:tests/Unit/Services/ConvertidorTrmTest.php
```

Nombres de tests (`it(` / `test(`) por archivo:

Comando: `git grep -n -E "^[[:space:]]*(it|test)\(" 0a2dced -- <archivo>`

### tests/Feature/ApplicationBehaviorCoverageTest.php

```
0a2dced:tests/Feature/ApplicationBehaviorCoverageTest.php:40:test('all documented pages expose their Inertia contracts', function () {
0a2dced:tests/Feature/ApplicationBehaviorCoverageTest.php:82:test('all documented write routes validate and redirect', function () {
0a2dced:tests/Feature/ApplicationBehaviorCoverageTest.php:138:test('requests expose authorization and validation contracts', function () {
0a2dced:tests/Feature/ApplicationBehaviorCoverageTest.php:178:test('models expose their casts and relationships', function () {
0a2dced:tests/Feature/ApplicationBehaviorCoverageTest.php:223:test('enums preserve the domain vocabulary', function () {
0a2dced:tests/Feature/ApplicationBehaviorCoverageTest.php:233:test('production provider defaults are configured', function () {
```

### tests/Feature/ContractRoutesTest.php

```
0a2dced:tests/Feature/ContractRoutesTest.php:19:test('contract routes expose the documented pages and redirects', function () {
0a2dced:tests/Feature/ContractRoutesTest.php:48:test('application contracts resolve to the phase fakes', function () {
0a2dced:tests/Feature/ContractRoutesTest.php:56:test('reservation pages expose their documented Inertia props', function () {
```

### tests/Feature/Fakes/FakeTarifaServiceTest.php

```
0a2dced:tests/Feature/Fakes/FakeTarifaServiceTest.php:13:it('calculates COP totals from the vehicle tariff and active agreement', function () {
0a2dced:tests/Feature/Fakes/FakeTarifaServiceTest.php:42:it('converts a quote to USD using the latest persisted TRM that is not in the future', function () {
0a2dced:tests/Feature/Fakes/FakeTarifaServiceTest.php:77:it('uses a current type tariff when the vehicle has no specific tariff', function () {
0a2dced:tests/Feature/Fakes/FakeTarifaServiceTest.php:96:it('prefers the valid vehicle tariff over its vehicle-type tariff', function () {
0a2dced:tests/Feature/Fakes/FakeTarifaServiceTest.php:120:it('charges an additional full day for a rental that extends beyond a day', function () {
0a2dced:tests/Feature/Fakes/FakeTarifaServiceTest.php:139:it('rejects a quote when no tariff is valid on the rental start date', function () {
0a2dced:tests/Feature/Fakes/FakeTarifaServiceTest.php:154:it('rejects a USD quote when there is no persisted TRM in effect today', function () {
0a2dced:tests/Feature/Fakes/FakeTarifaServiceTest.php:176:it('rejects a USD quote when the persisted TRM is zero', function () {
0a2dced:tests/Feature/Fakes/FakeTarifaServiceTest.php:198:it('rejects an inactive agreement instead of applying its discount', function () {
0a2dced:tests/Feature/Fakes/FakeTarifaServiceTest.php:215:it('rejects a convention discount above one hundred percent', function () {
0a2dced:tests/Feature/Fakes/FakeTarifaServiceTest.php:232:it('rejects an unsupported currency and an invalid rental interval', function (string $currency, string $start, string $end) {
```

### tests/Feature/HomeTest.php

```
0a2dced:tests/Feature/HomeTest.php:19:    it('renders the storefront home instead of the operational panel', function () {
0a2dced:tests/Feature/HomeTest.php:33:    it('exposes featured available vehicles with their daily rate', function () {
0a2dced:tests/Feature/HomeTest.php:67:    it('keeps the public landing for an authenticated coordinator', function () {
```

### tests/Feature/Http/Controllers/CatalogoControllerTest.php

```
0a2dced:tests/Feature/Http/Controllers/CatalogoControllerTest.php:20:    it('renders the public catalog from seeded vehicles, categories and rates', function () {
0a2dced:tests/Feature/Http/Controllers/CatalogoControllerTest.php:47:    it('filters vehicles by category and keeps every category in the sidebar', function () {
0a2dced:tests/Feature/Http/Controllers/CatalogoControllerTest.php:74:    it('excludes vehicles that the availability service marks as unavailable', function () {
0a2dced:tests/Feature/Http/Controllers/CatalogoControllerTest.php:120:    it('keeps vehicles without a current rate in the listing', function () {
0a2dced:tests/Feature/Http/Controllers/CatalogoControllerTest.php:135:    it('rejects a return date that is not after the pickup date', function () {
0a2dced:tests/Feature/Http/Controllers/CatalogoControllerTest.php:147:    it('rejects a pickup date without a return date', function () {
```

### tests/Feature/Http/Controllers/ReservaControllerTest.php

```
0a2dced:tests/Feature/Http/Controllers/ReservaControllerTest.php:17:    it('persists a pending reservation for the authenticated client with a server-calculated quote', function () {
0a2dced:tests/Feature/Http/Controllers/ReservaControllerTest.php:52:    it('applies the agreement discount when creating a reservation', function () {
0a2dced:tests/Feature/Http/Controllers/ReservaControllerTest.php:82:    it('persists a reservation quote in USD using the current saved TRM', function () {
0a2dced:tests/Feature/Http/Controllers/ReservaControllerTest.php:112:    it('shows the current TRM on the reservation form and ignores a future rate', function () {
0a2dced:tests/Feature/Http/Controllers/ReservaControllerTest.php:135:    it('rejects an unsupported reservation currency before creating a reservation', function () {
0a2dced:tests/Feature/Http/Controllers/ReservaControllerTest.php:149:    it('redirects guests to sign in without creating a reservation', function () {
0a2dced:tests/Feature/Http/Controllers/ReservaControllerTest.php:161:    it('rejects invalid reservation dates without persisting a reservation', function () {
0a2dced:tests/Feature/Http/Controllers/ReservaControllerTest.php:176:    it('rejects a vehicle that does not exist', function () {
0a2dced:tests/Feature/Http/Controllers/ReservaControllerTest.php:190:    it('forbids non-client roles from creating reservations', function () {
0a2dced:tests/Feature/Http/Controllers/ReservaControllerTest.php:203:    it('rejects a reservation when the availability contract reports an occupied vehicle', function () {
0a2dced:tests/Feature/Http/Controllers/ReservaControllerTest.php:235:    it('confirms a pending reservation and assigns its unique QR code', function () {
0a2dced:tests/Feature/Http/Controllers/ReservaControllerTest.php:249:    it('assigns a distinct QR code to every confirmed reservation', function () {
0a2dced:tests/Feature/Http/Controllers/ReservaControllerTest.php:265:    it('does not confirm another client reservation', function () {
0a2dced:tests/Feature/Http/Controllers/ReservaControllerTest.php:278:    it('redirects guests to sign in when confirming a reservation', function () {
0a2dced:tests/Feature/Http/Controllers/ReservaControllerTest.php:288:    it('locates the reservation from its QR code', function () {
0a2dced:tests/Feature/Http/Controllers/ReservaControllerTest.php:298:    it('returns not found for an unknown QR code', function () {
0a2dced:tests/Feature/Http/Controllers/ReservaControllerTest.php:303:    it('does not confirm a reservation more than once', function () {
```

### tests/Feature/Http/Controllers/TrmControllerTest.php

```
0a2dced:tests/Feature/Http/Controllers/TrmControllerTest.php:19:    it('renders the current exchange rate for an administrator', function () {
0a2dced:tests/Feature/Http/Controllers/TrmControllerTest.php:36:    it('renders the exchange rate form when no rate has been saved', function () {
0a2dced:tests/Feature/Http/Controllers/TrmControllerTest.php:47:    it('forbids a customer from viewing the exchange rate form', function () {
0a2dced:tests/Feature/Http/Controllers/TrmControllerTest.php:55:    it('redirects a guest to sign in before viewing the exchange rate form', function () {
0a2dced:tests/Feature/Http/Controllers/TrmControllerTest.php:60:    it('persists the administrator rate for its effective date', function () {
0a2dced:tests/Feature/Http/Controllers/TrmControllerTest.php:75:    it('updates the existing rate instead of duplicating its effective date', function () {
0a2dced:tests/Feature/Http/Controllers/TrmControllerTest.php:95:    it('rejects invalid and future exchange rates without persisting them', function (array $data, string $field, string $message) {
0a2dced:tests/Feature/Http/Controllers/TrmControllerTest.php:127:    it('does not permit a non-administrator to change the exchange rate', function () {
```

### tests/Feature/MvpSmokeTest.php

```
0a2dced:tests/Feature/MvpSmokeTest.php:28:test('seeded MVP exposes the complete smoke flow', function () {
0a2dced:tests/Feature/MvpSmokeTest.php:88:test('completes the MVP HTTP smoke flow from register to invoice', function () {
0a2dced:tests/Feature/MvpSmokeTest.php:232:test('rejects creating a mock reservation when the vehicle is occupied', function () {
0a2dced:tests/Feature/MvpSmokeTest.php:259:test('shows a server quote and the persisted TRM for selected available dates', function () {
0a2dced:tests/Feature/MvpSmokeTest.php:282:test('requires a client session to open the reservation form', function () {
0a2dced:tests/Feature/MvpSmokeTest.php:287:test('forbids staff from opening the reservation form', function () {
0a2dced:tests/Feature/MvpSmokeTest.php:293:test('suggests other criteria when no vehicles are available to reserve', function () {
0a2dced:tests/Feature/MvpSmokeTest.php:305:test('rejects invalid dates when requesting a reservation quote', function () {
0a2dced:tests/Feature/MvpSmokeTest.php:323:test('creates a reservation for the authenticated client instead of a submitted owner', function () {
0a2dced:tests/Feature/MvpSmokeTest.php:341:test('requires a client session to create a reservation', function () {
0a2dced:tests/Feature/MvpSmokeTest.php:354:test('forbids staff from creating a client reservation', function () {
```

### tests/Feature/ReservationFlowTest.php

```
0a2dced:tests/Feature/ReservationFlowTest.php:22:test('completes the reservation delivery return and payment contract flow', function () {
```

### tests/Unit/Services/ConvertidorTrmTest.php

```
0a2dced:tests/Unit/Services/ConvertidorTrmTest.php:8:it('scales a TRM with and without a decimal part', function () {
0a2dced:tests/Unit/Services/ConvertidorTrmTest.php:15:it('converts COP cents to USD cents using a scaled TRM', function () {
0a2dced:tests/Unit/Services/ConvertidorTrmTest.php:19:it('approves a positive amount unless the card ends in 0000', function () {
```

# C) Evolución respecto a la versión previa

## 8. Historial --follow de cotización

Comando: `git log --oneline --follow 0a2dced -- app/Services/TarifaServiceEstandar.php app/Services/ConvertidorTrm.php app/Contracts/TarifaService.php`

```
fatal: --follow requires exactly one pathspec
```

El comando anterior falla porque `--follow` exige un solo path. Historial por archivo:

### app/Services/TarifaServiceEstandar.php

Comando: `git log --oneline --follow 0a2dced -- app/Services/TarifaServiceEstandar.php`

```
05f4684 Aplicar Strategy, State y Adapter, extraer TRM y cerrar huecos de la demo.
2d4403e Promoción a main: SCRUM-44, SCRUM-45, SCRUM-58 (#47)
cc69733 Promoción a QA: SCRUM-45 (#48)
0613995 Feature/scrum 45 trm tarifa base (#45)
75f1db3 Promoción a QA: SCRUM-22, 23, 24, 25, 26, 27, 28 (#16)
b74fc1c SCRUM-27: checks de seguridad, deploy y arquitectura (#13)
f4aed01 SCRUM-25: publicar rutas y contratos HTTP (#6)
```

### app/Services/ConvertidorTrm.php

Comando: `git log --oneline --follow 0a2dced -- app/Services/ConvertidorTrm.php`

```
05f4684 Aplicar Strategy, State y Adapter, extraer TRM y cerrar huecos de la demo.
```

### app/Contracts/TarifaService.php

Comando: `git log --oneline --follow 0a2dced -- app/Contracts/TarifaService.php`

```
2d4403e Promoción a main: SCRUM-44, SCRUM-45, SCRUM-58 (#47)
cc69733 Promoción a QA: SCRUM-45 (#48)
0613995 Feature/scrum 45 trm tarifa base (#45)
75f1db3 Promoción a QA: SCRUM-22, 23, 24, 25, 26, 27, 28 (#16)
b74fc1c SCRUM-27: checks de seguridad, deploy y arquitectura (#13)
6d819b2 SCRUM-25: publicar contratos base (#1)
```

## 9. Historial de app/Fakes/FakeTarifaService.php

Comando: `git log --oneline --follow -- app/Fakes/FakeTarifaService.php`

```
05f4684 Aplicar Strategy, State y Adapter, extraer TRM y cerrar huecos de la demo.
2d4403e Promoción a main: SCRUM-44, SCRUM-45, SCRUM-58 (#47)
cc69733 Promoción a QA: SCRUM-45 (#48)
0613995 Feature/scrum 45 trm tarifa base (#45)
75f1db3 Promoción a QA: SCRUM-22, 23, 24, 25, 26, 27, 28 (#16)
b74fc1c SCRUM-27: checks de seguridad, deploy y arquitectura (#13)
f4aed01 SCRUM-25: publicar rutas y contratos HTTP (#6)
```

Existencia en 0a2dced:

Comando: `git cat-file -e 0a2dced:app/Fakes/FakeTarifaService.php`

```
NO ENCONTRADO
fatal: path 'app/Fakes/FakeTarifaService.php' does not exist in '0a2dced'
```

Diff del archivo entre el commit anterior y 0a2dced:

Comando: `git diff 0a2dced^ 0a2dced -- app/Fakes/FakeTarifaService.php`

```
diff --git a/app/Fakes/FakeTarifaService.php b/app/Fakes/FakeTarifaService.php
deleted file mode 100644
index 9fd3d97..0000000
--- a/app/Fakes/FakeTarifaService.php
+++ /dev/null
@@ -1,152 +0,0 @@
-<?php
-
-declare(strict_types=1);
-
-namespace App\Fakes;
-
-use App\Contracts\TarifaService;
-use App\Models\Convenio;
-use App\Models\Tarifa;
-use App\Models\Trm;
-use App\Models\Vehiculo;
-use DateTimeInterface;
-use Illuminate\Database\Eloquent\Builder;
-use Illuminate\Validation\ValidationException;
-
-final class FakeTarifaService implements TarifaService
-{
-    private const int IMPUESTO_PORCENTAJE = 19;
-
-    public function cotizar(
-        int $vehiculoId,
-        DateTimeInterface $inicio,
-        DateTimeInterface $fin,
-        ?int $convenioId = null,
-        ?string $moneda = null,
-    ): array {
-        $moneda = strtoupper($moneda ?? 'COP');
-
-        if (! in_array($moneda, ['COP', 'USD'], true)) {
-            throw ValidationException::withMessages([
-                'moneda' => 'La moneda seleccionada no es válida.',
-            ]);
-        }
-
-        $duracionSegundos = $fin->getTimestamp() - $inicio->getTimestamp();
-
-        if ($duracionSegundos <= 0) {
-            throw ValidationException::withMessages([
-                'fin' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
-            ]);
-        }
-
-        $vehiculo = Vehiculo::query()->findOrFail($vehiculoId);
-        $tarifa = $this->tarifaVigente($vehiculo, $inicio);
-
-        if ($tarifa === null) {
-            throw ValidationException::withMessages([
-                'vehiculo_id' => 'No hay una tarifa vigente para el vehículo en la fecha seleccionada.',
-            ]);
-        }
-
-        $dias = intdiv($duracionSegundos + 86399, 86400);
-        $subtotalCentavos = $tarifa->valor_diario_centavos * $dias;
-        $descuentoPorcentaje = 0;
-
-        if ($convenioId !== null) {
-            $convenio = Convenio::query()
-                ->whereKey($convenioId)
-                ->where('activo', true)
-                ->first();
-
-            if ($convenio === null) {
-                throw ValidationException::withMessages([
-                    'convenio_id' => 'El convenio seleccionado no está activo.',
-                ]);
-            }
-
-            if ($convenio->descuento_porcentaje > 100) {
-                throw ValidationException::withMessages([
-                    'convenio_id' => 'El descuento del convenio no es válido.',
-                ]);
-            }
-
-            $descuentoPorcentaje = $convenio->descuento_porcentaje;
-        }
-
-        $descuentoCentavos = intdiv(($subtotalCentavos * $descuentoPorcentaje) + 50, 100);
-        $impuestosCentavos = intdiv(($subtotalCentavos * self::IMPUESTO_PORCENTAJE) + 50, 100);
-        $totalCentavos = $subtotalCentavos - $descuentoCentavos + $impuestosCentavos;
-
-        if ($moneda === 'USD') {
-            $trm = Trm::query()->vigente()->first();
-
-            if ($trm === null) {
-                throw ValidationException::withMessages([
-                    'moneda' => 'No hay una TRM vigente para cotizar en USD.',
-                ]);
-            }
-
-            $trmEscalada = $this->escalarDecimal($trm->valor);
-
-            if ($trmEscalada <= 0) {
-                throw ValidationException::withMessages([
-                    'moneda' => 'No hay una TRM válida para cotizar en USD.',
-                ]);
-            }
-
-            $subtotalCentavos = $this->convertirACentavosUsd($subtotalCentavos, $trmEscalada);
-            $descuentoCentavos = $this->convertirACentavosUsd($descuentoCentavos, $trmEscalada);
-            $impuestosCentavos = $this->convertirACentavosUsd($impuestosCentavos, $trmEscalada);
-            $totalCentavos = $subtotalCentavos - $descuentoCentavos + $impuestosCentavos;
-        }
-
-        return [
-            'subtotal_centavos' => $subtotalCentavos,
-            'descuento_centavos' => $descuentoCentavos,
-            'impuestos_centavos' => $impuestosCentavos,
-            'total_centavos' => $totalCentavos,
-            'moneda' => $moneda,
-        ];
-    }
-
-    private function tarifaVigente(Vehiculo $vehiculo, DateTimeInterface $inicio): ?Tarifa
-    {
-        $fechaInicio = $inicio->format('Y-m-d');
-        $vigencia = static function (Builder $query) use ($fechaInicio): void {
-            $query->whereDate('vigente_desde', '<=', $fechaInicio)
-                ->where(static function (Builder $query) use ($fechaInicio): void {
-                    $query->whereNull('vigente_hasta')
-                        ->orWhereDate('vigente_hasta', '>=', $fechaInicio);
-                })
-                ->orderByDesc('vigente_desde')
-                ->orderByDesc('id');
-        };
-
-        return Tarifa::query()
-            ->where('vehiculo_id', $vehiculo->id)
-            ->tap($vigencia)
-            ->first()
-            ?? Tarifa::query()
-                ->whereNull('vehiculo_id')
-                ->where('tipo_vehiculo', $vehiculo->tipo)
-                ->tap($vigencia)
-                ->first();
-    }
-
-    private function escalarDecimal(string $valor): int
-    {
-        [$entero, $decimal] = array_pad(explode('.', $valor, 2), 2, '');
-
-        return ((int) $entero * 10000) + (int) str_pad(substr($decimal, 0, 4), 4, '0');
-    }
-
-    private function convertirACentavosUsd(int $centavosCop, int $trmEscalada): int
-    {
-        $unidadesEnteras = intdiv($centavosCop, $trmEscalada);
-        $resto = $centavosCop % $trmEscalada;
-        $centavosFraccionarios = intdiv(($resto * 10000) + intdiv($trmEscalada, 2), $trmEscalada);
-
-        return ($unidadesEnteras * 10000) + $centavosFraccionarios;
-    }
-}
```

Diff de renombre/refactor del método cotizar (último contenido de FakeTarifaService vs TarifaServiceEstandar en 0a2dced):

Comando: `git diff 05f4684^:app/Fakes/FakeTarifaService.php 0a2dced:app/Services/TarifaServiceEstandar.php`

```
diff --git a/app/Fakes/FakeTarifaService.php b/app/Services/TarifaServiceEstandar.php
index 9fd3d97..744c3c6 100644
--- a/app/Fakes/FakeTarifaService.php
+++ b/app/Services/TarifaServiceEstandar.php
@@ -2,7 +2,7 @@

 declare(strict_types=1);

-namespace App\Fakes;
+namespace App\Services;

 use App\Contracts\TarifaService;
 use App\Models\Convenio;
@@ -13,10 +13,12 @@
 use Illuminate\Database\Eloquent\Builder;
 use Illuminate\Validation\ValidationException;

-final class FakeTarifaService implements TarifaService
+final class TarifaServiceEstandar implements TarifaService
 {
     private const int IMPUESTO_PORCENTAJE = 19;

+    public function __construct(private ConvertidorTrm $convertidor) {}
+
     public function cotizar(
         int $vehiculoId,
         DateTimeInterface $inicio,
@@ -87,7 +89,7 @@ public function cotizar(
                 ]);
             }

-            $trmEscalada = $this->escalarDecimal($trm->valor);
+            $trmEscalada = $this->convertidor->escalarDecimal($trm->valor);

             if ($trmEscalada <= 0) {
                 throw ValidationException::withMessages([
@@ -95,9 +97,9 @@ public function cotizar(
                 ]);
             }

-            $subtotalCentavos = $this->convertirACentavosUsd($subtotalCentavos, $trmEscalada);
-            $descuentoCentavos = $this->convertirACentavosUsd($descuentoCentavos, $trmEscalada);
-            $impuestosCentavos = $this->convertirACentavosUsd($impuestosCentavos, $trmEscalada);
+            $subtotalCentavos = $this->convertidor->convertirACentavosUsd($subtotalCentavos, $trmEscalada);
+            $descuentoCentavos = $this->convertidor->convertirACentavosUsd($descuentoCentavos, $trmEscalada);
+            $impuestosCentavos = $this->convertidor->convertirACentavosUsd($impuestosCentavos, $trmEscalada);
             $totalCentavos = $subtotalCentavos - $descuentoCentavos + $impuestosCentavos;
         }

@@ -133,20 +135,4 @@ private function tarifaVigente(Vehiculo $vehiculo, DateTimeInterface $inicio): ?
                 ->tap($vigencia)
                 ->first();
     }
-
-    private function escalarDecimal(string $valor): int
-    {
-        [$entero, $decimal] = array_pad(explode('.', $valor, 2), 2, '');
-
-        return ((int) $entero * 10000) + (int) str_pad(substr($decimal, 0, 4), 4, '0');
-    }
-
-    private function convertirACentavosUsd(int $centavosCop, int $trmEscalada): int
-    {
-        $unidadesEnteras = intdiv($centavosCop, $trmEscalada);
-        $resto = $centavosCop % $trmEscalada;
-        $centavosFraccionarios = intdiv(($resto * 10000) + intdiv($trmEscalada, 2), $trmEscalada);
-
-        return ($unidadesEnteras * 10000) + $centavosFraccionarios;
-    }
 }
```

# D) Patrones — archivos completos

## app/Contracts/CargoDevolucionStrategy.php

Comando: `git show 0a2dced:app/Contracts/CargoDevolucionStrategy.php | cat -n`

```
     1	<?php
     2
     3	declare(strict_types=1);
     4
     5	namespace App\Contracts;
     6
     7	use App\Models\Reserva;
     8	use DateTimeInterface;
     9
    10	interface CargoDevolucionStrategy
    11	{
    12	    /**
    13	     * @param  array<string, mixed>  $condiciones
    14	     * @return array<int, array{concepto: string, monto_centavos: int}>
    15	     */
    16	    public function lineas(
    17	        Reserva $reserva,
    18	        DateTimeInterface $devueltoEn,
    19	        array $condiciones,
    20	    ): array;
    21	}
```

## app/Services/CalculadoraDevolucionEstandar.php

Comando: `git show 0a2dced:app/Services/CalculadoraDevolucionEstandar.php | cat -n`

```
     1	<?php
     2
     3	declare(strict_types=1);
     4
     5	namespace App\Services;
     6
     7	use App\Contracts\CalculadoraDevolucion;
     8	use App\Contracts\CargoDevolucionStrategy;
     9	use App\Models\Reserva;
    10	use DateTimeInterface;
    11
    12	final class CalculadoraDevolucionEstandar implements CalculadoraDevolucion
    13	{
    14	    /**
    15	     * @param  iterable<int, CargoDevolucionStrategy>  $estrategias
    16	     */
    17	    public function __construct(private readonly iterable $estrategias) {}
    18
    19	    /**
    20	     * @param  array<string, mixed>  $condiciones
    21	     * @return array{
    22	     *     cargos_centavos: int,
    23	     *     subtotal_centavos: int,
    24	     *     impuestos_centavos: int,
    25	     *     total_centavos: int,
    26	     *     desglose: array<int, array{concepto: string, monto_centavos: int}>
    27	     * }
    28	     */
    29	    public function calcular(
    30	        int $reservaId,
    31	        DateTimeInterface $devueltoEn,
    32	        array $condiciones = [],
    33	    ): array {
    34	        $reserva = Reserva::query()
    35	            ->with(['actaEntrega', 'contrato', 'danos', 'multas'])
    36	            ->findOrFail($reservaId);
    37
    38	        $desglose = [
    39	            ['concepto' => 'Renta base', 'monto_centavos' => $reserva->total_centavos],
    40	        ];
    41	        $cargos = 0;
    42
    43	        foreach ($this->estrategias as $estrategia) {
    44	            foreach ($estrategia->lineas($reserva, $devueltoEn, $condiciones) as $linea) {
    45	                $desglose[] = $linea;
    46	                $cargos += $linea['monto_centavos'];
    47	            }
    48	        }
    49
    50	        $subtotal = $reserva->total_centavos + $cargos;
    51	        $impuestos = intdiv($subtotal * (int) config('devolucion.iva_porcentaje'), 100);
    52
    53	        return [
    54	            'cargos_centavos' => $cargos,
    55	            'subtotal_centavos' => $subtotal,
    56	            'impuestos_centavos' => $impuestos,
    57	            'total_centavos' => $subtotal + $impuestos,
    58	            'desglose' => $desglose,
    59	        ];
    60	    }
    61	}
```

## app/Services/Devolucion/Strategies/RetrasoCargoStrategy.php

Comando: `git show 0a2dced:app/Services/Devolucion/Strategies/RetrasoCargoStrategy.php | cat -n`

```
     1	<?php
     2
     3	declare(strict_types=1);
     4
     5	namespace App\Services\Devolucion\Strategies;
     6
     7	use App\Contracts\CargoDevolucionStrategy;
     8	use App\Models\Reserva;
     9	use DateTimeInterface;
    10	use Illuminate\Support\Carbon;
    11
    12	final class RetrasoCargoStrategy implements CargoDevolucionStrategy
    13	{
    14	    /**
    15	     * @param  array<string, mixed>  $condiciones
    16	     * @return array<int, array{concepto: string, monto_centavos: int}>
    17	     */
    18	    public function lineas(
    19	        Reserva $reserva,
    20	        DateTimeInterface $devueltoEn,
    21	        array $condiciones,
    22	    ): array {
    23	        $devuelto = Carbon::parse($devueltoEn);
    24	        $inicio = Carbon::parse($reserva->inicio);
    25	        $fin = Carbon::parse($reserva->fin);
    26
    27	        if (! $devuelto->gt($fin)) {
    28	            return [];
    29	        }
    30
    31	        $segundos = (int) $fin->diffInSeconds($devuelto);
    32	        $diasRetraso = max(1, (int) ceil($segundos / 86400));
    33	        $diasReserva = max(1, (int) $inicio->diffInDays($fin));
    34	        $tarifaDiaria = intdiv($reserva->total_centavos, $diasReserva);
    35	        $monto = $tarifaDiaria + max(0, $diasRetraso - 1) * 2 * $tarifaDiaria;
    36
    37	        return [[
    38	            'concepto' => 'Retraso de '.$diasRetraso.' día(s)',
    39	            'monto_centavos' => $monto,
    40	        ]];
    41	    }
    42	}
```

## app/Contracts/ReservaState.php

Comando: `git show 0a2dced:app/Contracts/ReservaState.php | cat -n`

```
     1	<?php
     2
     3	declare(strict_types=1);
     4
     5	namespace App\Contracts;
     6
     7	use App\Models\Reserva;
     8
     9	interface ReservaState
    10	{
    11	    public function confirmar(Reserva $reserva): void;
    12
    13	    public function entregar(Reserva $reserva): void;
    14
    15	    public function devolver(Reserva $reserva): void;
    16
    17	    public function pagar(Reserva $reserva): void;
    18
    19	    public function cancelar(Reserva $reserva): void;
    20	}
```

## app/States/Reserva/AbstractReservaState.php

Comando: `git show 0a2dced:app/States/Reserva/AbstractReservaState.php | cat -n`

```
     1	<?php
     2
     3	declare(strict_types=1);
     4
     5	namespace App\States\Reserva;
     6
     7	use App\Contracts\ReservaState;
     8	use App\Exceptions\TransicionEstadoInvalida;
     9	use App\Models\Reserva;
    10
    11	abstract class AbstractReservaState implements ReservaState
    12	{
    13	    public function confirmar(Reserva $reserva): void
    14	    {
    15	        throw TransicionEstadoInvalida::para('confirmar', $reserva->estado);
    16	    }
    17
    18	    public function entregar(Reserva $reserva): void
    19	    {
    20	        throw TransicionEstadoInvalida::para('entregar', $reserva->estado);
    21	    }
    22
    23	    public function devolver(Reserva $reserva): void
    24	    {
    25	        throw TransicionEstadoInvalida::para('devolver', $reserva->estado);
    26	    }
    27
    28	    public function pagar(Reserva $reserva): void
    29	    {
    30	        throw TransicionEstadoInvalida::para('pagar', $reserva->estado);
    31	    }
    32
    33	    public function cancelar(Reserva $reserva): void
    34	    {
    35	        throw TransicionEstadoInvalida::para('cancelar', $reserva->estado);
    36	    }
    37	}
```

## app/States/Reserva/EntregadaState.php

Comando: `git show 0a2dced:app/States/Reserva/EntregadaState.php | cat -n`

```
     1	<?php
     2
     3	declare(strict_types=1);
     4
     5	namespace App\States\Reserva;
     6
     7	use App\Enums\ReservaEstado;
     8	use App\Models\Reserva;
     9
    10	final class EntregadaState extends AbstractReservaState
    11	{
    12	    public function devolver(Reserva $reserva): void
    13	    {
    14	        $reserva->update(['estado' => ReservaEstado::Facturada]);
    15	    }
    16	}
```

## app/States/Reserva/FacturadaState.php

Comando: `git show 0a2dced:app/States/Reserva/FacturadaState.php | cat -n`

```
     1	<?php
     2
     3	declare(strict_types=1);
     4
     5	namespace App\States\Reserva;
     6
     7	use App\Enums\ReservaEstado;
     8	use App\Models\Reserva;
     9
    10	final class FacturadaState extends AbstractReservaState
    11	{
    12	    public function pagar(Reserva $reserva): void
    13	    {
    14	        $reserva->update(['estado' => ReservaEstado::Pagada]);
    15	    }
    16	}
```

## app/Models/Reserva.php (líneas 100-155)

Comando: `git show 0a2dced:app/Models/Reserva.php | cat -n`

```
   100	        return $this->hasMany(Pago::class);
   101	    }
   102
   103	    /** @return HasMany<Multa, $this> */
   104	    public function multas(): HasMany
   105	    {
   106	        return $this->hasMany(Multa::class);
   107	    }
   108
   109	    /** @return HasMany<Dano, $this> */
   110	    public function danos(): HasMany
   111	    {
   112	        return $this->hasMany(Dano::class);
   113	    }
   114
   115	    public function estadoMaquina(): ReservaState
   116	    {
   117	        return match ($this->estado) {
   118	            ReservaEstado::Pendiente => new PendienteState,
   119	            ReservaEstado::Confirmada => new ConfirmadaState,
   120	            ReservaEstado::Entregada => new EntregadaState,
   121	            ReservaEstado::Facturada => new FacturadaState,
   122	            ReservaEstado::Pagada => new PagadaState,
   123	            ReservaEstado::Cancelada, ReservaEstado::Devuelta => new CanceladaState,
   124	        };
   125	    }
   126
   127	    public function confirmar(): void
   128	    {
   129	        $this->estadoMaquina()->confirmar($this);
   130	    }
   131
   132	    public function entregar(): void
   133	    {
   134	        $this->estadoMaquina()->entregar($this);
   135	    }
   136
   137	    public function devolver(): void
   138	    {
   139	        $this->estadoMaquina()->devolver($this);
   140	    }
   141
   142	    public function pagar(): void
   143	    {
   144	        $this->estadoMaquina()->pagar($this);
   145	    }
   146
   147	    public function cancelar(): void
   148	    {
   149	        $this->estadoMaquina()->cancelar($this);
   150	    }
   151
   152	    public function estaEntregada(): bool
   153	    {
   154	        return $this->estado === ReservaEstado::Entregada;
   155	    }
```

## app/Contracts/PasarelaPago.php

Comando: `git show 0a2dced:app/Contracts/PasarelaPago.php | cat -n`

```
     1	<?php
     2
     3	declare(strict_types=1);
     4
     5	namespace App\Contracts;
     6
     7	interface PasarelaPago
     8	{
     9	    /**
    10	     * @return array{
    11	     *     aprobado: bool,
    12	     *     referencia: string|null,
    13	     *     mensaje: string,
    14	     *     codigo: string
    15	     * }
    16	     */
    17	    public function autorizar(
    18	        int $reservaId,
    19	        int $montoCentavos,
    20	        string $numeroTarjeta,
    21	    ): array;
    22	}
```

## app/Services/Adapters/PasarelaPagoAdapter.php

Comando: `git show 0a2dced:app/Services/Adapters/PasarelaPagoAdapter.php | cat -n`

```
     1	<?php
     2
     3	declare(strict_types=1);
     4
     5	namespace App\Services\Adapters;
     6
     7	use App\Contracts\PasarelaPago;
     8	use App\Services\ExternalPaymentSdk\BankPaymentGateway;
     9
    10	final class PasarelaPagoAdapter implements PasarelaPago
    11	{
    12	    public function __construct(private BankPaymentGateway $pasarela) {}
    13
    14	    /**
    15	     * @return array{
    16	     *     aprobado: bool,
    17	     *     referencia: string|null,
    18	     *     mensaje: string,
    19	     *     codigo: string
    20	     * }
    21	     */
    22	    public function autorizar(
    23	        int $reservaId,
    24	        int $montoCentavos,
    25	        string $numeroTarjeta,
    26	    ): array {
    27	        $resultado = $this->pasarela->processTransaction($montoCentavos / 100, $numeroTarjeta);
    28	        $aprobado = $resultado['success'];
    29
    30	        return [
    31	            'aprobado' => $aprobado,
    32	            'referencia' => $aprobado ? 'FAKE-PAY-'.$reservaId : null,
    33	            'mensaje' => $aprobado ? 'Pago autorizado' : 'Pago rechazado',
    34	            'codigo' => $resultado['response_code'],
    35	        ];
    36	    }
    37	}
```

## app/Services/ExternalPaymentSdk/BankPaymentGateway.php

Comando: `git show 0a2dced:app/Services/ExternalPaymentSdk/BankPaymentGateway.php | cat -n`

```
     1	<?php
     2
     3	declare(strict_types=1);
     4
     5	namespace App\Services\ExternalPaymentSdk;
     6
     7	final class BankPaymentGateway
     8	{
     9	    /**
    10	     * Firma del SDK bancario simulado. El monto llega en unidades de moneda,
    11	     * no en centavos, y la respuesta no coincide con PasarelaPago.
    12	     *
    13	     * @return array{success: bool, transaction_id: string|null, message: string, response_code: string}
    14	     */
    15	    public function processTransaction(float $amount, string $cardNumber): array
    16	    {
    17	        $aprobado = $amount > 0 && ! str_ends_with($cardNumber, '0000');
    18
    19	        return [
    20	            'success' => $aprobado,
    21	            'transaction_id' => $aprobado ? 'BNK-'.substr(hash('xxh128', $cardNumber.(string) $amount), 0, 12) : null,
    22	            'message' => $aprobado ? 'Transaction approved' : 'Transaction declined',
    23	            'response_code' => $aprobado ? '00' : '05',
    24	        ];
    25	    }
    26	}
```

## Búsqueda de RegistrarPago

Comando: `git ls-tree -r --name-only 0a2dced | grep RegistrarPago`

```
app/Actions/Pagos/RegistrarPago.php
```

## app/Actions/Pagos/RegistrarPago.php

Comando: `git show 0a2dced:app/Actions/Pagos/RegistrarPago.php | cat -n`

```
     1	<?php
     2
     3	declare(strict_types=1);
     4
     5	namespace App\Actions\Pagos;
     6
     7	use App\Contracts\PasarelaPago;
     8	use App\Enums\FacturaEstado;
     9	use App\Enums\MetodoPago;
    10	use App\Enums\PagoEstado;
    11	use App\Models\Pago;
    12	use App\Models\Reserva;
    13	use App\States\Reserva\FacturadaState;
    14	use Illuminate\Support\Facades\DB;
    15	use Illuminate\Validation\ValidationException;
    16
    17	final class RegistrarPago
    18	{
    19	    public function __construct(private PasarelaPago $pasarela) {}
    20
    21	    public function handle(
    22	        Reserva $reserva,
    23	        MetodoPago $metodo,
    24	        int $montoCentavos,
    25	        ?string $numeroTarjeta,
    26	    ): Pago {
    27	        $pago = DB::transaction(function () use ($reserva, $metodo, $montoCentavos, $numeroTarjeta): Pago {
    28	            $factura = $reserva->factura;
    29
    30	            if ($factura === null || $factura->estado !== FacturaEstado::Pendiente) {
    31	                throw ValidationException::withMessages([
    32	                    'monto_centavos' => 'La reserva no tiene una factura pendiente.',
    33	                ]);
    34	            }
    35
    36	            if (! $reserva->estadoMaquina() instanceof FacturadaState) {
    37	                throw ValidationException::withMessages([
    38	                    'monto_centavos' => 'Solo se puede pagar una reserva facturada.',
    39	                ]);
    40	            }
    41
    42	            if ($montoCentavos !== $factura->total_centavos) {
    43	                throw ValidationException::withMessages([
    44	                    'monto_centavos' => 'El monto no coincide con el total de la factura.',
    45	                ]);
    46	            }
    47
    48	            $resultado = $metodo === MetodoPago::Tarjeta
    49	                ? $this->pasarela->autorizar($reserva->id, $montoCentavos, (string) $numeroTarjeta)
    50	                : [
    51	                    'aprobado' => true,
    52	                    'referencia' => 'FAKE-'.strtoupper($metodo->value).'-'.$reserva->id,
    53	                    'mensaje' => 'Pago autorizado',
    54	                    'codigo' => '00',
    55	                ];
    56
    57	            $pago = $reserva->pagos()->create([
    58	                'estado' => $resultado['aprobado'] ? PagoEstado::Aprobado : PagoEstado::Rechazado,
    59	                'metodo' => $metodo,
    60	                'monto_centavos' => $montoCentavos,
    61	                'referencia' => $resultado['referencia'],
    62	                'mensaje' => $resultado['mensaje'],
    63	            ]);
    64
    65	            if ($resultado['aprobado']) {
    66	                $factura->update(['estado' => FacturaEstado::Pagada]);
    67	                $reserva->pagar();
    68	            }
    69
    70	            return $pago;
    71	        });
    72
    73	        if ($pago->estado === PagoEstado::Rechazado) {
    74	            throw ValidationException::withMessages([
    75	                'numero_tarjeta' => $pago->mensaje ?? 'Pago rechazado',
    76	            ]);
    77	        }
    78
    79	        return $pago;
    80	    }
    81	}
```

# E) Verificaciones puntuales

## 22. ¿ReservaEstado::Devuelta se usa en app/?

Comando: `git grep -n "Devuelta" 0a2dced -- app resources`

```
0a2dced:app/Enums/ReservaEstado.php:12:    case Devuelta = 'devuelta';
0a2dced:app/Models/Reserva.php:123:            ReservaEstado::Cancelada, ReservaEstado::Devuelta => new CanceladaState,
0a2dced:resources/js/pages/Reservas/Index.vue:19:    devuelta: 'Devuelta',
```

## 23. ¿Existe resources/js/pages/Reservas/Index.vue y ListarReservas?

Comando: `git ls-files | grep -i "Reservas/Index\|ListarReservas"`

```
app/Actions/Reservas/ListarReservas.php
resources/js/pages/Reservas/Index.vue
```

Misma búsqueda acotada a 0a2dced:

Comando: `git ls-tree -r --name-only 0a2dced | grep -i "Reservas/Index\|ListarReservas"`

```
app/Actions/Reservas/ListarReservas.php
resources/js/pages/Reservas/Index.vue
```

## 24. routes/web.php

## routes/web.php

Comando: `git show 0a2dced:routes/web.php | cat -n`

```
     1	<?php
     2
     3	declare(strict_types=1);
     4
     5	use App\Http\Controllers\CatalogoController;
     6	use App\Http\Controllers\DevolucionController;
     7	use App\Http\Controllers\EntregaController;
     8	use App\Http\Controllers\FacturaController;
     9	use App\Http\Controllers\HomeController;
    10	use App\Http\Controllers\PagoController;
    11	use App\Http\Controllers\ReservaController;
    12	use App\Http\Controllers\TrmController;
    13	use Illuminate\Support\Facades\Route;
    14
    15	Route::get('/', HomeController::class)->name('home');
    16	Route::get('/catalogo', CatalogoController::class)->name('catalogo');
    17
    18	Route::get('/reservas', [ReservaController::class, 'index'])
    19	    ->middleware('auth')
    20	    ->name('reservas.index');
    21	Route::get('/reservas/crear', [ReservaController::class, 'create'])
    22	    ->middleware('auth')
    23	    ->name('reservas.create');
    24	Route::post('/reservas', [ReservaController::class, 'store'])
    25	    ->middleware('auth')
    26	    ->name('reservas.store');
    27	Route::get('/reservas/qr/{qrCodigo}', [ReservaController::class, 'showByQrCode'])->name('reservas.qr');
    28	Route::get('/reservas/{reserva}', [ReservaController::class, 'show'])->name('reservas.show');
    29	Route::post('/reservas/{reserva}/confirmar', [ReservaController::class, 'confirmar'])
    30	    ->middleware('auth')
    31	    ->name('reservas.confirmar');
    32	Route::post('/reservas/{reserva}/cancelar', [ReservaController::class, 'cancelar'])->name('reservas.cancelar');
    33
    34	Route::middleware(['auth', 'rol:administrador'])
    35	    ->prefix('administracion')
    36	    ->name('admin.')
    37	    ->group(function (): void {
    38	        Route::get('/trm', [TrmController::class, 'edit'])->name('trm.edit');
    39	        Route::put('/trm', [TrmController::class, 'update'])->name('trm.update');
    40	    });
    41
    42	Route::middleware(['auth', 'rol:coordinador,cajero,administrador'])->group(function (): void {
    43	    Route::get('/reservas/{reserva}/entrega', [EntregaController::class, 'create'])->name('entregas.create');
    44	    Route::post('/reservas/{reserva}/entrega', [EntregaController::class, 'store'])->name('entregas.store');
    45	    Route::get('/reservas/{reserva}/devolucion', [DevolucionController::class, 'create'])->name('devoluciones.create');
    46	    Route::post('/reservas/{reserva}/devolucion', [DevolucionController::class, 'store'])->name('devoluciones.store');
    47	    Route::get('/facturas/{factura}', [FacturaController::class, 'show'])->name('facturas.show');
    48	    Route::get('/reservas/{reserva}/pago', [PagoController::class, 'create'])->name('pagos.create');
    49	    Route::post('/reservas/{reserva}/pago', [PagoController::class, 'store'])->name('pagos.store');
    50	});
    51
    52	require __DIR__.'/auth.php';
```

## 25. phpunit.xml líneas 10-30

## phpunit.xml (líneas 10-30)

Comando: `git show 0a2dced:phpunit.xml | cat -n`

```
    10	        </testsuite>
    11	        <testsuite name="Feature">
    12	            <directory>tests/Feature</directory>
    13	        </testsuite>
    14	    </testsuites>
    15	    <source>
    16	        <include>
    17	            <directory>app</directory>
    18	        </include>
    19	        <exclude>
    20	            <directory>app/Contracts</directory>
    21	            <file>app/Http/Controllers/Controller.php</file>
    22	        </exclude>
    23	    </source>
    24	    <php>
    25	        <env name="APP_ENV" value="testing"/>
    26	        <env name="APP_MAINTENANCE_DRIVER" value="file"/>
    27	        <env name="BCRYPT_ROUNDS" value="4"/>
    28	        <env name="BROADCAST_CONNECTION" value="null"/>
    29	        <env name="CACHE_STORE" value="array"/>
    30	        <env name="DB_CONNECTION" value="sqlite"/>
```

# F) Autoría real

## 26. shortlog del SHA

Comando: `git shortlog -sne 0a2dced`

```
    44	Santiago Molano <102083840+molxno@users.noreply.github.com>
     5	Emanuel Atencia <emanuelatencia2407@gmail.com>
     5	molxno <s.molano0818@gmail.com>
     1	dependabot[bot] <49699333+dependabot[bot]@users.noreply.github.com>
```

## 27. shortlog por archivo (secciones A y D)

### app/Contracts/TarifaService.php

Comando: `git shortlog -sn 0a2dced -- app/Contracts/TarifaService.php`

```
     1	Santiago Molano
```

### app/Services/TarifaServiceEstandar.php

Comando: `git shortlog -sn 0a2dced -- app/Services/TarifaServiceEstandar.php`

```
     1	molxno
```

### app/Services/ConvertidorTrm.php

Comando: `git shortlog -sn 0a2dced -- app/Services/ConvertidorTrm.php`

```
     1	molxno
```

### app/Models/Trm.php

Comando: `git shortlog -sn 0a2dced -- app/Models/Trm.php`

```
     1	Santiago Molano
```

### config/servicios.php

Comando: `git shortlog -sn 0a2dced -- config/servicios.php`

```
     2	Santiago Molano
     1	molxno
```

### app/Contracts/CargoDevolucionStrategy.php

Comando: `git shortlog -sn 0a2dced -- app/Contracts/CargoDevolucionStrategy.php`

```
     1	molxno
```

### app/Services/CalculadoraDevolucionEstandar.php

Comando: `git shortlog -sn 0a2dced -- app/Services/CalculadoraDevolucionEstandar.php`

```
     1	Santiago Molano
     1	molxno
```

### app/Services/Devolucion/Strategies/RetrasoCargoStrategy.php

Comando: `git shortlog -sn 0a2dced -- app/Services/Devolucion/Strategies/RetrasoCargoStrategy.php`

```
     1	molxno
```

### app/Contracts/ReservaState.php

Comando: `git shortlog -sn 0a2dced -- app/Contracts/ReservaState.php`

```
     1	molxno
```

### app/States/Reserva/AbstractReservaState.php

Comando: `git shortlog -sn 0a2dced -- app/States/Reserva/AbstractReservaState.php`

```
     1	molxno
```

### app/States/Reserva/EntregadaState.php

Comando: `git shortlog -sn 0a2dced -- app/States/Reserva/EntregadaState.php`

```
     1	molxno
```

### app/States/Reserva/FacturadaState.php

Comando: `git shortlog -sn 0a2dced -- app/States/Reserva/FacturadaState.php`

```
     1	molxno
```

### app/Models/Reserva.php

Comando: `git shortlog -sn 0a2dced -- app/Models/Reserva.php`

```
     5	Santiago Molano
     1	Emanuel Atencia
     1	molxno
```

### app/Contracts/PasarelaPago.php

Comando: `git shortlog -sn 0a2dced -- app/Contracts/PasarelaPago.php`

```
     1	Santiago Molano
```

### app/Services/Adapters/PasarelaPagoAdapter.php

Comando: `git shortlog -sn 0a2dced -- app/Services/Adapters/PasarelaPagoAdapter.php`

```
     1	molxno
```

### app/Services/ExternalPaymentSdk/BankPaymentGateway.php

Comando: `git shortlog -sn 0a2dced -- app/Services/ExternalPaymentSdk/BankPaymentGateway.php`

```
     1	molxno
```

### app/Actions/Pagos/RegistrarPago.php

Comando: `git shortlog -sn 0a2dced -- app/Actions/Pagos/RegistrarPago.php`

```
     1	Santiago Molano
     1	molxno
```
