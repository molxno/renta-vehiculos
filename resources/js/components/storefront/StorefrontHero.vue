<script setup lang="ts">
import { create as crearReserva } from '@/actions/App/Http/Controllers/ReservaController';
import { heroImage } from '@/components/storefront/mocks';
import type { HomeSearch } from '@/types';

defineProps<{
    search: HomeSearch;
}>();

const crearReservaUrl = crearReserva.url();
</script>

<template>
    <section id="inicio" class="relative isolate min-h-[34rem] overflow-hidden">
        <img
            :src="heroImage"
            alt="Vehículo de la flota RENT-GO en carretera"
            class="absolute inset-0 h-full w-full object-cover"
        />
        <div
            class="absolute inset-0 bg-linear-to-b from-brand-navy/75 via-brand-navy/55 to-brand-navy/85"
        />
        <div
            class="relative mx-auto flex max-w-5xl flex-col items-center px-6 pt-24 pb-28 text-center text-white"
        >
            <p
                class="rounded-full border border-white/20 bg-white/10 px-4 py-1 text-xs font-semibold tracking-wide uppercase"
            >
                +40 vehículos disponibles hoy
            </p>
            <h1
                class="mt-6 max-w-3xl text-4xl font-semibold tracking-tight md:text-6xl"
            >
                Tu próximo viaje
                <span class="text-brand-teal">comienza aquí</span>
            </h1>
            <p class="mt-4 max-w-xl text-lg text-white/80">
                Encuentra, reserva y disfruta el vehículo ideal para cada viaje.
            </p>
        </div>
        <!-- TODO: REEMPLAZAR MOCK CON PROPS DINÁMICAS DEL BACKEND CUANDO EL CATÁLOGOS/RESERVAS ESTÉ LISTO -->
        <form
            method="get"
            :action="crearReservaUrl"
            class="relative mx-auto mb-[-4.5rem] grid max-w-5xl gap-3 rounded-2xl bg-white p-4 text-left text-slate-900 shadow-xl md:grid-cols-4"
        >
            <label class="flex flex-col gap-1.5 text-sm font-medium">
                Sede de recogida
                <input
                    name="location"
                    :value="search.location"
                    placeholder="Ciudad o sucursal"
                    class="rounded-lg border border-slate-200 px-3 py-2 outline-none focus:border-brand-teal focus:ring-2 focus:ring-brand-teal/30"
                />
            </label>
            <label class="flex flex-col gap-1.5 text-sm font-medium">
                Fecha de recogida
                <input
                    name="startDate"
                    type="date"
                    :value="search.startDate"
                    class="rounded-lg border border-slate-200 px-3 py-2 outline-none focus:border-brand-teal focus:ring-2 focus:ring-brand-teal/30"
                />
            </label>
            <label class="flex flex-col gap-1.5 text-sm font-medium">
                Fecha de devolución
                <input
                    name="endDate"
                    type="date"
                    :value="search.endDate"
                    class="rounded-lg border border-slate-200 px-3 py-2 outline-none focus:border-brand-teal focus:ring-2 focus:ring-brand-teal/30"
                />
            </label>
            <button
                type="submit"
                class="self-end rounded-lg bg-brand-teal px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-teal-dark"
            >
                Buscar vehículos
            </button>
        </form>
    </section>
</template>
