export type DevolucionListItem = {
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
};

export type DevolucionCobroLinea = {
    concepto: string;
    monto_centavos: number;
};

export type DevolucionCobro = {
    cargos_centavos: number;
    subtotal_centavos: number;
    impuestos_centavos: number;
    total_centavos: number;
    desglose: DevolucionCobroLinea[];
};

export type DevolucionDano = {
    descripcion: string;
    monto_centavos: number;
};
