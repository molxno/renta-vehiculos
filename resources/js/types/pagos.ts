export type PagoListItem = {
    reservationId: number;
    invoiceNumber: string;
    status: string;
    vehicleLabel: string;
    vehiclePlate: string;
    clientName: string;
    clientDocument: string;
    qrCode: string | null;
    totalCents: number;
    currency: string;
};

export type PagoFacturaLinea = {
    concepto: string;
    monto_centavos: number;
};

export type PagoFactura = {
    numero: string;
    estado: string;
    subtotalCents: number;
    taxCents: number;
    totalCents: number;
    desglose: PagoFacturaLinea[];
};

export type PagoIntento = {
    estado: string;
    metodo: string;
    mensaje: string | null;
    referencia: string | null;
    montoCents: number;
};

export type PagoMetodo = {
    value: 'tarjeta' | 'transferencia' | 'efectivo';
    label: string;
};
