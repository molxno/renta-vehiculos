<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Button from '@/components/ui/Button.vue';

withDefaults(
    defineProps<{
        role?: 'admin' | 'operador' | 'cliente';
    }>(),
    { role: 'operador' },
);

const navigation = {
    admin: ['Dashboard', 'Usuarios', 'Reportes'],
    operador: ['Reservas', 'Entregas', 'Devoluciones'],
    cliente: ['Mis reservas', 'Facturas', 'Pagos'],
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
