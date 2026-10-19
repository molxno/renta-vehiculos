# Contratos de Fase 0

Este documento define la superficie estable que necesitan los equipos de
historias. PR0 publica nombres, rutas, props y firmas; no implementa reglas de
negocio.

## Decisiones de base

- El modelo de autenticación conserva el nombre `User` por compatibilidad con
  el starter kit.
- El dominio usa nombres en español para tablas y modelos nuevos, excepto
  `User`.
- No se crea una tabla `metodos_pago`: el método de pago será un enum/cast en
  `pagos`.
- No se crea una tabla propia de notificaciones: se usará el mecanismo estándar
  de Laravel si una historia lo necesita.
- No se crea `lineas_factura`: `facturas.desglose` será una columna JSON.
- No existe una entidad `Alquiler`. `Reserva` conserva la máquina de estados:
  `pendiente`, `confirmada`, `entregada`, `devuelta`, `facturada`, `pagada` y
  `cancelada`.
- `ActaEntrega` y `ActaDevolucion` son entidades separadas relacionadas con una
  reserva.
- El archivo Figma Make no expuso un archivo CSS de tokens legible mediante el
  conector. Se usará el tema provisional azul marino `#1E2761` y ámbar
  `#E8A33D`, con Instrument Sans como tipografía disponible en el starter.

## Estados y transiciones permitidas

### Reserva

```text
pendiente  -> confirmada
confirmada -> entregada
entregada  -> devuelta
devuelta   -> facturada
facturada  -> pagada
confirmada -> cancelada
```

La creación deja la reserva pendiente. Solo el cliente propietario puede
confirmarla; al confirmarse se genera y persiste el QR. Una reserva cancelada
es terminal. No se permiten saltos de estado ni transiciones inversas en la
capa de aplicación. Las rutas de creación y confirmación requieren un cliente
autenticado. La ruta `reservas.qr` localiza la reserva por su código y redirige
a su página de consulta.

### Vehículo

Los estados son `disponible`, `alquilado`, `contingencia` y `mantenimiento`.

```text
disponible   -> alquilado
alquilado    -> disponible
disponible   -> contingencia
contingencia -> disponible
disponible   -> mantenimiento
mantenimiento -> disponible
```

Las reservas confirmadas no deben inventar disponibilidad; esa decisión
pertenece a `DisponibilidadService`.

## Rutas y páginas Inertia

Las rutas siguientes son el contrato HTTP de las historias. PR0 solo las
documenta; serán registradas con controladores placeholder en PR3a.

| Nombre                | Método | URI                                    | Página                            |
| --------------------- | ------ | -------------------------------------- | --------------------------------- |
| `home`                | GET    | `/`                                    | `Welcome`                         |
| `catalogo`            | GET    | `/catalogo`                            | `Catalogo/Index`                  |
| `reservas.index`      | GET    | `/reservas`                            | `Reservas/Index`                  |
| `reservas.create`     | GET    | `/reservas/crear`                      | `Reservas/Create`                 |
| `reservas.store`      | POST   | `/reservas`                            | redirección a `reservas.show`     |
| `reservas.qr`         | GET    | `/reservas/qr/{qrCodigo}`              | redirección a `reservas.show`     |
| `reservas.show`       | GET    | `/reservas/{reserva}`                  | `Reservas/Show`                   |
| `reservas.confirmar`  | POST   | `/reservas/{reserva}/confirmar`        | redirección                       |
| `reservas.cancelar`   | POST   | `/reservas/{reserva}/cancelar`         | redirección                       |
| `entregas.index`      | GET    | `/entregas`                            | `Entregas/Index`                  |
| `entregas.create`     | GET    | `/reservas/{reserva}/entrega`          | `Entregas/Create`                 |
| `entregas.store`      | POST   | `/reservas/{reserva}/entrega`          | redirección a `entregas.show`     |
| `entregas.show`       | GET    | `/reservas/{reserva}/entrega/exito`    | `Entregas/Show`                   |
| `devoluciones.index`  | GET    | `/devoluciones`                        | `Devoluciones/Index`              |
| `devoluciones.create` | GET    | `/reservas/{reserva}/devolucion`       | `Devoluciones/Create`             |
| `devoluciones.store`  | POST   | `/reservas/{reserva}/devolucion`       | redirección a `devoluciones.show` |
| `devoluciones.show`   | GET    | `/reservas/{reserva}/devolucion/exito` | `Devoluciones/Show`               |
| `facturas.show`       | GET    | `/facturas/{factura}`                  | `Facturas/Show`                   |
| `pagos.create`        | GET    | `/reservas/{reserva}/pago`             | `Pagos/Create`                    |
| `pagos.store`         | POST   | `/reservas/{reserva}/pago`             | redirección                       |
| `admin.trm.edit`      | GET    | `/administracion/trm`                  | `Administracion/Trm/Edit`         |
| `admin.trm.update`    | PUT    | `/administracion/trm`                  | redirección a `admin.trm.edit`    |

