<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { create as crearReserva } from '@/actions/App/Http/Controllers/ReservaController';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import { catalogo, home } from '@/routes';
import type { CatalogFilters, CatalogVehicle } from '@/types';

const props = defineProps<{
    vehicles: CatalogVehicle[];
    categories: string[];
    filters: CatalogFilters;
}>();

const homeUrl = home.url();
const catalogCount = props.vehicles.length;

const categoryImages: Record<string, string> = {
    sedan: 'https://images.unsplash.com/photo-1550242499-b5171f56de56?w=600&h=400&fit=crop&auto=format',
    camioneta:
        'https://images.unsplash.com/photo-1606590526453-9b8ee4569a70?w=600&h=400&fit=crop&auto=format',
    compacto:
        'https://images.unsplash.com/photo-1672024110512-f7028b49db28?w=600&h=400&fit=crop&auto=format',
};

const statusLabels: Record<CatalogVehicle['status'], string> = {
    disponible: 'Disponible',
    alquilado: 'Alquilado',
    contingencia: 'Contingencia',
    mantenimiento: 'Mantenimiento',
};

function categoryLabel(category: string): string {
    const labels: Record<string, string> = {
        sedan: 'Sedán',
        camioneta: 'Camioneta',
        compacto: 'Compacto',
    };

    return labels[category] ?? category;
}

function filterQuery(
    overrides: Partial<CatalogFilters> = {},
): Record<string, string> {
    const next = { ...props.filters, ...overrides };
    const query: Record<string, string> = {};

    if (next.category !== '') {
        query.category = next.category;
    }

    if (next.startDate !== '') {
        query.startDate = next.startDate;
    }

    if (next.endDate !== '') {
        query.endDate = next.endDate;
    }

    if (next.location !== '') {
        query.location = next.location;
    }

    return query;
}

function formatDailyRate(cents: number): string {
    if (cents <= 0) {
        return 'Consultar';
    }

    return `${new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        maximumFractionDigits: 0,
    }).format(Math.round(cents / 100))}/día`;
}

function vehicleImage(vehicle: CatalogVehicle): string {
    return (
        categoryImages[vehicle.category] ??
        'https://images.unsplash.com/photo-1617814086906-d847a8bc6fca?w=600&h=400&fit=crop&auto=format'
    );
}

function reservationUrl(vehicle: CatalogVehicle): string {
    const query: Record<string, string | number> = {
        vehiculo_id: vehicle.id,
    };

    if (props.filters.startDate !== '') {
        query.inicio = props.filters.startDate;
    }

    if (props.filters.endDate !== '') {
        query.fin = props.filters.endDate;
    }

    return crearReserva.url({ query });
}
</script>

