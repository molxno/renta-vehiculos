# Guía de demo del MVP

Recorrido reproducible de RENT-GO con los seeders del repositorio. Sirve para
ensayar la demostración pública (SCRUM-51) sobre el código integrado en `dev`.

La URL local es la de `APP_URL` en `.env`. Con `composer dev` suele ser
`http://localhost:8000`.

## Preparar el entorno

```text
php artisan migrate:fresh --seed
composer dev
```

`migrate:fresh --seed` recarga usuarios, catálogo mínimo y cuatro reservas de
fixture. Si ya corriste una entrega o un pago sobre esos datos, vuelve a
sembrar antes de la demo.

## Credenciales de prueba

Todos los usuarios demo usan la contraseña `password`. Son datos locales, no
credenciales de producción.

El dominio no tiene un rol llamado «Empleado»: el personal operativo se
reparte en **Coordinador** (entrega y devolución) y **Cajero** (pago y
factura).

| Rol de la demo   | Rol en el sistema | Correo                     | Contraseña | Para qué se usa                                      |
| ---------------- | ----------------- | -------------------------- | ---------- | ---------------------------------------------------- |
| Cliente          | Cliente           | `cliente@renta.test`       | `password` | Landing, catálogo, login y titular de las reservas   |
| Empleado (patio) | Coordinador       | `coordinador@renta.test`   | `password` | Registrar entrega y devolución                       |
| Empleado (caja)  | Cajero            | `cajero@renta.test`        | `password` | Cobrar la factura y ver el HTML de facturación       |
| Administrador    | Administrador     | `administrador@renta.test` | `password` | Entrar con rol de administración y recorrer el sitio |

También existen `gerente@renta.test` / `password` (Gerente). No hace falta
para el flujo de esta guía.

## Fixtures que usa el recorrido

Tras un `migrate:fresh --seed` las reservas se identifican por QR. Los `id`
numéricos suelen ser 1 a 4 en un clon limpio; si dudan, confírmenlos:

```text
php artisan tinker --execute "App\Models\Reserva::query()->get(['id','qr_codigo','estado'])->each(fn ($r) => print($r->id.' '.$r->qr_codigo.' '.$r->estado->value.PHP_EOL));"
```

| QR                      | Estado inicial | Vehículo  | Uso en la demo                                      | Ruta típica (id fresco)  |
| ----------------------- | -------------- | --------- | --------------------------------------------------- | ------------------------ |
| `QR-RESERVA-CONFIRMADA` | confirmada     | `ABC-123` | Recorrido principal: entrega → devolución → pago    | `/reservas/1/entrega`    |
| `QR-ALQUILER-ENTREGADO` | entregada      | `DEF-456` | Atajo: entrar directo a devolución con cargos extra | `/reservas/2/devolucion` |
| `QR-DEVOLUCION-FACTURA` | devuelta       | `GHI-789` | Atajo: factura pendiente `FAC-DEVOLUCION-PENDIENTE` | `/facturas/1`            |
| `QR-PAGO-RECHAZADO`     | facturada      | `ABC-123` | Atajo: reintento de pago tras un rechazo            | `/reservas/4/pago`       |

## Qué está simulado

- **Crear reserva.** `GET /reservas/crear` y `POST /reservas` requieren un
  cliente autenticado. El formulario cotiza mediante el servicio configurado;
  el alta crea una reserva confirmada y la pantalla de detalle muestra el
  total y el código QR de demostración. El generador QR y la tarifa usan fakes.
- **Listado de reservas.** `/reservas` responde con el contrato de Inertia,
  pero la página Vue `Reservas/Index` sigue pendiente.
- **Disponibilidad.** El catálogo y el formulario consultan
  `DisponibilidadService`; el fake excluye vehículos fuera de servicio y
  reservas no canceladas que se solapan, pero permite reservas consecutivas.