### Props comunes

```ts
export type UserRole =
    'cliente' | 'coordinador' | 'cajero' | 'administrador' | 'gerente';

export interface AuthUser {
    id: number;
    name: string;
    role: UserRole;
}

export interface SharedPageProps {
    auth: {
        user: AuthUser | null;
    };
    flash?: {
        success?: string;
        error?: string;
    };
}
```

### Props por página

```ts
export interface CatalogVehicle {
    id: number;
    label: string;
    brand: string;
    model: string;
    year: number;
    category: string;
    status: 'disponible' | 'alquilado' | 'contingencia' | 'mantenimiento';
    dailyRateCents: number;
}

export interface CatalogIndexProps extends SharedPageProps {
    vehicles: CatalogVehicle[];
    categories: string[];
    filters: {
        category: string;
        startDate: string;
        endDate: string;
        location: string;
    };
}

export interface ReservationSummary {
    id: number;
    status:
        | 'pendiente'
        | 'confirmada'
        | 'entregada'
        | 'devuelta'
        | 'facturada'
        | 'pagada'
        | 'cancelada';
    vehicleId: number;
    startsAt: string;
    endsAt: string;
    totalCents: number;
    currency: string;
}

export interface ReservationsIndexProps extends SharedPageProps {
    reservations: ReservationSummary[];
    filters: {
        status?: ReservationSummary['status'];
        page: number;
    };
}

export interface ReservationsCreateProps extends SharedPageProps {
    vehicles: CatalogVehicle[];
    filters: {
        vehiculoId: number | null;
        inicio: string;
        fin: string;
    };
    trm: string | null;
    quote: {
        dailyRateCents: number;
        subtotalCents: number;
        discountCents: number;
        taxesCents: number;
        totalCents: number;
        currency: string;
    } | null;
}

export interface ReservationShowProps extends SharedPageProps {
    reservation: ReservationSummary & {
        vehicleLabel: string;
        vehiclePlate: string;
        qrCode: string | null;
    };
}

export interface DeliveryCreateProps extends SharedPageProps {
    reservation: ReservationSummary;
    checklist: string[];
    licencia: {
        numero: string;
        fecha_vencimiento: string;
        categoria: string;
        vigente: boolean;
    } | null;
    bloqueo: string | null;
    anticipoCents: number;
    currency: string;
    condicionesContrato: Record<string, unknown>;
}

export interface ReturnCreateProps extends SharedPageProps {
    reservation: ReservationSummary;
    checklist: string[];
    estimatedChargesCents: number;
}

export interface InvoiceShowProps extends SharedPageProps {
    invoice: {
        id: number;
        reservationId: number;
        status: 'pendiente' | 'pagada' | 'anulada';
        totalCents: number;
        currency: string;
        desglose: Array<{
            concepto: string;
            montoCents: number;
        }>;
    };
}

export interface PaymentCreateProps extends SharedPageProps {
    reservation: ReservationSummary;
    amountCents: number;
    currency: string;
    methods: Array<'tarjeta' | 'transferencia' | 'efectivo'>;
}
```

`reservas.create` y `reservas.store` requieren un cliente autenticado. El
formulario recibe solo vehículos disponibles para las fechas solicitadas; la
cotización y la TRM provienen de los servicios y registros configurados en el
servidor. `reservas.store` acepta `moneda` opcional (`COP` o `USD`); en USD
la cotización usa la última TRM persistida con fecha no futura. Las rutas
`admin.trm.*` son exclusivas del administrador y no consultan proveedores
externos. La respuesta de `reservas.show` incluye los datos del vehículo y el
código QR de demostración.

