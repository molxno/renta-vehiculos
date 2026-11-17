# Guía de demo del MVP

Recorrido reproducible de RENT-GO con los seeders del repositorio. Sirve
para la demostración pública sobre `main` (local o Render).

- Local: `APP_URL` de `.env`. Con `composer dev` suele ser
  `http://localhost:8000`.
- Render Free: `https://<servicio>.onrender.com`. El plan duerme; el primer
  hit tarda ~1 min. La SQLite es efímera y se resiembra al arrancar.

## Preparar el entorno (local)

```text
php artisan migrate:fresh --seed
composer dev
```

Eso carga cinco usuarios demo, tres vehículos y seis reservas de fixture.
Si ya corriste una entrega o un pago sobre esos datos, vuelve a sembrar
antes de la demo. En Render no hace falta: cada cold start resiembra.

## Credenciales de prueba

Todos los usuarios demo usan la contraseña `password`.

El dominio no tiene un rol llamado «Empleado»: el personal operativo se
reparte en **Coordinador** (entrega y devolución) y **Cajero** (pago y
factura).

| Rol de la demo   | Rol en el sistema | Correo                     | Contraseña | Aterrizaje  | Para qué se usa                                  |
| ---------------- | ----------------- | -------------------------- | ---------- | ----------- | ------------------------------------------------ |
| Cliente          | Cliente           | `cliente@renta.test`       | `password` | `/`         | Landing, catálogo, reservas y cuenta             |
| Empleado (patio) | Coordinador       | `coordinador@renta.test`   | `password` | `/entregas` | Registrar entrega y devolución                   |
| Empleado (caja)  | Cajero            | `cajero@renta.test`        | `password` | `/pagos`    | Cobrar la factura y ver el HTML de facturación   |
| Administrador    | Administrador     | `administrador@renta.test` | `password` | `/`         | Recorrer el sitio y TRM en `/administracion/trm` |

También existe `gerente@renta.test` / `password` (Gerente). No hace falta
para este recorrido.

Entrega, devolución, pago y factura **exigen sesión y rol**. Si pides una
ruta operativa sin login, Laravel redirige a `/iniciar-sesion`.

## Pantallas actuales

| Flujo              | Rutas principales                                 | Página Vue                                       |
| ------------------ | ------------------------------------------------- | ------------------------------------------------ |
| Landing y catálogo | `/`, `/catalogo`                                  | `Welcome`, `Catalogo/Index`                      |
| Registro y login   | `/registro`, `/iniciar-sesion`                    | `Auth/Register`, `Auth/Login`                    |
| Cuenta y licencia  | `/cuenta`                                         | `Cuenta/Show`                                    |
| Reservas           | `/reservas`, `/reservas/crear`, `/reservas/{id}`  | `Reservas/Index`, `Create`, `Show`               |
| Entrega            | `/entregas`, `/reservas/{id}/entrega`             | `Entregas/Index`, `Create`, `Show`, `Acta`       |
| Devolución         | `/devoluciones`, `/reservas/{id}/devolucion`      | `Devoluciones/Index`, `Create`, `Show`           |
| Pago y factura     | `/pagos`, `/reservas/{id}/pago`, `/facturas/{id}` | `Pagos/Index`, `Create`, `Show`, `Facturas/Show` |

## Fixtures que usa el recorrido

Tras un `migrate:fresh --seed` las reservas se identifican por QR. Los `id`
numéricos suelen ser 1 a 6 en un clon limpio; si dudan, confírmenlos:

```text
php artisan tinker --execute "App\Models\Reserva::query()->get(['id','qr_codigo','estado'])->each(fn ($r) => print($r->id.' '.$r->qr_codigo.' '.$r->estado->value.PHP_EOL));"
```

| QR                      | Estado inicial | Vehículo  | Uso en la demo                                      | Ruta típica (id fresco)     |
| ----------------------- | -------------- | --------- | --------------------------------------------------- | --------------------------- |
| `QR-RESERVA-CONFIRMADA` | confirmada     | `ABC-123` | Entregable hoy (fechas relativas a now() Bogota)    | `/reservas/{id}/entrega`    |
| `QR-RESERVA-FUTURA`     | confirmada     | `GHI-789` | Bloqueada: inicia pasado mañana                     | `/entregas`                 |
| `QR-RESERVA-VENCIDA`    | confirmada     | `ABC-123` | Bloqueada: ventana ya cerrada                       | `/entregas`                 |
| `QR-ALQUILER-ENTREGADO` | entregada      | `DEF-456` | Atajo: devolución con cargos. Fechas fijas feb-2026 | `/reservas/{id}/devolucion` |
| `QR-DEVOLUCION-FACTURA` | devuelta       | `GHI-789` | Atajo: factura pendiente `FAC-DEVOLUCION-PENDIENTE` | `/facturas/{id}`            |
| `QR-PAGO-RECHAZADO`     | facturada      | `ABC-123` | Atajo: reintento de pago tras un rechazo            | `/reservas/{id}/pago`       |

