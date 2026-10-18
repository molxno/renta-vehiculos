<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { login, logout } from '@/routes';
import { index as reservasIndex } from '@/actions/App/Http/Controllers/ReservaController';
import { edit as editarTrm } from '@/actions/App/Http/Controllers/TrmController';
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
    administrador: ['Dashboard', 'Tasa de cambio', 'Usuarios', 'Reportes'],
    gerente: ['Dashboard', 'Reportes', 'Indicadores'],
} as const;

const navigationHrefs: Record<string, string> = {
    'Mis reservas': reservasIndex.url(),
    Reservas: reservasIndex.url(),
    'Tasa de cambio': editarTrm.url(),
};
const loginUrl = login.url();
const logoutUrl = logout.url();
</script>

<template>
    <div
        class="flex min-h-screen flex-col bg-brand-navy text-slate-800 antialiased"
    >
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
                <Link v-if="authUser" :href="logoutUrl" method="post">
                    <Button variant="secondary">Cerrar sesión</Button>
                </Link>
                <Link v-else :href="loginUrl">
                    <Button variant="secondary">Iniciar sesión</Button>
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
