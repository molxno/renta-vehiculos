<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    create as crearReserva,
    store as guardarReserva,
} from '@/actions/App/Http/Controllers/ReservaController';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import { catalogo } from '@/routes';
import type { ReservationFormProps } from '@/types';

const props = defineProps<ReservationFormProps>();

const form = useForm({
    vehiculo_id: props.filters.vehiculoId?.toString() ?? '',
    inicio: props.filters.inicio,
    fin: props.filters.fin,
});

const quoteIsCurrent = computed(
    () =>
        props.quote !== null &&
        form.vehiculo_id === String(props.filters.vehiculoId ?? '') &&
        form.inicio === props.filters.inicio &&
        form.fin === props.filters.fin,
);

function pesos(centavos: number, currency = 'COP'): string {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(centavos / 100);
}

function trmLabel(value: string | null): string {
    if (value === null) {
        return 'No disponible';
    }

    return `${new Intl.NumberFormat('es-CO', {
        maximumFractionDigits: 4,
    }).format(Number(value))} COP/USD`;
}

function reservar(): void {
    form.post(guardarReserva.url());
}
</script>

<template>
    <StorefrontLayout>
        <Head title="Crear reserva" />
        <section class="bg-brand-navy text-white">
            <div class="mx-auto max-w-7xl px-6 py-10">
                <p class="text-sm font-semibold tracking-wide text-brand-teal">
                    RESERVA
                </p>
                <h1
                    class="mt-2 text-3xl font-extrabold tracking-tight md:text-4xl"
                >
                    Cotiza y reserva tu vehículo
                </h1>
                <p class="mt-2 max-w-2xl text-sm text-white/75">
                    Elige las fechas y el vehículo. Revisaremos la
                    disponibilidad y calcularemos el costo antes de confirmar.
                </p>
            </div>
        </section>

        <section
            class="mx-auto grid max-w-7xl gap-6 px-6 py-8 lg:grid-cols-[minmax(0,1.4fr)_minmax(300px,0.8fr)]"
        >
            <Card>
                <form
                    method="get"
                    :action="crearReserva.url()"
                    class="flex flex-col gap-5"
                >
                    <h2 class="text-xl font-bold text-brand-navy">
                        Datos de la reserva
                    </h2>

                    <label
                        class="flex flex-col gap-1.5 text-sm font-medium text-slate-700"
                    >
                        Vehículo
                        <select
                            v-model="form.vehiculo_id"
                            name="vehiculo_id"
                            required
                            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 transition outline-none focus:border-brand-teal focus:ring-2 focus:ring-brand-teal/30"
                        >
                            <option value="" disabled>
                                Selecciona un vehículo
                            </option>
                            <option
                                v-for="vehicle in vehicles"
                                :key="vehicle.id"
                                :value="String(vehicle.id)"
                            >
                                {{ vehicle.label }} · {{ vehicle.category }}
                            </option>
                        </select>
                        <span
                            v-if="form.errors.vehiculo_id"
                            class="text-xs font-normal text-red-600"
                        >
                            {{ form.errors.vehiculo_id }}
                        </span>
                    </label>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label
                            class="flex flex-col gap-1.5 text-sm font-medium text-slate-700"
                        >
                            Fecha de recogida
                            <input
                                v-model="form.inicio"
                                name="inicio"
                                type="date"
                                required
                                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 transition outline-none focus:border-brand-teal focus:ring-2 focus:ring-brand-teal/30"
                            />
                            <span
                                v-if="form.errors.inicio"
                                class="text-xs font-normal text-red-600"
                            >
                                {{ form.errors.inicio }}
                            </span>
                        </label>
                        <label
                            class="flex flex-col gap-1.5 text-sm font-medium text-slate-700"
                        >
                            Fecha de devolución
                            <input
                                v-model="form.fin"
                                name="fin"
                                type="date"
                                required
                                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 transition outline-none focus:border-brand-teal focus:ring-2 focus:ring-brand-teal/30"
                            />
                            <span
                                v-if="form.errors.fin"
                                class="text-xs font-normal text-red-600"
                            >
                                {{ form.errors.fin }}
                            </span>
                        </label>
                    </div>

                    <div
                        v-if="vehicles.length === 0"
                        class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"
                    >
                        No encontramos vehículos disponibles para esos
                        criterios. Modifica las fechas o
                        <a
                            :href="catalogo.url()"
                            class="font-semibold underline underline-offset-2"
                        >
                            revisa el catálogo </a
                        >.
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Button
                            type="submit"
                            variant="secondary"
                            :disabled="vehicles.length === 0"
                        >
                            Actualizar resumen
                        </Button>
                        <Button
                            type="button"
                            :disabled="form.processing || !quoteIsCurrent"
                            @click="reservar"
                        >
                            {{
                                form.processing
                                    ? 'Reservando…'
                                    : 'Confirmar reserva'
                            }}
                        </Button>
                    </div>
                    <p v-if="form.errors.reserva" class="text-sm text-red-600">
                        {{ form.errors.reserva }}
                    </p>
                </form>
            </Card>

            <Card class="h-fit">
                <h2 class="text-xl font-bold text-brand-navy">
                    Resumen de costos
                </h2>
                <p v-if="!quoteIsCurrent" class="mt-3 text-sm text-slate-600">
                    Selecciona un vehículo y ambas fechas, luego actualiza el
                    resumen para consultar la cotización vigente.
                </p>
                <template v-else-if="quote !== null">
                    <dl class="mt-5 flex flex-col gap-4 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-600">Tarifa diaria</dt>
                            <dd class="font-semibold text-brand-navy">
                                {{
                                    pesos(quote.dailyRateCents, quote.currency)
                                }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-600">TRM de referencia</dt>
                            <dd class="font-semibold text-brand-navy">
                                {{ trmLabel(trm) }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-600">Subtotal</dt>
                            <dd class="font-semibold text-brand-navy">
                                {{ pesos(quote.subtotalCents, quote.currency) }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-600">Descuento</dt>
                            <dd class="font-semibold text-brand-navy">
                                -{{
                                    pesos(quote.discountCents, quote.currency)
                                }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-600">Impuestos</dt>
                            <dd class="font-semibold text-brand-navy">
                                {{ pesos(quote.taxesCents, quote.currency) }}
                            </dd>
                        </div>
                    </dl>
                    <div class="mt-5 border-t border-slate-200 pt-5">
                        <div class="flex items-end justify-between gap-4">
                            <span class="font-semibold text-brand-navy">
                                Total estimado
                            </span>
                            <span
                                class="text-2xl font-extrabold text-brand-navy"
                            >
                                {{ pesos(quote.totalCents, quote.currency) }}
                            </span>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">
                            La cotización se calcula en el servidor y puede
                            variar si cambian las fechas o la disponibilidad.
                        </p>
                    </div>
                </template>
            </Card>
        </section>
    </StorefrontLayout>
</template>
