<script setup lang="ts">
import { heroImage } from '@/components/storefront/mocks';
import { pickupLocations } from '@/lib/vehiclePresentation';
import { catalogo } from '@/routes';
import type { HomeSearch } from '@/types';

defineProps<{
    search: HomeSearch;
    vehiclesAvailableCount: number;
}>();

const catalogoUrl = catalogo.url();
</script>

<template>
    <section
        id="inicio"
        class="relative flex min-h-[calc(100vh-4rem)] items-center justify-center bg-brand-navy text-white"
    >
        <div class="absolute inset-0 overflow-hidden">
            <img
                :src="heroImage"
                alt="Vehículo de la flota RENT-GO en carretera"
                class="h-full w-full object-cover opacity-40 mix-blend-overlay"
            />
        </div>
        <div
            class="relative mx-auto flex w-full max-w-5xl flex-col items-center px-6 pt-16 pb-16 text-center"
        >
            <p
                class="rounded-full border border-white/20 bg-white/10 px-4 py-1 text-xs font-semibold tracking-wide uppercase"
            >
                {{ vehiclesAvailableCount }}
                {{
                    vehiclesAvailableCount === 1
                        ? 'vehículo disponible hoy'
                        : 'vehículos disponibles hoy'
                }}
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
            <form
                method="get"
                :action="catalogoUrl"
                class="relative mt-10 grid w-full max-w-4xl gap-3 rounded-[28px] bg-white p-3 text-left text-slate-900 shadow-xl sm:grid-cols-2 lg:grid-cols-[1.3fr_1fr_1fr_auto] lg:items-end lg:gap-2 lg:p-4"
            >
                <label
                    class="flex min-w-0 flex-col gap-1 px-3 py-1.5 text-[10px] font-bold tracking-widest text-slate-400 uppercase"
                >
                    Sede de recogida
                    <select
                        name="location"
                        :value="search.location"
                        class="w-full appearance-none border-0 bg-transparent p-0 text-sm font-medium tracking-normal text-slate-900 normal-case outline-none"
                    >
                        <option value="">Seleccionar sede</option>
                        <option
                            v-for="location in pickupLocations"
                            :key="location"
                            :value="location"
                        >
                            {{ location }}
                        </option>
                    </select>
                </label>
                <label
                    class="flex min-w-0 flex-col gap-1 px-3 py-1.5 text-[10px] font-bold tracking-widest text-slate-400 uppercase"
                >
                    Fecha de recogida
                    <input
                        name="startDate"
                        type="date"
                        :value="search.startDate"
                        class="w-full border-0 bg-transparent p-0 text-sm font-medium tracking-normal text-slate-900 normal-case outline-none"
                    />
                </label>
                <label
                    class="flex min-w-0 flex-col gap-1 px-3 py-1.5 text-[10px] font-bold tracking-widest text-slate-400 uppercase"
                >
                    Fecha de devolución
                    <input
                        name="endDate"
                        type="date"
                        :value="search.endDate"
                        class="w-full border-0 bg-transparent p-0 text-sm font-medium tracking-normal text-slate-900 normal-case outline-none"
                    />
                </label>
                <button
                    type="submit"
                    class="rounded-xl bg-brand-teal px-5 py-3 text-sm font-semibold text-white hover:bg-brand-teal-dark lg:mb-1"
                >
                    Buscar vehículos
                </button>
            </form>
        </div>
    </section>
</template>
