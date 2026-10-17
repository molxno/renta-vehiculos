<script setup lang="ts">
import { mockSteps } from '@/components/storefront/mocks';
</script>

<template>
    <!-- TODO: REEMPLAZAR MOCK CON PROPS DINÁMICAS DEL BACKEND CUANDO EL CATÁLOGOS/RESERVAS ESTÉ LISTO -->
    <section id="como-funciona" class="bg-brand-navy py-20 text-white">
        <div class="mx-auto max-w-7xl px-6">
            <p
                class="text-sm font-semibold tracking-wide text-brand-teal uppercase"
            >
                Proceso simple
            </p>
            <h2 class="mt-2 text-3xl font-semibold">¿Cómo funciona?</h2>
            <div class="mt-10 grid gap-6 md:grid-cols-4">
                <article
                    v-for="step in mockSteps"
                    :key="step.number"
                    class="rounded-2xl border border-white/10 bg-white/5 p-6"
                >
                    <p class="text-3xl font-semibold text-brand-teal">
                        {{ step.number }}
                    </p>
                    <h3 class="mt-4 text-lg font-semibold">
                        {{ step.title }}
                    </h3>
                    <p class="mt-2 text-sm text-white/70">
                        {{ step.description }}
                    </p>
                </article>
            </div>
        </div>
    </section>
</template>
