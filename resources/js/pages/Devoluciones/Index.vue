<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    create as crearDevolucion,
    index as listarDevoluciones,
} from '@/actions/App/Http/Controllers/DevolucionController';
import Badge from '@/components/ui/Badge.vue';
import Card from '@/components/ui/Card.vue';
import Table from '@/components/ui/Table.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { DevolucionListItem } from '@/types';

const props = defineProps<{
    reservations: DevolucionListItem[];
    filters: { q: string };
}>();

function pesos(centavos: number, currency: string): string {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(centavos / 100);
}

function fecha(value: string): string {
    return new Intl.DateTimeFormat('es-CO', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}
</script>

<template>
    <Head title="Devoluciones" />
    <AppLayout>
        <div class="flex flex-col gap-6">
            <div>
                <p
                    class="text-sm font-semibold tracking-wide text-brand-teal uppercase"
                >
                    Coordinador
                </p>
                <h1 class="mt-1 text-3xl font-extrabold text-brand-navy">
                    Devoluciones de vehículos
                </h1>
                <p class="mt-2 max-w-2xl text-slate-600">
                    Busca una reserva entregada por número, código QR o
                    documento del cliente y registra la inspección.
                </p>
            </div>

            <form
                method="get"
                :action="listarDevoluciones.url()"
                class="grid gap-3 rounded-2xl border border-neutral-border bg-white p-4 md:grid-cols-[1fr_auto]"
            >
                <label class="flex flex-col gap-1.5 text-sm font-medium">
                    Buscar reserva
                    <input
                        name="q"
                        type="search"
                        :value="props.filters.q"
                        placeholder="Número, FAKE-QR-12 o documento"
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
                <p
                    v-if="reservations.length === 0"
                    class="text-sm text-slate-600"
                >
                    No hay reservas entregadas para devolver con esos criterios.
                </p>
                <Table
                    v-else
                    :headers="[
                        'Reserva',
                        'Cliente',
                        'Vehículo',
                        'QR',
                        'Devolución prevista',
                        'Total',
                        'Acción',
                    ]"
                >
                    <tr
                        v-for="reservation in reservations"
                        :key="reservation.id"
                    >
                        <td class="px-4 py-3 font-medium text-brand-navy">
                            #{{ reservation.id }}
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            {{ reservation.clientName }}
                            <span class="block text-xs text-slate-500">
                                {{ reservation.clientDocument }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            {{ reservation.vehicleLabel }} ·
                            {{ reservation.vehiclePlate }}
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-brand-navy">
                            {{ reservation.qrCode ?? 'Sin QR' }}
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            {{ fecha(reservation.endsAt) }}
                        </td>
                        <td class="px-4 py-3 font-medium text-brand-navy">
                            {{
                                pesos(
                                    reservation.totalCents,
                                    reservation.currency,
                                )
                            }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <Badge tone="success">Entregada</Badge>
                                <Link
                                    :href="
                                        crearDevolucion.url({
                                            reserva: reservation.id,
                                        })
                                    "
                                    class="rounded-lg bg-brand-teal px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-teal-dark"
                                >
                                    Registrar devolución
                                </Link>
                            </div>
                        </td>
                    </tr>
                </Table>
            </Card>
        </div>
    </AppLayout>
</template>
