# Evidencia de código SOLID — Cotización y TRM

Fuente extraída de `main` SHA `0a2dced533ee0d344565f82ca844696579f46f75`.
Los números de línea coinciden con los archivos del repositorio.

Componentes:

| Archivo                                  | Rol SOLID                                               |
| ---------------------------------------- | ------------------------------------------------------- |
| `app/Contracts/TarifaService.php`        | Contrato de cotización (DIP / ISP)                      |
| `app/Services/ConvertidorTrm.php`        | Conversión COP → USD extraída (SRP)                     |
| `app/Services/TarifaServiceEstandar.php` | Implementación de cotización (OCP / LSP)                |
| `config/servicios.php`                   | Binding `TarifaService` → `TarifaServiceEstandar` (DIP) |

---

## 1. `app/Contracts/TarifaService.php`

```php
 1  <?php
 2
 3  declare(strict_types=1);
 4
 5  namespace App\Contracts;
 6
 7  use DateTimeInterface;
 8
 9  interface TarifaService
10  {
11      /**
12       * @return array{
13       *     subtotal_centavos: int,
14       *     descuento_centavos: int,
15       *     impuestos_centavos: int,
16       *     total_centavos: int,
17       *     moneda: string
18       * }
19       */
20      public function cotizar(
21          int $vehiculoId,
22          DateTimeInterface $inicio,
23          DateTimeInterface $fin,
24          ?int $convenioId = null,
25          ?string $moneda = null,
26      ): array;
27  }
```

---

## 2. `app/Services/ConvertidorTrm.php`

```php
 1  <?php
 2
 3  declare(strict_types=1);
 4
 5  namespace App\Services;
 6
 7  final class ConvertidorTrm
 8  {
 9      public function escalarDecimal(string $valor): int
10      {
11          [$entero, $decimal] = array_pad(explode('.', $valor, 2), 2, '');
12
13          return ((int) $entero * 10000) + (int) str_pad(substr($decimal, 0, 4), 4, '0');
14      }
15
16      public function convertirACentavosUsd(int $centavosCop, int $trmEscalada): int
17      {
18          $unidadesEnteras = intdiv($centavosCop, $trmEscalada);
19          $resto = $centavosCop % $trmEscalada;
20          $centavosFraccionarios = intdiv(($resto * 10000) + intdiv($trmEscalada, 2), $trmEscalada);
21
22          return ($unidadesEnteras * 10000) + $centavosFraccionarios;
23      }
24  }
```

---

## 3. `app/Services/TarifaServiceEstandar.php`

