<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import {
    confirmar as confirmarReserva,
    create as crearReserva,
    store as guardarReserva,
} from '@/actions/App/Http/Controllers/ReservaController';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import {
    categoryLabel,
    formatDailyRate,
    formatMoney,
    pickupLocations,
    quotedDays,
    sedeFromQuery,
    statusLabels,
    vehicleDescription,
    vehicleGallery,
    vehicleSpecifications,
} from '@/lib/vehiclePresentation';
import { catalogo, home } from '@/routes';
import type { ReservationFormProps } from '@/types';

const props = defineProps<ReservationFormProps>();

const form = useForm({
    vehiculo_id: props.filters.vehiculoId?.toString() ?? '',
    inicio: props.filters.inicio,
    fin: props.filters.fin,
});

const sede = ref<string>(sedeFromQuery(usePage().url));
const selectedImageIndex = ref(0);
const quoting = ref(false);

const homeUrl = home.url();
const catalogoUrl = catalogo.url();

const selectedVehicle = computed(
    () =>
        props.vehicles.find(
            (vehicle) => String(vehicle.id) === form.vehiculo_id,
        ) ?? null,
);

const gallery = computed(() =>
    selectedVehicle.value === null ? [] : vehicleGallery(selectedVehicle.value),
);

const mainImage = computed(
    () => gallery.value[selectedImageIndex.value] ?? gallery.value[0] ?? '',
);

const specifications = computed(() =>
    selectedVehicle.value === null
        ? []
        : vehicleSpecifications(selectedVehicle.value),
);

const quoteIsCurrent = computed(
    () =>
        props.quote !== null &&
        form.vehiculo_id === String(props.filters.vehiculoId ?? '') &&
        form.inicio === props.filters.inicio &&
        form.fin === props.filters.fin,
);

const rentalDays = computed(() => {
    if (props.quote === null) {
        return 0;
    }

    return quotedDays(props.quote.subtotalCents, props.quote.dailyRateCents);
});

const canReserve = computed(
    () =>
        !form.processing &&
        !quoting.value &&
        quoteIsCurrent.value &&
        props.vehicles.length > 0 &&
        form.vehiculo_id !== '',
);

const pageTitle = computed(() =>
    selectedVehicle.value === null
        ? 'Realizar reserva'
        : selectedVehicle.value.label,
);

function pesos(centavos: number, currency = 'COP'): string {
    return formatMoney(centavos, currency);
}

function statusBadgeClass(status: string): string {
    if (status === 'disponible') {
        return 'bg-[#00B8A9]';
    }

    if (status === 'alquilado') {
        return 'bg-brand-blue';
    }

    if (status === 'contingencia') {
        return 'bg-amber-500';
    }

    return 'bg-red-600';
}

function refreshQuote(): void {
    if (form.vehiculo_id === '' || form.inicio === '' || form.fin === '') {
        return;
    }

    if (quoteIsCurrent.value || quoting.value) {
        return;
    }

    quoting.value = true;

    router.get(
        crearReserva.url(),
        {
            vehiculo_id: form.vehiculo_id,
            inicio: form.inicio,
            fin: form.fin,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            onFinish: () => {
                quoting.value = false;
            },
        },
    );
}

function reservar(): void {
    form.post(guardarReserva.url(), {
        onSuccess: (page) => {
            const reservation = page.props.reservation;

            if (
                typeof reservation !== 'object' ||
                reservation === null ||
                !('id' in reservation) ||
                typeof reservation.id !== 'number'
            ) {
                throw new Error(
                    'La respuesta de reserva no incluyó un identificador válido.',
                );
            }

            router.post(confirmarReserva.url({ reserva: reservation.id }));
        },
    });
}

watch(gallery, () => {
    selectedImageIndex.value = 0;
});

watch(
    () => [form.vehiculo_id, form.inicio, form.fin] as const,
    () => {
        refreshQuote();
    },
);
</script>

