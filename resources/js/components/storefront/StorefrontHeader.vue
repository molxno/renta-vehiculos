<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import RentGoLogo from '@/components/storefront/RentGoLogo.vue';
import { home, login, logout, register } from '@/routes';
import type { UserRole } from '@/types';

const page = usePage();
const menuAbierto = ref(false);
const authUser = computed(() => page.props.auth?.user ?? null);
const navigation = computed(() => page.props.navigation ?? []);
const homeUrl = home.url();
const loginUrl = login.url();
const registerUrl = register.url();
const logoutUrl = logout.url();

const etiquetasRol: Record<UserRole, string> = {
    cliente: 'Cliente',
    coordinador: 'Coordinador',
    cajero: 'Cajero',
    administrador: 'Administrador',
    gerente: 'Gerente',
};

function esAncla(href: string): boolean {
    return href.includes('#');
}

function rutaDe(href: string): string {
    return href.split('#')[0] ?? href;
}

function estaActivo(href: string): boolean {
    if (esAncla(href)) {
        return false;
    }

    const ruta = rutaDe(href);
    const actual = page.url.split('?')[0] ?? page.url;

    if (ruta === '/' || ruta === '') {
        return actual === '/' || actual === '';
    }

    return actual === ruta || actual.startsWith(`${ruta}/`);
}

function claseEnlace(href: string): string {
    return estaActivo(href)
        ? 'text-brand-teal'
        : 'text-white/80 hover:text-brand-teal';
}

function cerrarMenu(): void {
    menuAbierto.value = false;
}
</script>

<template>
    <header class="sticky top-0 z-50 bg-brand-navy text-white">
        <div
            class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-6 px-6"
        >
            <Link :href="homeUrl" @click="cerrarMenu">
                <RentGoLogo />
            </Link>
            <nav
                class="hidden items-center gap-4 text-sm font-medium lg:flex xl:gap-6"
                aria-label="Principal"
            >
                <template v-for="item in navigation" :key="item.href">
                    <a
                        v-if="esAncla(item.href)"
                        :href="item.href"
                        :class="claseEnlace(item.href)"
                    >
                        {{ item.label }}
                    </a>
                    <Link
                        v-else
                        :href="item.href"
                        :class="claseEnlace(item.href)"
                    >
                        {{ item.label }}
                    </Link>
                </template>
            </nav>
            <div class="flex items-center gap-3">
                <div v-if="authUser" class="hidden items-center gap-3 lg:flex">
                    <p class="text-xs text-white/60">
                        {{ authUser.name }}
                        <span class="text-white/40">
                            · {{ etiquetasRol[authUser.role] }}
                        </span>
                    </p>
                    <Link
                        :href="logoutUrl"
                        method="post"
                        class="rounded-lg px-4 py-2 text-sm font-semibold hover:text-brand-teal"
                    >
                        Cerrar sesión
                    </Link>
                </div>
                <template v-else>
                    <Link
                        :href="loginUrl"
                        class="hidden rounded-lg px-4 py-2 text-sm font-semibold hover:text-brand-teal lg:inline-flex"
                    >
                        Iniciar sesión
                    </Link>
                    <Link
                        :href="registerUrl"
                        class="hidden rounded-lg bg-brand-teal px-4 py-2 text-sm font-semibold text-white hover:bg-brand-teal-dark lg:inline-flex"
                    >
                        Registrarse
                    </Link>
                </template>
                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-lg p-2 text-white hover:bg-white/10 lg:hidden"
                    :aria-expanded="menuAbierto"
                    aria-controls="menu-principal-movil"
                    :aria-label="menuAbierto ? 'Cerrar menú' : 'Abrir menú'"
                    @click="menuAbierto = !menuAbierto"
                >
                    <span class="sr-only">Menú</span>
                    <svg
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.8"
                        aria-hidden="true"
                    >
                        <path
                            v-if="menuAbierto"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6 18 18 6M6 6l12 12"
                        />
                        <path
                            v-else
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M4 7h16M4 12h16M4 17h16"
                        />
                    </svg>
                </button>
            </div>
        </div>
        <div
            v-if="menuAbierto"
            id="menu-principal-movil"
            class="border-t border-white/10 px-6 py-4 lg:hidden"
        >
            <nav
                class="flex flex-col gap-3 text-sm font-medium"
                aria-label="Móvil"
            >
                <template
                    v-for="item in navigation"
                    :key="`movil-${item.href}`"
                >
                    <a
                        v-if="esAncla(item.href)"
                        :href="item.href"
                        :class="claseEnlace(item.href)"
                        @click="cerrarMenu"
                    >
                        {{ item.label }}
                    </a>
                    <Link
                        v-else
                        :href="item.href"
                        :class="claseEnlace(item.href)"
                        @click="cerrarMenu"
                    >
                        {{ item.label }}
                    </Link>
                </template>
            </nav>
            <div class="mt-4 flex flex-col gap-2 border-t border-white/10 pt-4">
                <template v-if="authUser">
                    <p class="text-xs text-white/60">
                        {{ authUser.name }}
                        <span class="text-white/40">
                            · {{ etiquetasRol[authUser.role] }}
                        </span>
                    </p>
                    <Link
                        :href="logoutUrl"
                        method="post"
                        class="rounded-lg px-4 py-2 text-sm font-semibold hover:text-brand-teal"
                        @click="cerrarMenu"
                    >
                        Cerrar sesión
                    </Link>
                </template>
                <template v-else>
                    <Link
                        :href="loginUrl"
                        class="rounded-lg px-4 py-2 text-sm font-semibold hover:text-brand-teal"
                        @click="cerrarMenu"
                    >
                        Iniciar sesión
                    </Link>
                    <Link
                        :href="registerUrl"
                        class="rounded-lg bg-brand-teal px-4 py-2 text-center text-sm font-semibold text-white hover:bg-brand-teal-dark"
                        @click="cerrarMenu"
                    >
                        Registrarse
                    </Link>
                </template>
            </div>
        </div>
    </header>
</template>
