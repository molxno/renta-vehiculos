<script setup lang="ts">
import { mockTestimonials } from '@/components/storefront/mocks';
</script>

<template>
    <!-- TODO: REEMPLAZAR MOCK CON PROPS DINÁMICAS DEL BACKEND CUANDO EL CATÁLOGOS/RESERVAS ESTÉ LISTO -->
    <section class="bg-slate-50 py-20">
        <div class="mx-auto max-w-7xl px-6">
            <p
                class="text-sm font-semibold tracking-wide text-brand-teal uppercase"
            >
                Clientes satisfechos
            </p>
            <h2 class="mt-2 text-3xl font-semibold text-brand-navy">
                Testimonios
            </h2>
            <div class="mt-8 grid gap-6 md:grid-cols-3">
                <article
                    v-for="testimonial in mockTestimonials"
                    :key="testimonial.name"
                    class="rounded-2xl border border-slate-200 bg-white p-6"
                >
                    <p class="text-brand-teal" aria-label="5 estrellas">
                        ★★★★★
                    </p>
                    <p class="mt-3 text-sm text-slate-700">
                        “{{ testimonial.quote }}”
                    </p>
                    <p class="mt-4 font-semibold text-brand-navy">
                        {{ testimonial.name }}
                    </p>
                    <p class="text-xs text-slate-500">
                        {{ testimonial.role }}
                    </p>
                </article>
            </div>
        </div>
    </section>
</template>