<template>
    <StorefrontLayout>
        <Head :title="pageTitle" />

        <div class="bg-neutral-background">
            <div class="mx-auto max-w-7xl px-4 py-8">
                <nav class="text-sm text-neutral-muted">
                    <Link :href="homeUrl" class="hover:text-brand-teal"
                        >Inicio</Link
                    >
                    <span class="px-2">/</span>
                    <Link :href="catalogoUrl" class="hover:text-brand-teal"
                        >Catálogo</Link
                    >
                    <span class="px-2">/</span>
                    <span class="font-medium text-slate-900">{{
                        selectedVehicle?.label ?? 'Reserva'
                    }}</span>
                </nav>

                <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-3">
                    <div class="lg:col-span-2">
                        <template v-if="selectedVehicle">
                            <div
                                class="relative overflow-hidden rounded-2xl bg-slate-100"
                            >
                                <img
                                    :src="mainImage"
                                    :alt="selectedVehicle.label"
                                    class="h-[22rem] w-full object-cover md:h-[28rem]"
                                />
                                <span
                                    class="absolute top-4 right-4 rounded-full px-3 py-1 text-xs font-semibold text-white"
                                    :class="
                                        statusBadgeClass(selectedVehicle.status)
                                    "
                                >
                                    {{ statusLabels[selectedVehicle.status] }}
                                </span>
                            </div>

                            <div
                                v-if="gallery.length > 1"
                                class="mt-3 grid grid-cols-4 gap-3"
                            >
                                <button
                                    v-for="(image, index) in gallery"
                                    :key="image"
                                    type="button"
                                    class="overflow-hidden rounded-xl ring-2 ring-offset-2 transition"
                                    :class="
                                        selectedImageIndex === index
                                            ? 'ring-[#00B8A9]'
                                            : 'ring-transparent hover:ring-slate-200'
                                    "
                                    :aria-label="`Ver imagen ${index + 1} de ${selectedVehicle.label}`"
                                    @click="selectedImageIndex = index"
                                >
                                    <img
                                        :src="image"
                                        alt=""
                                        class="h-20 w-full object-cover md:h-24"
                                    />
                                </button>
                            </div>

                            <div
                                class="mt-8 flex flex-wrap items-start justify-between gap-4"
                            >
                                <div>
                                    <h1
                                        class="text-3xl font-extrabold tracking-tight text-slate-900"
                                    >
                                        {{ selectedVehicle.model }}
                                    </h1>
                                    <p class="mt-1 text-sm text-neutral-muted">
                                        {{ selectedVehicle.brand }} ·
                                        {{
                                            categoryLabel(
                                                selectedVehicle.category,
                                            )
                                        }}
                                    </p>
                                </div>
                                <p
                                    class="text-3xl font-extrabold text-[#00B8A9]"
                                >
                                    {{
                                        formatDailyRate(
                                            selectedVehicle.dailyRateCents,
                                        )
                                    }}
                                </p>
                            </div>

                            <p
                                class="mt-4 max-w-3xl text-sm leading-relaxed text-neutral-muted"
                            >
                                {{ vehicleDescription(selectedVehicle) }}
                            </p>

                            <section class="mt-8">
                                <h2 class="text-lg font-bold text-slate-900">
                                    Especificaciones
                                </h2>
                                <dl
                                    class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3"
                                >
                                    <div
                                        v-for="spec in specifications"
                                        :key="spec.label"
                                        class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm"
                                    >
                                        <dt
                                            class="text-[11px] font-bold tracking-widest text-neutral-muted uppercase"
                                        >
                                            {{ spec.label }}
                                        </dt>
                                        <dd
                                            class="mt-1 text-sm font-semibold text-slate-900"
                                        >
                                            {{ spec.value }}
                                        </dd>
                                    </div>
                                </dl>
                            </section>
                        </template>

                        <section
                            v-else
                            class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm"
                        >
                            <h1
                                class="text-2xl font-extrabold tracking-tight text-slate-900"
                            >
                                Elige un vehículo
                            </h1>
                            <p class="mt-2 text-sm text-neutral-muted">
                                Selecciona un auto disponible para ver la
                                galería, las especificaciones y completar la
                                reserva.
                            </p>
                            <label
                                class="mt-6 flex flex-col gap-2 text-[11px] font-bold tracking-widest text-slate-500 uppercase"
                            >
                                Vehículo
                                <select
                                    v-model="form.vehiculo_id"
                                    class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium tracking-normal text-slate-900 normal-case outline-none focus:border-[#00B8A9] focus:ring-2 focus:ring-[#00B8A9]/30"
                                >
                                    <option value="" disabled>
                                        Selecciona un vehículo
                                    </option>
                                    <option
                                        v-for="vehicle in vehicles"
                                        :key="vehicle.id"
                                        :value="String(vehicle.id)"
                                    >
                                        {{ vehicle.label }} ·
                                        {{ categoryLabel(vehicle.category) }}
                                    </option>
                                </select>
                            </label>
                            <p
                                v-if="form.errors.vehiculo_id"
                                class="mt-2 text-xs font-normal text-red-600"
                            >
                                {{ form.errors.vehiculo_id }}
                            </p>
                            <div
                                v-if="vehicles.length === 0"
                                class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"
                            >
                                No encontramos vehículos disponibles para esos
                                criterios. Modifica las fechas o
                                <Link
                                    :href="catalogoUrl"
                                    class="font-semibold underline underline-offset-2"
                                >
                                    revisa el catálogo
                                </Link>
                                .
                            </div>
                        </section>
                    </div>

                    <aside class="lg:col-span-1">
                        <div class="lg:sticky lg:top-24">
                            <form
                                class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm"
                                @submit.prevent="reservar"
                            >
                                <h2 class="text-lg font-bold text-slate-900">
                                    Realizar reserva
                                </h2>

                                <label
                                    v-if="selectedVehicle === null"
                                    class="mt-5 flex flex-col gap-2 text-[11px] font-bold tracking-widest text-slate-500 uppercase"
                                >
                                    Vehículo
                                    <select
                                        v-model="form.vehiculo_id"
                                        required
                                        class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium tracking-normal text-slate-900 normal-case outline-none focus:border-[#00B8A9] focus:ring-2 focus:ring-[#00B8A9]/30"
                                    >
                                        <option value="" disabled>
                                            Selecciona un vehículo
                                        </option>
                                        <option
                                            v-for="vehicle in vehicles"
                                            :key="vehicle.id"
                                            :value="String(vehicle.id)"
                                        >
                                            {{ vehicle.label }} ·
                                            {{
                                                categoryLabel(vehicle.category)
                                            }}
                                        </option>
                                    </select>
                                    <span
                                        v-if="form.errors.vehiculo_id"
                                        class="text-xs font-normal tracking-normal text-red-600 normal-case"
                                    >
                                        {{ form.errors.vehiculo_id }}
                                    </span>
                                </label>

                                <label
                                    class="mt-5 flex flex-col gap-2 text-[11px] font-bold tracking-widest text-slate-500 uppercase"
                                >
                                    Sede de recogida
                                    <select
                                        v-model="sede"
                                        class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium tracking-normal text-slate-900 normal-case outline-none focus:border-[#00B8A9] focus:ring-2 focus:ring-[#00B8A9]/30"
                                    >
                                        <option
                                            v-for="location in pickupLocations"
                                            :key="location"
                                            :value="location"
                                        >
                                            {{ location }}
                                        </option>
                                    </select>
                                </label>

                                <div class="mt-4 grid grid-cols-2 gap-3">
                                    <label
                                        class="flex flex-col gap-2 text-[11px] font-bold tracking-widest text-slate-500 uppercase"
                                    >
                                        Recogida
                                        <input
                                            v-model="form.inicio"
                                            type="date"
                                            required
                                            class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium tracking-normal text-slate-900 outline-none focus:border-[#00B8A9] focus:ring-2 focus:ring-[#00B8A9]/30"
                                        />
                                        <span
                                            v-if="form.errors.inicio"
                                            class="text-xs font-normal tracking-normal text-red-600 normal-case"
                                        >
                                            {{ form.errors.inicio }}
                                        </span>
                                    </label>
                                    <label
                                        class="flex flex-col gap-2 text-[11px] font-bold tracking-widest text-slate-500 uppercase"
                                    >
                                        Devolución
                                        <input
                                            v-model="form.fin"
                                            type="date"
                                            required
                                            class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium tracking-normal text-slate-900 outline-none focus:border-[#00B8A9] focus:ring-2 focus:ring-[#00B8A9]/30"
                                        />
                                        <span
                                            v-if="form.errors.fin"
                                            class="text-xs font-normal tracking-normal text-red-600 normal-case"
                                        >
                                            {{ form.errors.fin }}
                                        </span>
                                    </label>
                                </div>

                                <div
                                    class="mt-6 border-t border-slate-100 pt-5"
                                >
                                    <template v-if="quoteIsCurrent && quote">
                                        <dl class="flex flex-col gap-3 text-sm">
                                            <div
                                                class="flex justify-between gap-4"
                                            >
                                                <dt class="text-neutral-muted">
                                                    Tarifa por
                                                    {{ rentalDays }}
                                                    {{
                                                        rentalDays === 1
                                                            ? 'día'
                                                            : 'días'
                                                    }}
                                                </dt>
                                                <dd
                                                    class="font-semibold text-slate-900"
                                                >
                                                    {{
                                                        pesos(
                                                            quote.subtotalCents,
                                                            quote.currency,
                                                        )
                                                    }}
                                                </dd>
                                            </div>
                                            <div
                                                v-if="quote.discountCents > 0"
                                                class="flex justify-between gap-4"
                                            >
                                                <dt class="text-neutral-muted">
                                                    Descuento
                                                </dt>
                                                <dd
                                                    class="font-semibold text-slate-900"
                                                >
                                                    -{{
                                                        pesos(
                                                            quote.discountCents,
                                                            quote.currency,
                                                        )
                                                    }}
                                                </dd>
                                            </div>
                                            <div
                                                class="flex justify-between gap-4"
                                            >
                                                <dt class="text-neutral-muted">
                                                    Seguro incluido
                                                </dt>
                                                <dd
                                                    class="font-semibold text-[#00B8A9]"
                                                >
                                                    Incluido
                                                </dd>
                                            </div>
                                            <div
                                                v-if="quote.taxesCents > 0"
                                                class="flex justify-between gap-4"
                                            >
                                                <dt class="text-neutral-muted">
                                                    Impuestos
                                                </dt>
                                                <dd
                                                    class="font-semibold text-slate-900"
                                                >
                                                    {{
                                                        pesos(
                                                            quote.taxesCents,
                                                            quote.currency,
                                                        )
                                                    }}
                                                </dd>
                                            </div>
                                            <div
                                                class="mt-1 flex items-end justify-between gap-4 border-t border-slate-100 pt-4"
                                            >
                                                <dt
                                                    class="font-semibold text-slate-900"
                                                >
                                                    Total estimado
                                                </dt>
                                                <dd
                                                    class="text-2xl font-extrabold text-slate-900"
                                                >
                                                    {{
                                                        pesos(
                                                            quote.totalCents,
                                                            quote.currency,
                                                        )
                                                    }}
                                                </dd>
                                            </div>
                                        </dl>
                                    </template>
                                    <p
                                        v-else
                                        class="text-sm text-neutral-muted"
                                    >
                                        {{
                                            quoting
                                                ? 'Calculando el total estimado…'
                                                : 'Selecciona las fechas para ver el total estimado.'
                                        }}
                                    </p>
                                </div>

                                <button
                                    type="submit"
                                    :disabled="!canReserve"
                                    class="mt-6 w-full rounded-xl bg-[#00B8A9] py-3 text-sm font-semibold text-white transition hover:bg-[#00a395] disabled:cursor-not-allowed disabled:bg-slate-300 disabled:hover:bg-slate-300"
                                >
                                    {{
                                        form.processing
                                            ? 'Reservando…'
                                            : 'Reservar ahora'
                                    }}
                                </button>
                                <p
                                    v-if="
                                        form.errors.vehiculo_id &&
                                        selectedVehicle
                                    "
                                    class="mt-3 text-sm text-red-600"
                                >
                                    {{ form.errors.vehiculo_id }}
                                </p>
                                <p
                                    v-if="form.errors.reserva"
                                    class="mt-3 text-sm text-red-600"
                                >
                                    {{ form.errors.reserva }}
                                </p>
                                <p
                                    class="mt-3 text-center text-xs text-neutral-muted"
                                >
                                    {{ rentalDisclaimer }}
                                </p>
                            </form>

                            <section
                                class="mt-6 rounded-2xl border border-slate-100 bg-white p-6 shadow-sm"
                            >
                                <h2 class="text-lg font-bold text-slate-900">
                                    Información del alquiler
                                </h2>
                                <ul class="mt-4 flex flex-col gap-3">
                                    <li
                                        v-for="note in rentalNotes"
                                        :key="note"
                                        class="flex items-start gap-3 text-sm text-slate-700"
                                    >
                                        <span
                                            class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#00B8A9]/10 text-[#00B8A9]"
                                            aria-hidden="true"
                                        >
                                            <svg
                                                class="h-3.5 w-3.5"
                                                viewBox="0 0 16 16"
                                                fill="none"
                                            >
                                                <path
                                                    d="M3.5 8.2 6.4 11 12.5 4.8"
                                                    stroke="currentColor"
                                                    stroke-width="1.8"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                />
                                            </svg>
                                        </span>
                                        {{ note }}
                                    </li>
                                </ul>
                            </section>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </StorefrontLayout>
</template>