## Interfaces de aplicación

Las interfaces PHP de `app/Contracts` son los límites de aplicación. Las
implementaciones reales y falsas se incorporarán después, mediante inyección
del contenedor; este PR no registra implementaciones.

### Formato monetario

Todos los contratos expresan importes en centavos enteros y declaran la moneda
en el resultado cuando corresponde. Esto evita ambigüedad de redondeo entre
PHP, la base de datos y TypeScript.

### Disponibilidad por fechas

`DisponibilidadService::consultar` devuelve los vehículos filtrados por ID
cuando se proporciona y determina disponibilidad con intervalos semiabiertos:
una reserva existente bloquea si su inicio es anterior al fin de la consulta y
su fin es posterior al inicio. Las reservas canceladas no bloquean; los
vehículos cuyo estado no sea `disponible` tampoco se ofrecen. Si ninguno de
los vehículos consultados está disponible, el motivo recomienda modificar las
fechas o seleccionar otro vehículo. El fixture independiente puede cargarse
con `php artisan db:seed --class=DisponibilidadSeeder`.

### FormRequests

- `FiltrarCatalogoRequest`
- `PrepararReservaRequest`
- `StoreReservaRequest`
- `UpdateTrmRequest`
- `ConfirmarReservaRequest`
- `StoreActaEntregaRequest`
- `StoreActaDevolucionRequest`
- `StorePagoRequest`

### Próximos límites

Las interfaces no cambian por una implementación fake. Las historias pueden
construir sus casos contra estos contratos y sustituir el binding del
contenedor en tests.

## Implementaciones configurables

Los bindings de contratos se declaran en `config/servicios.php`, con una línea
por interfaz. Para cambiar una implementación, cada desarrollador modifica
únicamente la línea del contrato que necesita y no `AppServiceProvider`, lo que
reduce conflictos entre historias. El proveedor registra automáticamente ese
mapa al iniciar la aplicación.

## Estado del panel inicial

El panel de reservas de la interfaz inicial es únicamente un placeholder
visual. Sus métricas, filas y búsqueda son datos estáticos de demostración; no
ejecuta lógica de negocio ni consulta datos reales. Las historias posteriores
deben sustituirlo por props provenientes de los controladores contractuales.

## Mapa de historias, rutas y props

| Historia                         | Ruta(s)                                                                                | Página Inertia                                                   | Props principales                                                |
| -------------------------------- | -------------------------------------------------------------------------------------- | ---------------------------------------------------------------- | ---------------------------------------------------------------- |
| Consultar catálogo               | `catalogo`                                                                             | `Catalogo/Index`                                                 | `vehicles`, `categories`, `filters`                              |
| Crear y consultar reservas       | `reservas.index`, `reservas.create`, `reservas.store`, `reservas.show`                 | `Reservas/Index`, `Reservas/Create`, `Reservas/Show`             | `reservations`, `filters`, `vehicles`, `quote`, `reservation`    |
| Confirmar o cancelar una reserva | `reservas.confirmar`, `reservas.cancelar`                                              | redirección a `Reservas/Show`                                    | `reservation`                                                    |
| Registrar entrega                | `entregas.index`, `entregas.create`, `entregas.store`, `entregas.show`                 | `Entregas/Index`, `Entregas/Create`, `Entregas/Show`             | `reservations`, `filters`, `reservation`, `checklist`, `bloqueo` |
| Registrar devolución             | `devoluciones.index`, `devoluciones.create`, `devoluciones.store`, `devoluciones.show` | `Devoluciones/Index`, `Devoluciones/Create`, `Devoluciones/Show` | `reservations`, `filters`, `reservation`, `checklist`, `cobro`   |
| Consultar factura                | `facturas.show`                                                                        | `Facturas/Show`                                                  | `invoice`                                                        |
| Registrar pago                   | `pagos.create`, `pagos.store`                                                          | `Pagos/Create` y redirección                                     | `reservation`, `amountCents`, `currency`, `methods`              |

Las páginas de catálogo y creación/detalle de reservas están implementadas con
props tipadas. El listado de reservas continúa pendiente; otros flujos usan los
contratos y fakes enlazados por el contenedor según su estado de desarrollo.
