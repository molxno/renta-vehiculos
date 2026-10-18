import type { CatalogVehicle } from '@/types/catalogo';

export type ReservationQuote = {
    dailyRateCents: number;
    subtotalCents: number;
    discountCents: number;
    taxesCents: number;
    totalCents: number;
    currency: string;
};

export type ReservationFormProps = {
    vehicles: CatalogVehicle[];
    filters: {
        vehiculoId: number | null;
        inicio: string;
        fin: string;
    };
    trm: string | null;
    quote: ReservationQuote | null;
};

export type ReservationReceipt = {
    id: number;
    status: string;
    vehicleId: number;
    vehicleLabel: string;
    vehiclePlate: string;
    startsAt: string;
    endsAt: string;
    totalCents: number;
    currency: string;
    qrCode: string | null;
};

export type ReservationListItem = {
    id: number;
    status: string;
    vehicleLabel: string;
    vehiclePlate: string;
    startsAt: string;
    endsAt: string;
    totalCents: number;
    currency: string;
};
