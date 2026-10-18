<script setup lang="ts">
import { computed, ref } from 'vue';
import { mockPolicies, policyImage } from '@/components/storefront/mocks';

const activeId = ref(mockPolicies[0]?.id ?? '');
const activePolicy = computed(
    () =>
        mockPolicies.find((policy) => policy.id === activeId.value) ??
        mockPolicies[0],
);
</script>

<template>
    <!-- TODO: REEMPLAZAR MOCK CON PROPS DINÁMICAS DEL BACKEND CUANDO EL CATÁLOGOS/RESERVAS ESTÉ LISTO -->
    <section class="mx-auto max-w-7xl px-6 py-20">
        <p
            class="text-sm font-semibold tracking-wide text-brand-teal uppercase"
        >
            Tranquilidad al volante
        </p>
        <h2 class="mt-2 text-3xl font-semibold text-brand-navy">
            Pólizas y coberturas
        </h2>
        <div class="mt-8 grid items-start gap-8 lg:grid-cols-2">
            <div class="space-y-3">
                <button
                    v-for="policy in mockPolicies"
                    :key="policy.id"
                    type="button"
                    class="w-full rounded-2xl border px-5 py-4 text-left transition"
                    :class="
                        policy.id === activeId
                            ? 'border-brand-teal bg-brand-teal/5'
                            : 'border-slate-200 bg-white hover:border-brand-teal/40'
                    "
                    @click="activeId = policy.id"
                >
                    <span class="block font-semibold text-brand-navy">
                        {{ policy.title }}
                    </span>
                    <span
                        v-if="policy.id === activeId"
                        class="mt-2 block text-sm text-slate-600"
                    >
                        {{ policy.description }}
                    </span>
                </button>
            </div>
            <figure
                class="overflow-hidden rounded-3xl border border-slate-200 bg-slate-100"
            >
                <img
                    :src="policyImage"
                    alt="Vehículo cubierto por póliza RENT-GO"
                    class="h-80 w-full object-cover"
                />
                <figcaption class="p-6">
                    <p class="text-sm font-semibold text-brand-teal">
                        Auto asegurado
                    </p>
                    <p class="mt-1 text-lg font-semibold text-brand-navy">
                        {{ activePolicy?.title }}
                    </p>
                    <p class="mt-2 text-sm text-slate-600">
                        {{ activePolicy?.description }}
                    </p>
                </figcaption>
            </figure>
        </div>
    </section>
</template>