## Qué está simulado

- **Crear reserva.** `GET /reservas/crear` y `POST /reservas` requieren un
  cliente autenticado. El formulario cotiza; al confirmar se genera un QR
  de demostración (gráfico ilustrativo, no escaneable).
- **Listado de reservas.** `/reservas` renderiza `Reservas/Index`.
- **Confirmación y búsqueda QR.** Solo el cliente titular confirma una
  reserva pendiente. `/reservas/qr/{qrCodigo}` la localiza por el código
  de demostración.
- **Disponibilidad.** El catálogo excluye vehículos fuera de servicio y
  reservas no canceladas que se solapan; permite reservas consecutivas.
- **Ubicación.** El buscador envía `location`, pero ese valor no filtra la
  flota.
- **Pasarela.** Cualquier tarjeta que no termine en `0000` se aprueba.
  `4111111111110000` se rechaza y permite reintentar.
- **OAuth.** Los botones de Google y Facebook están en el login y **no
  autentican**. Usar correo y contraseña.
- **Anticipo e IVA.** La entrega registra un anticipo en efectivo (30 %).
  La factura de devolución no lo descuenta y puede volver a aplicar IVA
  sobre una renta que ya lo traía. No corregir eso en vivo.

## Recorrido principal

Sigan este orden con **una sola reserva**: `QR-RESERVA-CONFIRMADA`.

### 1. Landing

1. Abran `/`.
2. Comprueben el hero a pantalla completa, el buscador blanco y el pie
   pegado al fondo en `#0B1F33`.
3. Opcional: rellenen sede, fecha de recogida y fecha de devolución.

Resultado: la landing pública RENT-GO, no un panel operativo.

### 2. Catálogo

1. Envíen el buscador del hero o pulsen **Catálogo** / **Buscar vehículos**.
2. Deben llegar a `/catalogo` con los tres vehículos del seeder:

    | Placa     | Vehículo            | Categoría | Tarifa diaria |
    | --------- | ------------------- | --------- | ------------- |
    | `ABC-123` | Toyota Corolla 2024 | sedan     | $150.000 COP  |
    | `DEF-456` | Renault Duster 2023 | camioneta | $220.000 COP  |
    | `GHI-789` | Kia Picanto 2022    | compacto  | $150.000 COP  |

3. Filtren por categoría (por ejemplo **Camioneta**): debe quedar solo
   `DEF-456`.
4. Con fechas de recogida y devolución, el listado se recarga y excluye los
   vehículos ocupados para ese período.
5. **Reservar** lleva a `/reservas/crear` con el vehículo y las fechas
   seleccionadas. La creación requiere sesión de cliente; si pide login,
   completen el paso 3 y vuelvan al catálogo.

### 3. Login

1. En el encabezado, **Iniciar sesión** (`/iniciar-sesion`).
2. Entren como cliente: `cliente@renta.test` / `password`.
3. Deben volver a `/`. Mantengan la sesión de cliente para crear la reserva
   o listen `/reservas`.
4. Más adelante, cierren sesión e inicien como coordinador:
   `coordinador@renta.test` / `password` (aterriza en `/entregas`).

Para mostrar administración, en otro momento usen
`administrador@renta.test` / `password`. El cromado del layout operativo
cambia según el rol de la sesión.

### 4. Reserva y QR

Desde el catálogo, pulsen **Reservar** en un vehículo disponible. En el
formulario, confirmen las fechas y actualicen el resumen de costos. Inicien
sesión como cliente si aún no lo hicieron y regresen al formulario para
confirmar la reserva.

Resultado: se guarda la reserva con el total calculado y se muestra una
pantalla de confirmación con un código QR de demostración.

Para continuar el recorrido operativo con los datos precargados, usen la
reserva fixture confirmada que se describe a continuación.

### 5. Reserva fixture confirmada

Con sesión de **coordinador**, abran la entrega de la reserva fixture:

```text
/reservas/1/entrega
```

Si el `id` no es 1, sustituyan el número que devolvió tinker para
`QR-RESERVA-CONFIRMADA`. La pantalla debe mostrar la licencia
`LIC-DEMO-001`, categoría B1, vigente hasta 2028-12-31, y un anticipo en
efectivo (30 % de la renta).

### 6. Entrega

En `/entregas` busquen `QR-RESERVA-CONFIRMADA` (fechas relativas a hoy en
America/Bogota). `QR-RESERVA-FUTURA` y `QR-RESERVA-VENCIDA` muestran el
botón deshabilitado.

En el acta de la reserva entregable hoy:

1. Fecha y hora: la del día (prellenada).
2. Kilometraje de salida: `10000`.
3. Combustible: **Lleno**.
4. Marquen documentos y llantas.
5. Observaciones: `Entrega sin novedades`.
6. Acepten el contrato.
7. Confirmen.

