<script setup lang="ts">
import { heroImage } from '@/components/storefront/mocks';
import { catalogo } from '@/routes';
import type { HomeSearch } from '@/types';

defineProps<{
    search: HomeSearch;
}>();

const catalogoUrl = catalogo.url();
</script>

<template>
    <section
        id="inicio"
        class="relative flex min-h-[calc(100vh-80px)] items-center justify-center overflow-hidden bg-brand-navy text-white"
    >
        <img
            :src="heroImage"
            alt="Vehículo de la flota RENT-GO en carretera"
            class="absolute inset-0 h-full w-full object-cover opacity-40 mix-blend-overlay"
        />
        <div
            class="relative mx-auto flex w-full max-w-5xl flex-col items-center px-6 pt-16 pb-28 text-center"
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
            <!-- TODO: REEMPLAZAR MOCK CON PROPS DINÁMICAS DEL BACKEND CUANDO EL CATÁLOGOS/RESERVAS ESTÉ LISTO -->
            <form
                method="get"
                :action="catalogoUrl"
                class="relative mt-10 grid w-full gap-3 rounded-2xl bg-white p-4 text-left text-slate-900 shadow-xl md:grid-cols-4"
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
        </div>
    </section>
</template>
