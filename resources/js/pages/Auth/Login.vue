<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { store } from '@/actions/App/Http/Controllers/Auth/SesionController';
import { register } from '@/routes';
import RentGoLogo from '@/components/storefront/RentGoLogo.vue';
import { loginHeroImage } from '@/components/storefront/mocks';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const passwordVisible = ref(false);
const registerUrl = register.url();

const benefits = [
    'Reservas en minutos',
    'Flota premium disponible',
    'Soporte 24/7',
] as const;

function submit(): void {
    form.post(store.url());
}
</script>

<template>
    <Head title="Iniciar sesión" />
    <div
        class="flex min-h-screen flex-col bg-brand-navy text-slate-800 antialiased"
    >
        <div
            class="grid min-h-0 w-full flex-1 grid-cols-1 bg-white text-slate-900 lg:grid-cols-2"
        >
            <aside
                class="relative isolate flex min-h-[28rem] flex-col justify-between overflow-hidden bg-brand-navy px-8 py-8 text-white lg:min-h-screen lg:px-12 lg:py-10"
            >
                <img
                    :src="loginHeroImage"
                    alt="Auto deportivo de la flota RENT-GO"
                    class="absolute inset-0 h-full w-full object-cover"
                />
                <div class="absolute inset-0 bg-brand-navy/75" />

                <div class="relative">
                    <Link href="/" class="inline-flex text-white">
                        <RentGoLogo />
                    </Link>
                </div>

                <div class="relative max-w-md">
                    <h1
                        class="text-4xl font-semibold tracking-tight lg:text-5xl"
                    >
                        Bienvenido de vuelta
                    </h1>
                    <p class="mt-4 text-base leading-relaxed text-white/80">
                        Inicia sesión para gestionar tus reservas, consultar
                        contratos y disfrutar de todos los beneficios de
                        RENT-GO.
                    </p>
                    <ul class="mt-8 flex flex-col gap-3 text-sm font-medium">
                        <li
                            v-for="benefit in benefits"
                            :key="benefit"
                            class="flex items-center gap-3"
                        >
                            <span
                                class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-teal"
                                aria-hidden="true"
                            >
                                <svg
                                    class="h-3.5 w-3.5 text-white"
                                    viewBox="0 0 16 16"
                                    fill="none"
                                >
                                    <path
                                        d="M3.5 8.2 6.4 11 12.5 4.8"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </span>
                            {{ benefit }}
                        </li>
                    </ul>
                </div>

                <p class="relative text-sm text-white/60">© 2026 RENT-GO</p>
            </aside>

            <section
                class="flex items-center justify-center bg-white px-6 py-12 lg:px-12"
            >
                <div class="w-full max-w-md">
                    <h2
                        class="text-3xl font-semibold tracking-tight text-brand-navy"
                    >
                        Iniciar sesión
                    </h2>
                    <p class="mt-2 text-slate-600">
                        ¿Sin cuenta?
                        <Link
                            :href="registerUrl"
                            class="font-semibold text-brand-teal hover:text-brand-teal-dark"
                        >
                            Registrate gratis
                        </Link>
                    </p>

                    <form
                        class="mt-8 flex flex-col gap-5"
                        @submit.prevent="submit"
                    >
                        <label
                            class="flex flex-col gap-1.5 text-xs font-semibold tracking-wide text-slate-500 uppercase"
                        >
                            Correo electrónico
                            <input
                                v-model="form.email"
                                type="email"
                                name="email"
                                autocomplete="email"
                                placeholder="tu@email.com"
                                class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-base font-normal tracking-normal text-slate-900 normal-case transition outline-none placeholder:text-slate-400 focus:border-brand-teal focus:ring-2 focus:ring-brand-teal/30"
                            />
                            <span
                                v-if="form.errors.email"
                                class="text-xs font-normal tracking-normal text-red-600 normal-case"
                            >
                                {{ form.errors.email }}
                            </span>
                        </label>

                        <label
                            class="flex flex-col gap-1.5 text-xs font-semibold tracking-wide text-slate-500 uppercase"
                        >
                            Contraseña
                            <span class="relative">
                                <input
                                    v-model="form.password"
                                    :type="
                                        passwordVisible ? 'text' : 'password'
                                    "
                                    name="password"
                                    autocomplete="current-password"
                                    placeholder="••••••••"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 pr-12 text-base font-normal tracking-normal text-slate-900 normal-case transition outline-none placeholder:text-slate-400 focus:border-brand-teal focus:ring-2 focus:ring-brand-teal/30"
                                />
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600"
                                    :aria-label="
                                        passwordVisible
                                            ? 'Ocultar contraseña'
                                            : 'Mostrar contraseña'
                                    "
                                    @click="passwordVisible = !passwordVisible"
                                >
                                    <svg
                                        v-if="!passwordVisible"
                                        class="h-5 w-5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                    >
                                        <path
                                            d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                    <svg
                                        v-else
                                        class="h-5 w-5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                    >
                                        <path
                                            d="M3 3l18 18M10.6 10.6A3 3 0 0 0 12 15a3 3 0 0 0 2.4-4.4M9.9 5.1A10.8 10.8 0 0 1 12 5c6.5 0 10 7 10 7a17.5 17.5 0 0 1-3.2 4.4M6.1 6.1C3.8 7.8 2 12 2 12s3.5 7 10 7c1.5 0 2.9-.3 4.1-.8"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                    </svg>
                                </button>
                            </span>
                            <span
                                v-if="form.errors.password"
                                class="text-xs font-normal tracking-normal text-red-600 normal-case"
                            >
                                {{ form.errors.password }}
                            </span>
                        </label>

                        <div class="flex items-center justify-between gap-4">
                            <label
                                class="flex items-center gap-2 text-sm font-normal text-slate-600 normal-case"
                            >
                                <input
                                    v-model="form.remember"
                                    type="checkbox"
                                    name="remember"
                                    class="h-4 w-4 rounded border-slate-300 text-brand-teal focus:ring-brand-teal"
                                />
                                Recordarme
                            </label>
                            <a
                                href="#olvido-contrasena"
                                class="text-sm font-semibold text-brand-teal hover:text-brand-teal-dark"
                            >
                                ¿Olvidaste tu contraseña?
                            </a>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full rounded-xl bg-brand-teal px-4 py-3 text-sm font-semibold text-white transition hover:bg-brand-teal-dark focus:ring-2 focus:ring-brand-teal/40 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Iniciar sesión
                        </button>
                    </form>

                    <div
                        class="mt-8 flex items-center gap-3 text-sm text-slate-400"
                    >
                        <span class="h-px flex-1 bg-slate-200" />
                        o continúa con
                        <span class="h-px flex-1 bg-slate-200" />
                    </div>

                    <!-- TODO: VINCULAR RUTAS ROUTE('socialite.redirect', 'google') EN SCRUM-56 -->
                    <div class="mt-6 grid grid-cols-2 gap-3">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-800 hover:bg-slate-50"
                        >
                            <svg
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path
                                    fill="#4285F4"
                                    d="M23.54 12.27c0-.85-.08-1.67-.22-2.46H12v4.66h6.46a5.52 5.52 0 0 1-2.4 3.62v3h3.88c2.27-2.09 3.6-5.17 3.6-8.82Z"
                                />
                                <path
                                    fill="#34A853"
                                    d="M12 24c3.24 0 5.96-1.07 7.95-2.91l-3.88-3c-1.08.72-2.47 1.15-4.07 1.15-3.13 0-5.78-2.11-6.73-4.96H1.27v3.09A12 12 0 0 0 12 24Z"
                                />
                                <path
                                    fill="#FBBC05"
                                    d="M5.27 14.28A7.2 7.2 0 0 1 4.89 12c0-.79.14-1.56.38-2.28V6.63H1.27A12 12 0 0 0 0 12c0 1.94.46 3.78 1.27 5.37l4-3.09Z"
                                />
                                <path
                                    fill="#EA4335"
                                    d="M12 4.75c1.76 0 3.34.61 4.59 1.8l3.44-3.44C17.95 1.19 15.24 0 12 0 7.31 0 3.26 2.69 1.27 6.63l4 3.09C6.22 6.86 8.87 4.75 12 4.75Z"
                                />
                            </svg>
                            Google
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-800 hover:bg-slate-50"
                        >
                            <svg
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path
                                    fill="#1877F2"
                                    d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.09 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.01 1.79-4.67 4.53-4.67 1.31 0 2.69.23 2.69.23v2.95h-1.51c-1.49 0-1.96.93-1.96 1.87v2.25h3.33l-.53 3.49h-2.8V24C19.61 23.09 24 18.1 24 12.07Z"
                                />
                            </svg>
                            Facebook
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>
