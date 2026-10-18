<script setup lang="ts">
import { ref } from 'vue';
import { mockFaqs } from '@/components/storefront/mocks';

const openIndex = ref(0);
</script>

<template>
    <!-- TODO: REEMPLAZAR MOCK CON PROPS DINÁMICAS DEL BACKEND CUANDO EL CATÁLOGOS/RESERVAS ESTÉ LISTO -->
    <section class="mx-auto max-w-4xl px-6 py-20">
        <p
            class="text-center text-sm font-semibold tracking-wide text-brand-teal uppercase"
        >
            Resolvemos tus dudas
        </p>
        <h2 class="mt-2 text-center text-3xl font-semibold text-brand-navy">
            Preguntas frecuentes
        </h2>
        <div
            class="mt-8 divide-y divide-slate-200 rounded-2xl border border-slate-200 bg-white"
        >
            <div v-for="(faq, index) in mockFaqs" :key="faq.question">
                <button
                    type="button"
                    class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left font-semibold text-brand-navy"
                    :aria-expanded="openIndex === index"
                    @click="openIndex = openIndex === index ? -1 : index"
                >
                    {{ faq.question }}
                    <span class="text-brand-teal" aria-hidden="true">
                        {{ openIndex === index ? '−' : '+' }}
                    </span>
                </button>
                <p
                    v-if="openIndex === index"
                    class="px-5 pb-4 text-sm text-slate-600"
                >
                    {{ faq.answer }}
                </p>
            </div>
        </div>
    </section>
</template>
