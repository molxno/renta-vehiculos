<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    create as login,
    destroy as logout,
} from '@/actions/App/Http/Controllers/Auth/SesionController';
import { index as reservasIndex } from '@/actions/App/Http/Controllers/ReservaController';
import Button from '@/components/ui/Button.vue';
import type { UserRole } from '@/types';

const props = withDefaults(
    defineProps<{
        role?: UserRole;
    }>(),
    { role: 'cliente' },
);

const page = usePage();
const authUser = computed(() => page.props.auth?.user ?? null);
const currentRole = computed(() => authUser.value?.role ?? props.role);

const navigation = {
    cliente: ['Mis reservas', 'Facturas', 'Pagos'],
    coordinador: ['Reservas', 'Entregas', 'Devoluciones'],
    cajero: ['Facturas', 'Pagos', 'Cierres de caja'],
    administrador: ['Dashboard', 'Usuarios', 'Reportes'],
    gerente: ['Dashboard', 'Reportes', 'Indicadores'],
} as const;

const navigationHrefs: Record<string, string> = {
    'Mis reservas': reservasIndex.url(),
    Reservas: reservasIndex.url(),
};
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
                        v-for="item in navigation[currentRole]"
                        :key="item"
                        :href="navigationHrefs[item] ?? '#'"
                        class="text-sm font-medium text-slate-600 hover:text-brand-ink"
                    >
                        {{ item }}
                    </a>
                </nav>
                <Link v-if="authUser" :href="logout.url()" method="post">
                    <Button variant="secondary">Cerrar sesión</Button>
                </Link>
                <Link v-else :href="login.url()">
                    <Button variant="secondary">Iniciar sesión</Button>
                </Link>
            </div>
        </header>
        <main class="mx-auto max-w-7xl px-6 py-8">
            <slot />
        </main>
    </div>
</template>
