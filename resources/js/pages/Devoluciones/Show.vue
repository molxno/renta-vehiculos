<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { index as listarDevoluciones } from '@/actions/App/Http/Controllers/DevolucionController';
import Badge from '@/components/ui/Badge.vue';
import Card from '@/components/ui/Card.vue';
import Table from '@/components/ui/Table.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatFechaHora } from '@/lib/utils';
import type { DevolucionCobroLinea } from '@/types';

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
    acta: {
        devueltoAt: string | null;
        kilometraje: number | null;
        combustible: string | null;
        observaciones: string | null;
    };
    factura: {
        numero: string;
        estado: string;
        subtotalCents: number;
        taxCents: number;
        totalCents: number;
        desglose: DevolucionCobroLinea[];
    };
    pagoUrl: string | null;
    puedeCobrar: boolean;
}>();

function pesos(centavos: number): string {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: props.reservation.currency,
        maximumFractionDigits: 0,
    }).format(centavos / 100);
}

function fecha(value: string | null): string {
    return formatFechaHora(value);
}
</script>

<template>
    <Head title="Devolución registrada" />
    <AppLayout>
        <div class="flex flex-col gap-8">
            <div>
                <p
                    class="text-sm font-semibold tracking-wide text-brand-teal uppercase"
                >
                    Devolución confirmada
                </p>
                <h1 class="mt-1 text-3xl font-extrabold text-brand-navy">
                    El vehículo fue inspeccionado
                </h1>
                <p class="mt-2 max-w-2xl text-slate-600">
                    La reserva #{{ reservation.id }} quedó facturada. La factura
                    {{ factura.numero }} quedó pendiente de cobro.
                </p>
            </div>

            <Card>
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-sm text-slate-500">Reserva</p>
                        <h2 class="mt-1 text-2xl font-bold text-brand-navy">
                            #{{ reservation.id }} ·
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
                        <dt class="text-sm text-slate-500">
                            Hora de devolución
                        </dt>
                        <dd class="mt-1 font-semibold text-brand-navy">
                            {{ fecha(acta.devueltoAt) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-slate-500">
                            Kilometraje final
                        </dt>
                        <dd class="mt-1 font-semibold text-brand-navy">
                            {{ acta.kilometraje ?? 'No registrado' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-slate-500">
                            Nivel de gasolina
                        </dt>
                        <dd class="mt-1 font-semibold text-brand-navy">
                            {{ acta.combustible ?? 'No registrado' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-slate-500">Factura</dt>
                        <dd class="mt-1 font-semibold text-brand-navy">
                            {{ factura.numero }} ·
                            {{ pesos(factura.totalCents) }}
                        </dd>
                    </div>
                </dl>

                <p
                    v-if="acta.observaciones"
                    class="mt-6 text-sm text-slate-600"
                >
                    {{ acta.observaciones }}
                </p>

                <Table class="mt-7" :headers="['Concepto', 'Monto']">
                    <tr v-for="linea in factura.desglose" :key="linea.concepto">
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
                            {{ pesos(factura.taxCents) }}
                        </td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-semibold text-brand-navy">
                            Total
                        </td>
                        <td class="px-4 py-3 font-semibold text-brand-navy">
                            {{ pesos(factura.totalCents) }}
                        </td>
                    </tr>
                </Table>

                <p
                    v-if="reservation.qrCode"
                    class="mt-6 font-mono text-sm text-slate-500"
                >
                    QR {{ reservation.qrCode }}
                </p>

                <p class="mt-6 text-sm text-slate-600">
                    Factura {{ factura.numero }}. Pasar a caja para el cobro.
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <Link
                        v-if="puedeCobrar && pagoUrl"
                        :href="pagoUrl"
                        class="inline-flex items-center justify-center rounded-lg bg-brand-teal px-4 py-2 text-sm font-semibold text-white hover:bg-brand-teal-dark"
                    >
                        Cobrar
                    </Link>
                    <Link
                        :href="listarDevoluciones.url()"
                        class="inline-flex items-center justify-center rounded-lg border border-brand-navy bg-white px-4 py-2 text-sm font-semibold text-brand-navy hover:bg-slate-50"
                    >
                        Volver a devoluciones
                    </Link>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
