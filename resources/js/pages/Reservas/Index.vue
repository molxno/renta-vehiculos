<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { show as mostrarReserva } from '@/actions/App/Http/Controllers/ReservaController';
import Badge from '@/components/ui/Badge.vue';
import Card from '@/components/ui/Card.vue';
import Table from '@/components/ui/Table.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { ReservationListItem } from '@/types';

defineProps<{
    reservations: ReservationListItem[];
    filters: { page: number };
}>();

const statusLabels: Record<string, string> = {
    pendiente: 'Pendiente',
    confirmada: 'Confirmada',
    entregada: 'Entregada',
    devuelta: 'Devuelta',
    facturada: 'Facturada',
    pagada: 'Pagada',
    cancelada: 'Cancelada',
};

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

function estado(status: string): string {
    return statusLabels[status] ?? status;
}
</script>

<template>
    <Head title="Mis reservas" />
    <AppLayout>
        <div class="flex flex-col gap-6">
            <div>
                <p
                    class="text-sm font-semibold tracking-wide text-brand-gold uppercase"
                >
                    Reservas
                </p>
                <h1 class="mt-1 text-3xl font-bold text-brand-ink">
                    Listado de reservas
                </h1>
                <p class="mt-2 max-w-2xl text-slate-600">
                    Consulta el estado de cada reserva y abre el detalle para
                    continuar el flujo.
                </p>
            </div>

            <Card>
                <p
                    v-if="reservations.length === 0"
                    class="text-sm text-slate-600"
                >
                    Aún no hay reservas para mostrar.
                </p>
                <Table
                    v-else
                    :headers="[
                        'Reserva',
                        'Vehículo',
                        'Recogida',
                        'Devolución',
                        'Total',
                        'Estado',
                    ]"
                >
                    <tr
                        v-for="reservation in reservations"
                        :key="reservation.id"
                    >
                        <td class="px-4 py-3 font-medium text-brand-ink">
                            <Link
                                :href="
                                    mostrarReserva.url({
                                        reserva: reservation.id,
                                    })
                                "
                                class="underline-offset-2 hover:underline"
                            >
                                #{{ reservation.id }}
                            </Link>
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            {{ reservation.vehicleLabel }} ·
                            {{ reservation.vehiclePlate }}
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            {{ fecha(reservation.startsAt) }}
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            {{ fecha(reservation.endsAt) }}
                        </td>
                        <td class="px-4 py-3 font-medium text-brand-ink">
                            {{
                                pesos(
                                    reservation.totalCents,
                                    reservation.currency,
                                )
                            }}
                        </td>
                        <td class="px-4 py-3">
                            <Badge
                                :tone="
                                    reservation.status === 'pagada' ||
                                    reservation.status === 'confirmada'
                                        ? 'success'
                                        : reservation.status === 'cancelada'
                                          ? 'warning'
                                          : 'default'
                                "
                            >
                                {{ estado(reservation.status) }}
                            </Badge>
                        </td>
                    </tr>
                </Table>
            </Card>
            <p class="sr-only">Página {{ filters.page }}</p>
        </div>
    </AppLayout>
</template>
