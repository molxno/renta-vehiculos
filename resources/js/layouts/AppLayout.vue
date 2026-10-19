<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { index as entregasIndex } from '@/actions/App/Http/Controllers/EntregaController';
import { index as reservasIndex } from '@/actions/App/Http/Controllers/ReservaController';
import { edit as editarTrm } from '@/actions/App/Http/Controllers/TrmController';
import { login, logout } from '@/routes';
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
    administrador: [
        'Dashboard',
        'Tasa de cambio',
        'Entregas',
        'Usuarios',
        'Reportes',
    ],
    gerente: ['Dashboard', 'Reportes', 'Indicadores'],
} as const;

const navigationHrefs: Record<string, string> = {
    'Mis reservas': reservasIndex.url(),
    Reservas: reservasIndex.url(),
    Entregas: entregasIndex.url(),
    'Tasa de cambio': editarTrm.url(),
};
const loginUrl = login.url();
const logoutUrl = logout.url();
</script>

<template>
    <div
        class="flex min-h-screen flex-col bg-brand-navy text-slate-800 antialiased"
    >
        <header class="sticky top-0 z-50 bg-brand-navy text-white">
            <div
                class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-6 px-6"
            >
                <Link
                    href="/"
                    class="text-lg font-bold tracking-tight text-white"
                >
                    Renta Vehículos
                </Link>
                <nav class="hidden items-center gap-5 md:flex">
                    <Link
                        v-for="item in navigation[currentRole]"
                        :key="item"
                        :href="navigationHrefs[item] ?? '#'"
                        class="text-sm font-medium text-white/80 hover:text-brand-teal"
                    >
                        {{ item }}
                    </Link>
                </nav>
                <Link
                    v-if="authUser"
                    :href="logoutUrl"
                    method="post"
                    class="rounded-lg bg-brand-teal px-4 py-2 text-sm font-semibold text-white hover:bg-brand-teal-dark"
                >
                    Cerrar sesión
                </Link>
                <Link
                    v-else
                    :href="loginUrl"
                    class="rounded-lg bg-brand-teal px-4 py-2 text-sm font-semibold text-white hover:bg-brand-teal-dark"
                >
                    Iniciar sesión
                </Link>
            </div>
        </header>
        <main class="w-full flex-1 bg-slate-50">
            <div class="mx-auto max-w-7xl px-6 py-8">
                <slot />
            </div>
        </main>
    </div>
</template>