- **Ubicación.** El buscador de la landing y del catálogo envía `location`,
  pero ese valor no filtra la flota todavía.
- **Pasarela.** Cualquier tarjeta que no termine en `0000` se aprueba.
  `4111111111110000` se rechaza y permite reintentar.
- **OAuth.** El login visual está alineado con el diseño; Google y Facebook
  no autentican todavía (SCRUM-56).
- **Rutas operativas.** Entrega, devolución, pago y factura no exigen
  sesión en las rutas. Aun así, la demo inicia sesión por rol para mostrar
  autenticación y el cromado de cada perfil.

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
   seleccionadas. La creación requiere una sesión de cliente; si se solicita
   iniciar sesión, completen el paso 3 y vuelvan al catálogo.

### 3. Login

1. En el encabezado, **Iniciar sesión** (`/iniciar-sesion`).
2. Entren como cliente: `cliente@renta.test` / `password`.
3. Deben volver a `/`. Mantengan la sesión de cliente para crear la reserva.
4. Más adelante, cierren sesión e inicien como coordinador:
   `coordinador@renta.test` / `password`.

Para mostrar administración, en otro momento usen
`administrador@renta.test` / `password`. El cromado del layout operativo
cambia según el rol de la sesión.

### 4. Reserva y QR

Desde el catálogo, pulsen **Reservar** en un vehículo disponible. En el
formulario, confirmen las fechas y actualicen el resumen de costos. Inicien
sesión como cliente si aún no lo hicieron y regresen al formulario para
confirmar la reserva.

Resultado: se guarda la reserva con el total calculado por el servicio fake y
se muestra una pantalla de confirmación con un código QR de demostración. El
gráfico es ilustrativo; el fake no genera una imagen QR escaneable.

Para continuar el recorrido operativo con los datos precargados, usen la
reserva fixture confirmada que se describe a continuación.

### 5. Reserva fixture confirmada

Abran directo la entrega de la reserva fixture confirmada:

```text
/reservas/1/entrega
```

Si el `id` no es 1, sustituyan el número que devolvió el comando de tinker
para `QR-RESERVA-CONFIRMADA`. La pantalla debe mostrar la licencia
`LIC-DEMO-001`, categoría B1, vigente hasta 2028-12-31, y un anticipo en
efectivo (30 % de la renta).

### 6. Entrega

Con sesión de coordinador, en el acta:

1. Fecha y hora: `2026-02-01 09:00`.
2. Kilometraje de salida: `10000`.
3. Combustible: **Lleno**.
4. Marquen documentos y llantas.
5. Observaciones: `Entrega sin novedades`.
6. Acepten el contrato.
7. Confirmen.

Resultado esperado: acta, contrato `CTR-00000{id}` y anticipo en efectivo.
La reserva pasa a **entregada** y el vehículo a **alquilado**. La pantalla de
detalle muestra el resumen de la reserva; continúen al paso 7.

### 7. Devolución y costos

Abran:

```text
/reservas/1/devolucion
```

Comparen con el acta de salida (10 000 km, tanque lleno, fin contractual
`2026-02-04 09:00`). Para enseñar cargos extra:

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

Cierren sesión e inicien como cajero: `cajero@renta.test` / `password`.

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

## Verificación automatizada

En `origin/dev` (`03026e5`, incluye SCRUM-37):

```text
composer ci:check
```

Resultado local: Pint, ESLint, build Vite, PHPStan 0 errores y Pest
**103/103** pruebas, **546** aserciones. La cobertura al 100 % de `app/`
la mide CI con PCOV; en un entorno sin PCOV ni Xdebug el hook local no
bloquea.

La prueba de humo `tests/Feature/MvpSmokeTest.php` cubre listado de
reservas, detalle, entrega, devolución, pago y factura con los seeders.
`tests/Feature/ReservationFlowTest.php` cubre el contrato de estados
confirmada → entregada → devuelta → facturada → pagada.