Resultado esperado: acta `ACT-{id}`, contrato `CTR-00000{id}` y anticipo en
efectivo. La reserva pasa a **entregada** y el vehículo a **alquilado**. La
pantalla de éxito muestra el número de acta y el enlace «Ver acta de
entrega». Al confirmar, entregar, facturar y pagar se envía un correo al
cliente (`MAIL_MAILER=log`: el mensaje queda en `storage/logs/laravel.log`).
La línea de tiempo de `/reservas/{id}` lee el historial de estados.

### 7. Devolución y costos

Abran:

```text
/reservas/1/devolucion
```

El campo **Fin contractual** se muestra con `formatFechaHora` (fecha y hora
en America/Bogota, `dd/mm/aaaa hh:mm a. m./p. m.`), el mismo formato de
fecha y hora del resto de pantallas de devolución. El retraso se cobra
contra esa hora. Comparen con el acta de salida (10 000 km, tanque
lleno). Para enseñar cargos extra:

1. Fecha y hora de regreso: `2026-02-09 10:00` (retraso).
2. Kilometraje final: `10450` (exceso sobre los 200 km incluidos).
3. Combustible: **Un cuarto**.
4. Añadan un daño: `Rayón en puerta` con monto `5000000` (centavos =
   $50.000 COP).
5. Confirmen.

El panel de costo estimado desglosa renta base, retraso, kilometraje,
combustible y daño, más IVA. Al guardar, la reserva queda **devuelta** y se
genera una factura pendiente `FAC-00000{id}`.

Para una devolución limpia, usen regreso `2026-02-04 09:00`, kilometraje
`10000` y combustible **Lleno**, sin daños: solo renta base e impuestos.

### 8. Pago y factura

Cierren sesión e inicien como cajero: `cajero@renta.test` / `password`
(aterriza en `/pagos`).

1. Abran `/reservas/1/pago`.
2. Método **tarjeta**. Primera tarjeta: `4111111111110000` → rechazo,
   factura sigue pendiente, se puede reintentar.
3. Segunda tarjeta: `4111111111111111` → aprobado.

Localicen el id de factura:

```text
php artisan tinker --execute "echo App\Models\Factura::query()->whereHas('reserva', fn ($q) => $q->where('qr_codigo', 'QR-RESERVA-CONFIRMADA'))->value('id');"
```

Abran `/facturas/{id}` y comprueben número, desglose, subtotal, impuestos,
total en COP y estado **pagada**.

## Atajos si un paso ya se consumió

Si la reserva confirmada ya no está en el estado inicial, no hace falta
volver a sembrar para mostrar una pantalla suelta:

| Quieren mostrar       | Fixture                 | Rol         | Ruta                        |
| --------------------- | ----------------------- | ----------- | --------------------------- |
| Devolución con cargos | `QR-ALQUILER-ENTREGADO` | Coordinador | `/reservas/{id}/devolucion` |
| Factura pendiente     | `QR-DEVOLUCION-FACTURA` | Cajero      | `/facturas/{id}`            |
| Reintento de pago     | `QR-PAGO-RECHAZADO`     | Cajero      | `/reservas/{id}/pago`       |

Valores de devolución sobre `QR-ALQUILER-ENTREGADO` (contrato con 300 km
incluidos, salida 10 000 km, tanque lleno, fin `2026-02-08 09:00`):

- Fecha: `2026-02-09 10:00`
- Kilometraje: `10450`
- Combustible: **Un cuarto**
- Daño: `Rayón en puerta` / `5000000`

Eso reproduce el desglose de las pruebas de integración (renta, 2 días de
retraso, 150 km de exceso, 3 cuartos de combustible y el daño).

## Flujos alternos

- **Login fallido:** `cliente@renta.test` con una clave distinta a
  `password` debe mostrar error y no entrar.
- **Registro:** `/registro` crea un cliente nuevo; no sustituye al usuario
  demo del seeder.
- **Entrega sin licencia:** una reserva de un usuario sin
  `LicenciaConduccion` se bloquea. El cliente demo sí tiene licencia
  vigente.
- **Pago por transferencia o efectivo:** la pasarela no pide número de
  tarjeta; el cobro se registra igual.
- **Rol incorrecto:** un cajero recibe 403 en `/entregas`; un coordinador
  no cobra.

## Verificación automatizada

```text
composer ci:check
```

Cubre Pint, ESLint, build Vite, PHPStan y Pest (excluye el grupo `flow`).
La cobertura al 100 % de `app/` la mide CI con PCOV; en un entorno sin
PCOV ni Xdebug el hook local no bloquea.

La prueba de humo `tests/Feature/MvpSmokeTest.php` cubre listado de
reservas, detalle, entrega, devolución, pago y factura con los seeders.
`tests/Feature/ReservationFlowTest.php` cubre el contrato de estados
confirmada → entregada → devuelta → facturada → pagada.
