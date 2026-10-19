<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { show as cuenta } from '@/actions/App/Http/Controllers/CuentaController';
import { index as reservas } from '@/actions/App/Http/Controllers/ReservaController';
import { catalogo, home, login, logout, register } from '@/routes';
import RentGoLogo from '@/components/storefront/RentGoLogo.vue';

const page = usePage();
const authUser = computed(() => page.props.auth?.user ?? null);
const esCliente = computed(() => authUser.value?.role === 'cliente');
const homeUrl = home.url();
const catalogoUrl = catalogo.url();
const reservasUrl = reservas.url();
const cuentaUrl = cuenta.url();
const loginUrl = login.url();
const registerUrl = register.url();
const logoutUrl = logout.url();
const isCatalogo = computed(() => page.url.startsWith('/catalogo'));
</script>

<template>
    <header class="sticky top-0 z-50 bg-brand-navy text-white">
        <div
            class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-6 px-6"
        >
            <Link :href="homeUrl">
                <RentGoLogo />
            </Link>
            <nav class="hidden items-center gap-6 text-sm font-medium md:flex">
                <Link :href="homeUrl" class="hover:text-brand-teal"
                    >Inicio</Link
                >
                <Link
                    :href="catalogoUrl"
                    class="hover:text-brand-teal"
                    :class="{ 'text-brand-teal': isCatalogo }"
                >
                    Catálogo
                </Link>
                <a
                    :href="`${homeUrl}#como-funciona`"
                    class="hover:text-brand-teal"
                    >Cómo funciona</a
                >
                <a :href="`${homeUrl}#contacto`" class="hover:text-brand-teal"
                    >Contacto</a
                >
                <Link
                    v-if="esCliente"
                    :href="reservasUrl"
                    class="hover:text-brand-teal"
                >
                    Mis reservas
                </Link>
                <Link
                    v-if="esCliente"
                    :href="cuentaUrl"
                    class="hover:text-brand-teal"
                >
                    Mi cuenta
                </Link>
            </nav>
            <div class="flex items-center gap-3">
                <Link
                    v-if="authUser"
                    :href="logoutUrl"
                    method="post"
                    class="rounded-lg px-4 py-2 text-sm font-semibold hover:text-brand-teal"
                >
                    Cerrar sesión
                </Link>
                <template v-else>
                    <Link
                        :href="loginUrl"
                        class="rounded-lg px-4 py-2 text-sm font-semibold hover:text-brand-teal"
                    >
                        Iniciar sesión
                    </Link>
                    <Link
                        :href="registerUrl"
                        class="rounded-lg bg-brand-teal px-4 py-2 text-sm font-semibold text-white hover:bg-brand-teal-dark"
                    >
                        Registrarse
                    </Link>
                </template>
            </div>
        </div>
    </header>
</template>
