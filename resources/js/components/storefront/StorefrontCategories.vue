<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { mockCategories } from '@/components/storefront/mocks';
import StorefrontSoftIcon from '@/components/storefront/StorefrontSoftIcon.vue';
import { catalogo } from '@/routes';

const catalogoUrl = catalogo.url();
</script>

<template>
    <!-- TODO: REEMPLAZAR MOCK CON PROPS DINÁMICAS DEL BACKEND CUANDO EL CATÁLOGOS/RESERVAS ESTÉ LISTO -->
    <section id="categorias" class="bg-slate-50 py-20">
        <div class="mx-auto max-w-7xl px-6">
            <p
                class="text-sm font-semibold tracking-wide text-brand-teal uppercase"
            >
                Explora por tipo
            </p>
            <h2 class="mt-2 text-3xl font-semibold text-brand-navy">
                Categorías de vehículos
            </h2>
            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="category in mockCategories"
                    :key="category.name"
                    :href="catalogoUrl"
                    class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-teal hover:shadow-md"
                >
                    <span
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-teal/10 text-brand-teal"
                    >
                        <StorefrontSoftIcon :name="category.icon" />
                    </span>
                    <span>
                        <span class="block font-semibold text-brand-navy">
                            {{ category.name }}
                        </span>
                        <span class="text-sm text-slate-500">
                            {{ category.description }}
                        </span>
                    </span>
                </Link>
            </div>
        </div>
    </section>
</template>