<template>
    <StorefrontLayout>
        <Head title="Catálogo de vehículos" />
        <section class="bg-brand-navy text-white">
            <div class="mx-auto max-w-7xl px-6 pt-10 pb-12">
                <p class="text-sm text-white/70">
                    <Link :href="homeUrl" class="hover:text-brand-teal"
                        >Inicio</Link
                    >
                    <span class="px-2">/</span>
                    <span class="text-brand-teal">Catálogo</span>
                </p>
                <h1
                    class="mt-4 text-3xl font-extrabold tracking-tight md:text-4xl"
                >
                    Catálogo de vehículos
                </h1>
                <p class="mt-2 text-sm text-white/70">
                    {{ catalogCount }}
                    {{
                        catalogCount === 1
                            ? 'vehículo encontrado'
                            : 'vehículos encontrados'
                    }}
                </p>
            </div>
        </section>
        <section class="mx-auto max-w-7xl px-6 py-8">
            <form
                method="get"
                :action="catalogo.url()"
                class="mb-6 grid gap-3 rounded-2xl border border-neutral-border bg-white p-4 md:grid-cols-4"
            >
                <input
                    type="hidden"
                    name="category"
                    :value="filters.category"
                />
                <input
                    type="hidden"
                    name="location"
                    :value="filters.location"
                />
                <label class="flex flex-col gap-1.5 text-sm font-medium">
                    Fecha de recogida
                    <input
                        name="startDate"
                        type="date"
                        :value="filters.startDate"
                        class="rounded-lg border border-neutral-border px-3 py-2 outline-none focus:border-brand-teal focus:ring-2 focus:ring-brand-teal/30"
                    />
                </label>
                <label class="flex flex-col gap-1.5 text-sm font-medium">
                    Fecha de devolución
                    <input
                        name="endDate"
                        type="date"
                        :value="filters.endDate"
                        class="rounded-lg border border-neutral-border px-3 py-2 outline-none focus:border-brand-teal focus:ring-2 focus:ring-brand-teal/30"
                    />
                </label>
                <div class="flex items-end md:col-span-2">
                    <button
                        type="submit"
                        class="w-full rounded-lg bg-brand-teal px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-teal-dark"
                    >
                        Filtrar por fechas
                    </button>
                </div>
            </form>
            <div class="grid gap-6 lg:grid-cols-[240px_minmax(0,1fr)]">
                <aside
                    class="h-fit rounded-2xl border border-neutral-border bg-white p-6"
                >
                    <p
                        class="text-xs font-bold tracking-widest text-neutral-muted uppercase"
                    >
                        Categoría
                    </p>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li>
                            <Link
                                :href="
                                    catalogo.url({
                                        query: filterQuery({ category: '' }),
                                    })
                                "
                                class="flex items-center gap-2"
                            >
                                <span
                                    class="flex h-4 w-4 items-center justify-center rounded-full border border-brand-teal"
                                >
                                    <span
                                        v-if="filters.category === ''"
                                        class="h-2.5 w-2.5 rounded-full bg-brand-teal"
                                    />
                                </span>
                                Todas
                            </Link>
                        </li>
                        <li v-for="category in categories" :key="category">
                            <Link
                                :href="
                                    catalogo.url({
                                        query: filterQuery({ category }),
                                    })
                                "
                                class="flex items-center gap-2"
                            >
                                <span
                                    class="flex h-4 w-4 items-center justify-center rounded-full border border-brand-teal"
                                >
                                    <span
                                        v-if="filters.category === category"
                                        class="h-2.5 w-2.5 rounded-full bg-brand-teal"
                                    />
                                </span>
                                {{ categoryLabel(category) }}
                            </Link>
                        </li>
                    </ul>
                </aside>
                <div
                    v-if="vehicles.length === 0"
                    class="rounded-2xl border border-dashed border-neutral-border bg-white px-6 py-24 text-center text-neutral-muted"
                >
                    No encontramos vehículos con esos criterios. Prueba otras
                    fechas o otra categoría.
                </div>
                <div v-else class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                    <article
                        v-for="vehicle in vehicles"
                        :key="vehicle.id"
                        class="overflow-hidden rounded-2xl border border-neutral-border bg-white shadow-sm"
                    >
                        <div class="relative">
                            <img
                                :src="vehicleImage(vehicle)"
                                :alt="vehicle.label"
                                class="h-44 w-full object-cover"
                            />
                            <span
                                class="absolute top-3 left-3 rounded-full px-2.5 py-1 text-xs font-semibold text-white"
                                :class="{
                                    'bg-emerald-500':
                                        vehicle.status === 'disponible',
                                    'bg-brand-blue':
                                        vehicle.status === 'alquilado',
                                    'bg-amber-500':
                                        vehicle.status === 'contingencia',
                                    'bg-red-600':
                                        vehicle.status === 'mantenimiento',
                                }"
                            >
                                {{ statusLabels[vehicle.status] }}
                            </span>
                            <span
                                class="absolute top-3 right-3 rounded-full bg-white/90 px-2.5 py-1 text-xs font-semibold text-neutral-ink"
                            >
                                {{ formatDailyRate(vehicle.dailyRateCents) }}
                            </span>
                        </div>
                        <div class="p-5">
                            <p
                                class="text-xs font-semibold tracking-wide text-brand-blue uppercase"
                            >
                                {{ vehicle.brand }}
                            </p>
                            <div
                                class="mt-1 flex items-start justify-between gap-3"
                            >
                                <h2
                                    class="text-lg font-semibold text-brand-navy"
                                >
                                    {{ vehicle.model }}
                                </h2>
                                <span
                                    class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700"
                                >
                                    {{ categoryLabel(vehicle.category) }}
                                </span>
                            </div>
                            <p class="mt-3 text-xs text-neutral-muted">
                                {{ vehicle.year }}
                            </p>
                            <div class="mt-5 flex gap-3">
                                <Link
                                    :href="reservationUrl(vehicle)"
                                    class="inline-flex flex-1 justify-center rounded-lg border border-brand-navy px-3 py-2 text-sm font-semibold text-brand-navy hover:bg-slate-50"
                                >
                                    Ver detalles
                                </Link>
                                <Link
                                    :href="reservationUrl(vehicle)"
                                    class="inline-flex flex-1 justify-center rounded-lg bg-brand-teal px-3 py-2 text-sm font-semibold text-white hover:bg-brand-teal-dark"
                                >
                                    Reservar
                                </Link>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </section>
    </StorefrontLayout>
</template>
