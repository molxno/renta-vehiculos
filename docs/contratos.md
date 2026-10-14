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
  `confirmada`, `entregada`, `devuelta`, `facturada`, `pagada` y `cancelada`.
- `ActaEntrega` y `ActaDevolucion` son entidades separadas relacionadas con una
  reserva.
- El archivo Figma Make no expuso un archivo CSS de tokens legible mediante el
  conector. Se usará el tema provisional azul marino `#1E2761` y ámbar
  `#E8A33D`, con Instrument Sans como tipografía disponible en el starter.

## Estados y transiciones permitidas

### Reserva

```text
confirmada -> entregada
entregada  -> devuelta
devuelta   -> facturada
facturada  -> pagada
confirmada -> cancelada
```

No se permiten saltos de estado ni transiciones inversas en la capa de
aplicación. Una reserva cancelada es terminal.

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

| Nombre                | Método | URI                              | Página                        |
| --------------------- | ------ | -------------------------------- | ----------------------------- |
| `home`                | GET    | `/`                              | `Welcome`                     |
| `reservas.index`      | GET    | `/reservas`                      | `Reservas/Index`              |
| `reservas.create`     | GET    | `/reservas/crear`                | `Reservas/Create`             |
| `reservas.store`      | POST   | `/reservas`                      | redirección a `reservas.show` |
| `reservas.show`       | GET    | `/reservas/{reserva}`            | `Reservas/Show`               |
| `reservas.confirmar`  | POST   | `/reservas/{reserva}/confirmar`  | redirección                   |
| `reservas.cancelar`   | POST   | `/reservas/{reserva}/cancelar`   | redirección                   |
| `entregas.create`     | GET    | `/reservas/{reserva}/entrega`    | `Entregas/Create`             |
| `entregas.store`      | POST   | `/reservas/{reserva}/entrega`    | redirección                   |
| `devoluciones.create` | GET    | `/reservas/{reserva}/devolucion` | `Devoluciones/Create`         |
| `devoluciones.store`  | POST   | `/reservas/{reserva}/devolucion` | redirección                   |
| `facturas.show`       | GET    | `/facturas/{factura}`            | `Facturas/Show`               |
| `pagos.create`        | GET    | `/reservas/{reserva}/pago`       | `Pagos/Create`                |
| `pagos.store`         | POST   | `/reservas/{reserva}/pago`       | redirección                   |

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
export interface ReservationSummary {
    id: number;
    status:
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
    vehicles: Array<{
        id: number;
        label: string;
        category: string;
    }>;
    quote: {
        subtotalCents: number;
        discountCents: number;
        taxesCents: number;
        totalCents: number;
        currency: string;
    } | null;
}

export interface ReservationShowProps extends SharedPageProps {
    reservation: ReservationSummary & {
        qrCode: string | null;
        deliveryRecordId: number | null;
        returnRecordId: number | null;
        invoiceId: number | null;
        paymentStatus: 'pendiente' | 'aprobado' | 'rechazado' | null;
    };
}

export interface DeliveryCreateProps extends SharedPageProps {
    reservation: ReservationSummary;
    checklist: string[];
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

## Interfaces de aplicación

Las interfaces PHP de `app/Contracts` son los límites de aplicación. Las
implementaciones reales y falsas se incorporarán después, mediante inyección
del contenedor; este PR no registra implementaciones.

### Formato monetario

Todos los contratos expresan importes en centavos enteros y declaran la moneda
en el resultado cuando corresponde. Esto evita ambigüedad de redondeo entre
PHP, la base de datos y TypeScript.

### FormRequests previstos

PR3a publicará estos requests, sin duplicar reglas en controladores:

- `StoreReservaRequest`
- `ConfirmarReservaRequest`
- `StoreActaEntregaRequest`
- `StoreActaDevolucionRequest`
- `StorePagoRequest`

### Próximos límites

Las interfaces no cambian por una implementación fake. Las historias pueden
construir sus casos contra estos contratos y sustituir el binding del
contenedor en tests.
