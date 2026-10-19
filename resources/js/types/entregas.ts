export type EntregaListItem = {
    id: number;
    status: string;
    vehicleLabel: string;
    vehiclePlate: string;
    clientName: string;
    clientDocument: string;
    qrCode: string | null;
    startsAt: string;
    endsAt: string;
    totalCents: number;
    currency: string;
    deliverable: boolean;
    deliveryReason: string | null;
};

export type EntregaLicencia = {
    numero: string;
    fecha_vencimiento: string;
    categoria: string;
    vigente: boolean;
    categoria_valida: boolean;
} | null;
