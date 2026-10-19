<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { index as listarPagos } from '@/actions/App/Http/Controllers/PagoController';
import Badge from '@/components/ui/Badge.vue';
import Card from '@/components/ui/Card.vue';
import Table from '@/components/ui/Table.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { PagoFactura, PagoIntento } from '@/types';

const props = defineProps<{
    reservation: {
        id: number;
        status: string;
        vehicleLabel: string;
        vehiclePlate: string;
        clientName: string;
        qrCode: string | null;
        currency: string;
    };
    invoice: PagoFactura;
    payment: PagoIntento;
    facturaUrl: string;
}>();

function pesos(centavos: number): string {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: props.reservation.currency,
        maximumFractionDigits: 0,
    }).format(centavos / 100);
}

function etiquetaMetodo(metodo: string): string {
    return (
        {
            tarjeta: 'Tarjeta',
            transferencia: 'Transferencia',
            efectivo: 'Efectivo',
        }[metodo] ?? metodo
    );
}
</script>

<template>
    <Head title="Pago registrado" />
    <AppLayout>
        <div class="flex flex-col gap-8">
            <div>
                <p
                    class="text-sm font-semibold tracking-wide text-brand-teal uppercase"
                >
                    Pago aprobado
                </p>
                <h1 class="mt-1 text-3xl font-extrabold text-brand-navy">
                    El alquiler quedó cerrado
                </h1>
                <p class="mt-2 max-w-2xl text-slate-600">
                    La reserva #{{ reservation.id }} quedó pagada. La factura
                    {{ invoice.numero }} quedó marcada como pagada.
                </p>
            </div>

            <Card>
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-sm text-slate-500">Comprobante</p>
                        <h2 class="mt-1 text-2xl font-bold text-brand-navy">
                            {{ invoice.numero }} ·
                            {{ reservation.vehicleLabel }}
                        </h2>
                        <p class="mt-1 text-sm text-slate-600">
                            {{ reservation.clientName }} · Placa
                            {{ reservation.vehiclePlate }}
                        </p>
                    </div>
                    <Badge tone="success">{{ reservation.status }}</Badge>
                </div>

                <dl class="mt-7 grid gap-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm text-slate-500">Referencia</dt>
                        <dd class="mt-1 font-semibold text-brand-navy">
                            {{ payment.referencia ?? 'Sin referencia' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-slate-500">Método</dt>
                        <dd class="mt-1 font-semibold text-brand-navy">
                            {{ etiquetaMetodo(payment.metodo) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-slate-500">Monto cobrado</dt>
                        <dd class="mt-1 font-semibold text-brand-navy">
                            {{ pesos(payment.montoCents) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-slate-500">Estado del pago</dt>
                        <dd class="mt-1 font-semibold text-brand-navy">
                            {{ payment.estado }}
                        </dd>
                    </div>
                </dl>

                <Table class="mt-7" :headers="['Concepto', 'Monto']">
                    <tr v-for="linea in invoice.desglose" :key="linea.concepto">
                        <td class="px-4 py-3 text-slate-600">
                            {{ linea.concepto }}
                        </td>
                        <td class="px-4 py-3 font-medium text-brand-navy">
                            {{ pesos(linea.monto_centavos) }}
                        </td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 text-slate-600">IVA</td>
                        <td class="px-4 py-3 font-medium text-brand-navy">
                            {{ pesos(invoice.taxCents) }}
                        </td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-semibold text-brand-navy">
                            Total
                        </td>
                        <td class="px-4 py-3 font-semibold text-brand-navy">
                            {{ pesos(invoice.totalCents) }}
                        </td>
                    </tr>
                </Table>

                <p
                    v-if="reservation.qrCode"
                    class="mt-6 font-mono text-sm text-slate-500"
                >
                    QR {{ reservation.qrCode }}
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <Link
                        :href="facturaUrl"
                        class="inline-flex items-center justify-center rounded-lg bg-brand-teal px-4 py-2 text-sm font-semibold text-white hover:bg-brand-teal-dark"
                    >
                        Ver factura
                    </Link>
                    <Link
                        :href="listarPagos.url()"
                        class="inline-flex items-center justify-center rounded-lg border border-brand-navy bg-white px-4 py-2 text-sm font-semibold text-brand-navy hover:bg-slate-50"
                    >
                        Volver a pagos
                    </Link>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
