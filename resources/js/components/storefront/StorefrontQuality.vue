<script setup lang="ts">
import { mockQualities } from '@/components/storefront/mocks';
import StorefrontSoftIcon from '@/components/storefront/StorefrontSoftIcon.vue';
</script>

<template>
    <!-- TODO: REEMPLAZAR MOCK CON PROPS DINÁMICAS DEL BACKEND CUANDO EL CATÁLOGOS/RESERVAS ESTÉ LISTO -->
    <section class="mx-auto max-w-7xl px-6 py-20">
        <p
            class="text-sm font-semibold tracking-wide text-brand-teal uppercase"
        >
            Por qué RENT-GO
        </p>
        <h2 class="mt-2 text-3xl font-semibold text-brand-navy">
            Calidad en cada detalle
        </h2>
        <div class="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
            <article
                v-for="quality in mockQualities"
                :key="quality.title"
                class="rounded-2xl border border-slate-200 bg-white p-6"
            >
                <span
                    class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-teal/10 text-brand-teal"
                >
                    <StorefrontSoftIcon :name="quality.icon" />
                </span>
                <h3 class="mt-4 text-lg font-semibold text-brand-navy">
                    {{ quality.title }}
                </h3>
                <p class="mt-2 text-sm text-slate-600">
                    {{ quality.description }}
                </p>
            </article>
        </div>
    </section>
</template>
