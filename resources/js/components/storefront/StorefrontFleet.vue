<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { create as crearReserva } from '@/actions/App/Http/Controllers/ReservaController';
import { mockFleet } from '@/components/storefront/mocks';
</script>

<template>
    <!-- TODO: REEMPLAZAR MOCK CON PROPS DINÁMICAS DEL BACKEND CUANDO EL CATÁLOGOS/RESERVAS ESTÉ LISTO -->
    <section id="vehiculos" class="mx-auto max-w-7xl px-6 pt-28 pb-20">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p
                    class="text-sm font-semibold tracking-wide text-brand-teal uppercase"
                >
                    Flota destacada
                </p>
                <h2 class="mt-2 text-3xl font-semibold text-brand-navy">
                    Vehículos más solicitados
                </h2>
            </div>
            <a
                href="#categorias"
                class="text-sm font-semibold text-brand-blue hover:text-brand-teal"
            >
                Ver todo el catálogo →
            </a>
        </div>
        <div class="mt-8 grid gap-6 md:grid-cols-3">
            <article
                v-for="vehicle in mockFleet"
                :key="vehicle.name"
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
                <div class="relative">
                    <img
                        :src="vehicle.image"
                        :alt="vehicle.name"
                        class="h-48 w-full object-cover"
                    />
                    <span
                        class="absolute top-3 left-3 rounded-full bg-emerald-500 px-3 py-1 text-xs font-semibold text-white"
                    >
                        Disponible
                    </span>
                    <span
                        class="absolute top-3 right-3 rounded-full bg-brand-navy px-3 py-1 text-xs font-semibold text-white"
                    >
                        {{ vehicle.price }}
                    </span>
                </div>
                <div class="p-5">
                    <p
                        class="text-xs font-semibold tracking-wide text-brand-blue uppercase"
                    >
                        {{ vehicle.category }}
                    </p>
                    <h3 class="mt-1 text-lg font-semibold text-brand-navy">
                        {{ vehicle.name }}
                    </h3>
                    <ul
                        class="mt-3 flex flex-wrap gap-2 text-xs text-slate-600"
                    >
                        <li class="rounded-full bg-slate-100 px-3 py-1">
                            {{ vehicle.passengers }} pasajeros
                        </li>
                        <li class="rounded-full bg-slate-100 px-3 py-1">
                            {{ vehicle.transmission }}
                        </li>
                        <li class="rounded-full bg-slate-100 px-3 py-1">
                            {{ vehicle.fuel }}
                        </li>
                    </ul>
                    <div class="mt-5 flex gap-3">
                        <Link
                            :href="crearReserva.url()"
                            class="inline-flex flex-1 justify-center rounded-lg border border-brand-navy px-3 py-2 text-sm font-semibold text-brand-navy hover:bg-slate-50"
                        >
                            Ver detalles
                        </Link>
                        <Link
                            :href="crearReserva.url()"
                            class="inline-flex flex-1 justify-center rounded-lg bg-brand-teal px-3 py-2 text-sm font-semibold text-white hover:bg-brand-teal-dark"
                        >
                            Reservar
                        </Link>
                    </div>
                </div>
            </article>
        </div>
    </section>
</template>
