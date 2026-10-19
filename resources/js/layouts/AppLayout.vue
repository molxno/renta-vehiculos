<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import RentGoLogo from '@/components/storefront/RentGoLogo.vue';
import { login, logout } from '@/routes';

const page = usePage();
const authUser = computed(() => page.props.auth?.user ?? null);
const navigation = computed(() => page.props.navigation ?? []);
const landingHref = computed(() => page.props.landingHref ?? '/');
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
                <Link :href="landingHref" class="inline-flex items-center">
                    <RentGoLogo />
                </Link>
                <nav class="hidden items-center gap-5 md:flex">
                    <Link
                        v-for="item in navigation"
                        :key="item.label"
                        :href="item.href"
                        class="text-sm font-medium text-white/80 hover:text-brand-teal"
                    >
                        {{ item.label }}
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
