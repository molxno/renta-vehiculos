<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { create as register } from '@/actions/App/Http/Controllers/Auth/RegistroController';
</script>

<template>
    <section class="px-6 pb-20">
        <div
            class="mx-auto max-w-7xl rounded-3xl bg-brand-navy px-8 py-16 text-center text-white"
        >
            <h2 class="text-3xl font-semibold md:text-4xl">
                ¿Listo para tu próxima aventura?
            </h2>
            <p class="mx-auto mt-3 max-w-xl text-white/75">
                Explora la flota, elige fechas y reserva en minutos. Sin
                sorpresas al recoger el vehículo.
            </p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a
                    href="#vehiculos"
                    class="rounded-lg bg-brand-teal px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-teal-dark"
                >
                    Explorar catálogo
                </a>
                <Link
                    :href="register.url()"
                    class="rounded-lg border border-white/30 px-5 py-2.5 text-sm font-semibold text-white hover:bg-white/10"
                >
                    Crear cuenta gratis
                </Link>
            </div>
        </div>
    </section>
</template>
