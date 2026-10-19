<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { index as listarReservas } from '@/actions/App/Http/Controllers/ReservaController';
import Badge from '@/components/ui/Badge.vue';
import Card from '@/components/ui/Card.vue';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import { formatFechaHora } from '@/lib/utils';
import { catalogo } from '@/routes';
import type { ReservationReceipt, ReservationTimelineItem } from '@/types';

defineProps<{
    reservation: ReservationReceipt;
    timeline: ReservationTimelineItem[];
    actaUrl: string | null;
    avisoLicencia: string | null;
    cuentaUrl: string;
}>();

function pesos(centavos: number, currency: string): string {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(centavos / 100);
}

function fecha(value: string): string {
    return formatFechaHora(value);
}
</script>

<template>
    <StorefrontLayout>
        <Head :title="`Reserva #${reservation.id}`" />
        <section class="bg-brand-navy text-white">
            <div class="mx-auto max-w-5xl px-6 py-10">
                <p class="text-sm font-semibold tracking-wide text-brand-teal">
                    RESERVA CONFIRMADA
                </p>
                <h1
                    class="mt-2 text-3xl font-extrabold tracking-tight md:text-4xl"
                >
                    Tu vehículo está reservado
                </h1>
                <p class="mt-2 text-sm text-white/75">
                    Guarda el código QR para identificar tu reserva.
                </p>
                <p class="mt-3 max-w-2xl text-sm text-white/80">
                    El anticipo se registra al entregar el vehículo y el saldo
                    se cobra en caja al devolverlo.
                </p>
            </div>
        </section>

        <section
            class="mx-auto grid max-w-5xl gap-6 px-6 py-8 md:grid-cols-[minmax(0,1fr)_300px]"
        >
            <div
                v-if="avisoLicencia"
                class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950 md:col-span-2"
                role="status"
            >
                <p class="font-semibold">{{ avisoLicencia }}</p>
                <Link
                    :href="cuentaUrl"
                    class="mt-2 inline-flex font-semibold text-brand-teal hover:text-brand-teal-dark"
                >
                    Ir a Mi cuenta
                </Link>
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
                            Placa {{ reservation.vehiclePlate }}
                        </p>
                    </div>
                    <Badge tone="success">{{ reservation.status }}</Badge>
                </div>

                <dl class="mt-7 grid gap-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm text-slate-500">Recogida</dt>
                        <dd class="mt-1 font-semibold text-brand-navy">
                            {{ fecha(reservation.startsAt) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-slate-500">Devolución</dt>
                        <dd class="mt-1 font-semibold text-brand-navy">
                            {{ fecha(reservation.endsAt) }}
                        </dd>
                    </div>
                </dl>

                <div class="mt-7 border-t border-slate-200 pt-5">
                    <p class="text-sm text-slate-500">Total de la reserva</p>
                    <p class="mt-1 text-3xl font-extrabold text-brand-navy">
                        {{
                            pesos(reservation.totalCents, reservation.currency)
                        }}
                    </p>
                </div>

                <ol class="mt-8 space-y-3 border-t border-slate-200 pt-5">
                    <li
                        v-for="hito in timeline"
                        :key="hito.key"
                        class="flex items-start justify-between gap-3"
                    >
                        <div>
                            <p
                                class="font-semibold"
                                :class="
                                    hito.done
                                        ? 'text-brand-navy'
                                        : 'text-slate-400'
                                "
                            >
                                {{ hito.label }}
                            </p>
                            <p class="text-xs text-slate-500">
                                {{
                                    hito.at
                                        ? fecha(hito.at)
                                        : hito.done
                                          ? 'Hora no registrada'
                                          : 'Pendiente'
                                }}
                            </p>
                        </div>
                        <span
                            class="mt-0.5 rounded-full px-2 py-0.5 text-xs font-semibold"
                            :class="
                                hito.done
                                    ? 'bg-teal-50 text-brand-teal'
                                    : 'bg-slate-100 text-slate-500'
                            "
                        >
                            {{ hito.done ? 'Listo' : 'Pendiente' }}
                        </span>
                    </li>
                </ol>

                <Link
                    v-if="actaUrl"
                    :href="actaUrl"
                    class="mt-6 inline-flex items-center justify-center rounded-lg bg-brand-teal px-4 py-2 text-sm font-semibold text-white hover:bg-brand-teal-dark"
                >
                    Ver acta de entrega
                </Link>
            </Card>

            <Card class="flex flex-col items-center text-center">
                <h2 class="text-lg font-bold text-brand-navy">QR de reserva</h2>
                <div
                    class="mt-4 flex h-44 w-44 flex-col items-center justify-center gap-3 border-8 border-brand-navy bg-white p-3"
                    role="img"
                    :aria-label="`Código QR de demostración ${reservation.qrCode ?? ''}`"
                >
                    <svg
                        aria-hidden="true"
                        viewBox="0 0 64 64"
                        class="h-20 w-20 text-brand-navy"
                        fill="currentColor"
                    >
                        <path
                            d="M4 4h20v20H4V4Zm6 6v8h8v-8h-8Zm30-6h20v20H40V4Zm6 6v8h8v-8h-8ZM4 40h20v20H4V40Zm6 6v8h8v-8h-8Zm24-42h6v6h-6V4Zm-6 12h6v6h-6v-6Zm6 6h6v6h-6v-6Zm6 6h6v6h-6v-6Zm12 0h6v6h-6v-6Zm-18 6h6v6h-6v-6Zm12 0h6v6h-6v-6Zm12 6h6v6h-6v-6Zm-24 6h6v6h-6v-6Zm12 0h6v6h-6v-6Zm6 6h6v6h-6v-6Zm6-6h6v6h-6v-6Zm-24 6h6v6h-6v-6Z"
                        />
                    </svg>
                    <span
                        class="text-xs font-bold tracking-widest text-brand-navy"
                    >
                        QR DEMO
                    </span>
                </div>
                <p
                    class="mt-4 font-mono text-sm font-semibold break-all text-brand-navy"
                >
                    {{ reservation.qrCode ?? 'Código no disponible' }}
                </p>
                <p class="mt-2 text-xs text-slate-500">
                    Código único de prueba asociado a esta reserva.
                </p>
                <Link
                    :href="listarReservas.url()"
                    class="mt-5 inline-flex w-full justify-center rounded-lg bg-brand-teal px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-teal-dark focus:ring-2 focus:ring-brand-teal/40 focus:ring-offset-2 focus:outline-none"
                >
                    Ver mis reservas
                </Link>
                <Link
                    :href="catalogo.url()"
                    class="mt-3 inline-flex w-full justify-center rounded-lg border border-brand-ink bg-white px-4 py-2 text-sm font-semibold text-brand-ink transition hover:bg-brand-ink/5 focus:ring-2 focus:ring-brand-gold focus:ring-offset-2 focus:outline-none"
                >
                    Volver al catálogo
                </Link>
            </Card>
        </section>
    </StorefrontLayout>
</template>