```php
  1  <?php
  2
  3  declare(strict_types=1);
  4
  5  namespace App\Services;
  6
  7  use App\Contracts\TarifaService;
  8  use App\Models\Convenio;
  9  use App\Models\Tarifa;
 10  use App\Models\Trm;
 11  use App\Models\Vehiculo;
 12  use DateTimeInterface;
 13  use Illuminate\Database\Eloquent\Builder;
 14  use Illuminate\Validation\ValidationException;
 15
 16  final class TarifaServiceEstandar implements TarifaService
 17  {
 18      private const int IMPUESTO_PORCENTAJE = 19;
 19
 20      public function __construct(private ConvertidorTrm $convertidor) {}
 21
 22      public function cotizar(
 23          int $vehiculoId,
 24          DateTimeInterface $inicio,
 25          DateTimeInterface $fin,
 26          ?int $convenioId = null,
 27          ?string $moneda = null,
 28      ): array {
 29          $moneda = strtoupper($moneda ?? 'COP');
 30
 31          if (! in_array($moneda, ['COP', 'USD'], true)) {
 32              throw ValidationException::withMessages([
 33                  'moneda' => 'La moneda seleccionada no es válida.',
 34              ]);
 35          }
 36
 37          $duracionSegundos = $fin->getTimestamp() - $inicio->getTimestamp();
 38
 39          if ($duracionSegundos <= 0) {
 40              throw ValidationException::withMessages([
 41                  'fin' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
 42              ]);
 43          }
 44
 45          $vehiculo = Vehiculo::query()->findOrFail($vehiculoId);
 46          $tarifa = $this->tarifaVigente($vehiculo, $inicio);
 47
 48          if ($tarifa === null) {
 49              throw ValidationException::withMessages([
 50                  'vehiculo_id' => 'No hay una tarifa vigente para el vehículo en la fecha seleccionada.',
 51              ]);
 52          }
 53
 54          $dias = intdiv($duracionSegundos + 86399, 86400);
 55          $subtotalCentavos = $tarifa->valor_diario_centavos * $dias;
 56          $descuentoPorcentaje = 0;
 57
 58          if ($convenioId !== null) {
 59              $convenio = Convenio::query()
 60                  ->whereKey($convenioId)
 61                  ->where('activo', true)
 62                  ->first();
 63
 64              if ($convenio === null) {
 65                  throw ValidationException::withMessages([
 66                      'convenio_id' => 'El convenio seleccionado no está activo.',
 67                  ]);
 68              }
 69
 70              if ($convenio->descuento_porcentaje > 100) {
 71                  throw ValidationException::withMessages([
 72                      'convenio_id' => 'El descuento del convenio no es válido.',
 73                  ]);
 74              }
 75
 76              $descuentoPorcentaje = $convenio->descuento_porcentaje;
 77          }
 78
 79          $descuentoCentavos = intdiv(($subtotalCentavos * $descuentoPorcentaje) + 50, 100);
 80          $impuestosCentavos = intdiv(($subtotalCentavos * self::IMPUESTO_PORCENTAJE) + 50, 100);
 81          $totalCentavos = $subtotalCentavos - $descuentoCentavos + $impuestosCentavos;
 82
 83          if ($moneda === 'USD') {
 84              $trm = Trm::query()->vigente()->first();
 85
 86              if ($trm === null) {
 87                  throw ValidationException::withMessages([
 88                      'moneda' => 'No hay una TRM vigente para cotizar en USD.',
 89                  ]);
 90              }
 91
 92              $trmEscalada = $this->convertidor->escalarDecimal($trm->valor);
 93
 94              if ($trmEscalada <= 0) {
 95                  throw ValidationException::withMessages([
 96                      'moneda' => 'No hay una TRM válida para cotizar en USD.',
 97                  ]);
 98              }
 99
100              $subtotalCentavos = $this->convertidor->convertirACentavosUsd($subtotalCentavos, $trmEscalada);
101              $descuentoCentavos = $this->convertidor->convertirACentavosUsd($descuentoCentavos, $trmEscalada);
102              $impuestosCentavos = $this->convertidor->convertirACentavosUsd($impuestosCentavos, $trmEscalada);
103              $totalCentavos = $subtotalCentavos - $descuentoCentavos + $impuestosCentavos;
104          }
105
106          return [
107              'subtotal_centavos' => $subtotalCentavos,
108              'descuento_centavos' => $descuentoCentavos,
109              'impuestos_centavos' => $impuestosCentavos,
110              'total_centavos' => $totalCentavos,
111              'moneda' => $moneda,
112          ];
113      }
114
115      private function tarifaVigente(Vehiculo $vehiculo, DateTimeInterface $inicio): ?Tarifa
116      {
117          $fechaInicio = $inicio->format('Y-m-d');
118          $vigencia = static function (Builder $query) use ($fechaInicio): void {
119              $query->whereDate('vigente_desde', '<=', $fechaInicio)
120                  ->where(static function (Builder $query) use ($fechaInicio): void {
121                      $query->whereNull('vigente_hasta')
122                          ->orWhereDate('vigente_hasta', '>=', $fechaInicio);
123                  })
124                  ->orderByDesc('vigente_desde')
125                  ->orderByDesc('id');
126          };
127
128          return Tarifa::query()
129              ->where('vehiculo_id', $vehiculo->id)
130              ->tap($vigencia)
131              ->first()
132              ?? Tarifa::query()
133                  ->whereNull('vehiculo_id')
134                  ->where('tipo_vehiculo', $vehiculo->tipo)
135                  ->tap($vigencia)
136                  ->first();
137      }
138  }
```

---

## 4. `config/servicios.php`

Binding de `TarifaService` en la línea 24.

```php
 1  <?php
 2
 3  use App\Contracts\CalculadoraDevolucion;
 4  use App\Contracts\DisponibilidadService;
 5  use App\Contracts\GeneradorQr;
 6  use App\Contracts\PasarelaPago;
 7  use App\Contracts\TarifaService;
 8  use App\Fakes\FakeDisponibilidadService;
 9  use App\Fakes\FakeGeneradorQr;
10  use App\Services\Adapters\PasarelaPagoAdapter;
11  use App\Services\CalculadoraDevolucionEstandar;
12  use App\Services\Devolucion\Strategies\CombustibleCargoStrategy;
13  use App\Services\Devolucion\Strategies\DanoCargoStrategy;
14  use App\Services\Devolucion\Strategies\KilometrajeCargoStrategy;
15  use App\Services\Devolucion\Strategies\RetrasoCargoStrategy;
16  use App\Services\TarifaServiceEstandar;
17
18  return [
19      'bindings' => [
20          CalculadoraDevolucion::class => CalculadoraDevolucionEstandar::class,
21          DisponibilidadService::class => FakeDisponibilidadService::class,
22          GeneradorQr::class => FakeGeneradorQr::class,
23          PasarelaPago::class => PasarelaPagoAdapter::class,
24          TarifaService::class => TarifaServiceEstandar::class,
25      ],
26      'cargos_devolucion' => [
27          RetrasoCargoStrategy::class,
28          KilometrajeCargoStrategy::class,
29          CombustibleCargoStrategy::class,
30          DanoCargoStrategy::class,
31      ],
32  ];
```
