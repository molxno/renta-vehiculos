<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Badge from '@/components/ui/Badge.vue';
import Card from '@/components/ui/Card.vue';
import Table from '@/components/ui/Table.vue';

const props = defineProps<{
    invoice: {
        id: number;
        reservationId: number;
        status: 'pendiente' | 'pagada' | 'anulada';
        totalCents: number;
        currency: string;
        numero: string;
        subtotalCents: number;
        taxCents: number;
        desglose: Array<{
            concepto: string;
            monto_centavos: number;
        }>;
    };
}>();

function pesos(centavos: number): string {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: props.invoice.currency,
        maximumFractionDigits: 0,
    }).format(centavos / 100);
}
</script>

<template>
    <Head :title="`Factura ${invoice.numero}`" />
    <AppLayout>
        <div class="flex flex-col gap-8">
            <div>
                <p
                    class="text-sm font-semibold tracking-wide text-brand-gold uppercase"
                >
                    Facturación
                </p>
                <h1 class="mt-1 text-3xl font-bold text-brand-ink">
                    Factura {{ invoice.numero }}
                </h1>
                <p class="mt-2 text-slate-600">
                    Reserva #{{ invoice.reservationId }}
                </p>
            </div>

            <Card>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm text-slate-500">Total</p>
                        <p class="mt-2 text-3xl font-bold text-brand-ink">
                            {{ pesos(invoice.totalCents) }}
                        </p>
                    </div>
                    <Badge
                        :tone="
                            invoice.status === 'pagada' ? 'success' : 'warning'
                        "
                    >
                        {{ invoice.status }}
                    </Badge>
                </div>
                <Table class="mt-5" :headers="['Concepto', 'Monto']">
                    <tr v-for="linea in invoice.desglose" :key="linea.concepto">
                        <td class="px-4 py-3 text-slate-600">
                            {{ linea.concepto }}
                        </td>
                        <td class="px-4 py-3 font-medium text-brand-ink">
                            {{ pesos(linea.monto_centavos) }}
                        </td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 text-slate-600">IVA</td>
                        <td class="px-4 py-3 font-medium text-brand-ink">
                            {{ pesos(invoice.taxCents) }}
                        </td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium text-brand-ink">
                            Total
                        </td>
                        <td class="px-4 py-3 font-medium text-brand-ink">
                            {{ pesos(invoice.totalCents) }}
                        </td>
                    </tr>
                </Table>
            </Card>
        </div>
    </AppLayout>
</template>
