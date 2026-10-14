<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Button from '@/components/ui/Button.vue';

withDefaults(
    defineProps<{
        role?:
            | 'cliente'
            | 'coordinador'
            | 'cajero'
            | 'administrador'
            | 'gerente';
    }>(),
    { role: 'cliente' },
);

const navigation = {
    cliente: ['Mis reservas', 'Facturas', 'Pagos'],
    coordinador: ['Reservas', 'Entregas', 'Devoluciones'],
    cajero: ['Facturas', 'Pagos', 'Cierres de caja'],
    administrador: ['Dashboard', 'Usuarios', 'Reportes'],
    gerente: ['Dashboard', 'Reportes', 'Indicadores'],
} as const;
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-900">
        <header class="border-b border-slate-200 bg-white">
            <div
                class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-6 py-4"
            >
                <Link
                    href="/"
                    class="text-lg font-bold tracking-tight text-brand-ink"
                >
                    Renta Vehículos
                </Link>
                <nav class="hidden items-center gap-5 md:flex">
                    <a
                        v-for="item in navigation[role]"
                        :key="item"
                        href="#"
                        class="text-sm font-medium text-slate-600 hover:text-brand-ink"
                    >
                        {{ item }}
                    </a>
                </nav>
                <Button variant="secondary">Mi cuenta</Button>
            </div>
        </header>
        <main class="mx-auto max-w-7xl px-6 py-8">
            <slot />
        </main>
    </div>
</template>
