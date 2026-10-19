<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    create as crearPago,
    index as listarPagos,
} from '@/actions/App/Http/Controllers/PagoController';
import Badge from '@/components/ui/Badge.vue';
import Card from '@/components/ui/Card.vue';
import Table from '@/components/ui/Table.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { PagoListItem } from '@/types';

const props = defineProps<{
    invoices: PagoListItem[];
    filters: { q: string };
}>();

function pesos(centavos: number, currency: string): string {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(centavos / 100);
}
</script>

<template>
    <Head title="Pagos" />
    <AppLayout>
        <div class="flex flex-col gap-6">
            <div>
                <p
                    class="text-sm font-semibold tracking-wide text-brand-teal uppercase"
                >
                    Cajero
                </p>
                <h1 class="mt-1 text-3xl font-extrabold text-brand-navy">
                    Cobro de facturas
                </h1>
                <p class="mt-2 max-w-2xl text-slate-600">
                    Busca una factura pendiente por número, reserva o código QR
                    y registra el cobro.
                </p>
            </div>

            <form
                method="get"
                :action="listarPagos.url()"
                class="grid gap-3 rounded-2xl border border-neutral-border bg-white p-4 md:grid-cols-[1fr_auto]"
            >
                <label class="flex flex-col gap-1.5 text-sm font-medium">
                    Buscar factura
                    <input
                        name="q"
                        type="search"
                        :value="props.filters.q"
                        placeholder="FAC-…, número de reserva o QR"
                        class="rounded-lg border border-neutral-border px-3 py-2 outline-none focus:border-brand-teal focus:ring-2 focus:ring-brand-teal/30"
                    />
                </label>
                <div class="flex items-end">
                    <button
                        type="submit"
                        class="w-full rounded-lg bg-brand-teal px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-teal-dark md:w-auto"
                    >
                        Buscar
                    </button>
                </div>
            </form>

            <Card>
                <p v-if="invoices.length === 0" class="text-sm text-slate-600">
                    No hay facturas pendientes de cobro con esos criterios.
                </p>
                <Table
                    v-else
                    :headers="[
                        'Factura',
                        'Reserva',
                        'Cliente',
                        'Vehículo',
                        'Total',
                        'Acción',
                    ]"
                >
                    <tr
                        v-for="invoice in invoices"
                        :key="invoice.reservationId"
                    >
                        <td class="px-4 py-3 font-medium text-brand-navy">
                            {{ invoice.invoiceNumber }}
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            #{{ invoice.reservationId }}
                            <span
                                class="mt-0.5 block font-mono text-xs text-slate-500"
                            >
                                {{ invoice.qrCode ?? 'Sin QR' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            {{ invoice.clientName }}
                            <span class="block text-xs text-slate-500">
                                {{ invoice.clientDocument }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            {{ invoice.vehicleLabel }} ·
                            {{ invoice.vehiclePlate }}
                        </td>
                        <td class="px-4 py-3 font-medium text-brand-navy">
                            {{ pesos(invoice.totalCents, invoice.currency) }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <Badge tone="warning">Pendiente</Badge>
                                <Link
                                    :href="
                                        crearPago.url({
                                            reserva: invoice.reservationId,
                                        })
                                    "
                                    class="rounded-lg bg-brand-teal px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-teal-dark"
                                >
                                    Cobrar
                                </Link>
                            </div>
                        </td>
                    </tr>
                </Table>
            </Card>
        </div>
    </AppLayout>
</template>
