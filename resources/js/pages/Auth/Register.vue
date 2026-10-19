<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { store } from '@/actions/App/Http/Controllers/Auth/RegistroController';
import { home, login } from '@/routes';
import RentGoLogo from '@/components/storefront/RentGoLogo.vue';
import { registerHeroImage } from '@/components/storefront/mocks';

defineProps<{
    categoriasLicencia: string[];
}>();

const form = useForm({
    name: '',
    email: '',
    tipo_documento: 'CC',
    numero_documento: '',
    telefono: '',
    direccion: '',
    codigo_postal: '',
    licencia_numero: '',
    licencia_categoria: '',
    licencia_vencimiento: '',
    password: '',
    password_confirmation: '',
});

const passwordVisible = ref(false);
const confirmationVisible = ref(false);
const homeUrl = home.url();
const loginUrl = login.url();

const steps = [
    { number: 1, label: 'Datos personales', active: true },
    { number: 2, label: 'Datos de conducción', active: true },
    { number: 3, label: 'Seguridad', active: true },
] as const;

const controlClass =
    'w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-base font-normal tracking-normal text-slate-900 normal-case transition outline-none placeholder:text-slate-400 focus:border-brand-teal focus:ring-2 focus:ring-brand-teal/30';

function submit(): void {
    form.transform((data) => ({
        name: data.name,
        email: data.email,
        tipo_documento: data.tipo_documento,
        numero_documento: data.numero_documento,
        telefono: data.telefono,
        licencia_numero: data.licencia_numero,
        licencia_categoria: data.licencia_categoria,
        licencia_vencimiento: data.licencia_vencimiento,
        password: data.password,
        password_confirmation: data.password_confirmation,
    })).post(store.url());
}
</script>

<template>
    <Head title="Registro" />
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
                    :src="registerHeroImage"
                    alt="Carretera al anochecer"
                    class="absolute inset-0 h-full w-full object-cover"
                />
                <div class="absolute inset-0 bg-brand-navy/80" />

                <div class="relative">
                    <Link :href="homeUrl" class="inline-flex text-white">
                        <RentGoLogo />
                    </Link>
                </div>

                <div class="relative max-w-md">
                    <h1
                        class="text-4xl font-semibold tracking-tight lg:text-5xl"
                    >
                        Crea tu cuenta
                    </h1>
                    <p class="mt-4 text-base leading-relaxed text-white/80">
                        Únete a miles de viajeros que confían en RENT-GO para
                        sus desplazamientos.
                    </p>
                    <ol class="mt-10 flex flex-col gap-6">
                        <li
                            v-for="step in steps"
                            :key="step.number"
                            class="flex items-center gap-4"
                        >
                            <span
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-semibold"
                                :class="
                                    step.active
                                        ? 'bg-brand-teal text-white'
                                        : 'bg-white/10 text-white/40'
                                "
                            >
                                {{ step.number }}
                            </span>
                            <span
                                class="text-sm font-medium"
                                :class="
                                    step.active ? 'text-white' : 'text-white/60'
                                "
                            >
                                {{ step.label }}
                            </span>
                        </li>
                    </ol>
                </div>

                <p class="relative text-sm text-white/60">© 2026 RENT-GO</p>
            </aside>

            <section
                class="flex items-center justify-center bg-white px-6 py-12 lg:px-12"
            >
                <div class="w-full max-w-[560px]">
                    <h2
                        class="text-3xl font-semibold tracking-tight text-brand-navy"
                    >
                        Datos personales
                    </h2>
                    <p class="mt-2 text-slate-600">
                        Datos personales, conducción y seguridad · ¿Ya tienes
                        cuenta?
                        <Link
                            :href="loginUrl"
                            class="font-semibold text-brand-teal hover:text-brand-teal-dark"
                        >
                            Inicia sesión
                        </Link>
                    </p>

                    <form
                        class="mt-8 grid grid-cols-1 gap-3 sm:grid-cols-2"
                        @submit.prevent="submit"
                    >
                        <label
                            class="flex flex-col gap-1.5 text-xs font-semibold tracking-wide text-slate-500 uppercase"
                        >
                            Tipo de documento
                            <select
                                v-model="form.tipo_documento"
                                name="tipo_documento"
                                :class="controlClass"
                            >
                                <option value="CC">Cédula</option>
                                <option value="CE">
                                    Cédula de extranjería
                                </option>
                                <option value="PAS">Pasaporte</option>
                            </select>
                            <span
                                v-if="form.errors.tipo_documento"
                                class="text-xs font-normal tracking-normal text-red-600 normal-case"
                            >
                                {{ form.errors.tipo_documento }}
                            </span>
                        </label>

                        <label
                            class="flex flex-col gap-1.5 text-xs font-semibold tracking-wide text-slate-500 uppercase"
                        >
                            Número
                            <input
                                v-model="form.numero_documento"
                                type="text"
                                name="numero_documento"
                                autocomplete="off"
                                placeholder="12345678"
                                :class="controlClass"
                            />
                            <span
                                v-if="form.errors.numero_documento"
                                class="text-xs font-normal tracking-normal text-red-600 normal-case"
                            >
                                {{ form.errors.numero_documento }}
                            </span>
                        </label>

                        <label
                            class="flex flex-col gap-1.5 text-xs font-semibold tracking-wide text-slate-500 uppercase sm:col-span-2"
                        >
                            Nombre completo
                            <input
                                v-model="form.name"
                                type="text"
                                name="name"
                                autocomplete="name"
                                placeholder="Ana García López"
                                :class="controlClass"
                            />
                            <span
                                v-if="form.errors.name"
                                class="text-xs font-normal tracking-normal text-red-600 normal-case"
                            >
                                {{ form.errors.name }}
                            </span>
                        </label>

                        <label
                            class="flex flex-col gap-1.5 text-xs font-semibold tracking-wide text-slate-500 uppercase sm:col-span-2"
                        >
                            Dirección
                            <input
                                v-model="form.direccion"
                                type="text"
                                name="direccion"
                                autocomplete="street-address"
                                placeholder="Calle 45 #23-12, Apto 301"
                                :class="controlClass"
                            />
                        </label>

                        <label
                            class="flex flex-col gap-1.5 text-xs font-semibold tracking-wide text-slate-500 uppercase"
                        >
                            Código postal
                            <input
                                v-model="form.codigo_postal"
                                type="text"
                                name="codigo_postal"
                                autocomplete="postal-code"
                                inputmode="numeric"
                                placeholder="110111"
                                :class="controlClass"
                            />
                        </label>

                        <label
                            class="flex flex-col gap-1.5 text-xs font-semibold tracking-wide text-slate-500 uppercase"
                        >
                            Teléfono principal
                            <input
                                v-model="form.telefono"
                                type="tel"
                                name="telefono"
                                autocomplete="tel"
                                placeholder="+57 300 000 0000"
                                :class="controlClass"
                            />
                            <span
                                v-if="form.errors.telefono"
                                class="text-xs font-normal tracking-normal text-red-600 normal-case"
                            >
                                {{ form.errors.telefono }}
                            </span>
                        </label>

                        <label
                            class="flex flex-col gap-1.5 text-xs font-semibold tracking-wide text-slate-500 uppercase sm:col-span-2"
                        >
                            Correo electrónico
                            <input
                                v-model="form.email"
                                type="email"
                                name="email"
                                autocomplete="email"
                                placeholder="ana@email.com"
                                :class="controlClass"
                            />
                            <span
                                v-if="form.errors.email"
                                class="text-xs font-normal tracking-normal text-red-600 normal-case"
                            >
                                {{ form.errors.email }}
                            </span>
                        </label>

                        <h3
                            class="mt-4 text-lg font-semibold text-brand-navy sm:col-span-2"
                        >
                            Datos de conducción
                        </h3>

                        <label
                            class="flex flex-col gap-1.5 text-xs font-semibold tracking-wide text-slate-500 uppercase"
                        >
                            Número de licencia
                            <input
                                v-model="form.licencia_numero"
                                type="text"
                                name="licencia_numero"
                                autocomplete="off"
                                placeholder="LIC-12345678"
                                :class="controlClass"
                            />
                            <span
                                v-if="form.errors.licencia_numero"
                                class="text-xs font-normal tracking-normal text-red-600 normal-case"
                            >
                                {{ form.errors.licencia_numero }}
                            </span>
                        </label>

                        <label
                            class="flex flex-col gap-1.5 text-xs font-semibold tracking-wide text-slate-500 uppercase"
                        >
                            Categoría
                            <select
                                v-model="form.licencia_categoria"
                                name="licencia_categoria"
                                :class="controlClass"
                            >
                                <option disabled value="">Selecciona</option>
                                <option
                                    v-for="categoria in categoriasLicencia"
                                    :key="categoria"
                                    :value="categoria"
                                >
                                    {{ categoria }}
                                </option>
                            </select>
                            <span
                                v-if="form.errors.licencia_categoria"
                                class="text-xs font-normal tracking-normal text-red-600 normal-case"
                            >
                                {{ form.errors.licencia_categoria }}
                            </span>
                        </label>

                        <label
                            class="flex flex-col gap-1.5 text-xs font-semibold tracking-wide text-slate-500 uppercase sm:col-span-2"
                        >
                            Fecha de vencimiento
                            <input
                                v-model="form.licencia_vencimiento"
                                type="date"
                                name="licencia_vencimiento"
                                :class="controlClass"
                            />
                            <span
                                v-if="form.errors.licencia_vencimiento"
                                class="text-xs font-normal tracking-normal text-red-600 normal-case"
                            >
                                {{ form.errors.licencia_vencimiento }}
                            </span>
                        </label>

                        <h3
                            class="mt-4 text-lg font-semibold text-brand-navy sm:col-span-2"
                        >
                            Seguridad
                        </h3>

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
                                    autocomplete="new-password"
                                    placeholder="••••••••"
                                    :class="`${controlClass} pr-12`"
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

                        <label
                            class="flex flex-col gap-1.5 text-xs font-semibold tracking-wide text-slate-500 uppercase"
                        >
                            Confirmar contraseña
                            <span class="relative">
                                <input
                                    v-model="form.password_confirmation"
                                    :type="
                                        confirmationVisible
                                            ? 'text'
                                            : 'password'
                                    "
                                    name="password_confirmation"
                                    autocomplete="new-password"
                                    placeholder="••••••••"
                                    :class="`${controlClass} pr-12`"
                                />
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600"
                                    :aria-label="
                                        confirmationVisible
                                            ? 'Ocultar confirmación de contraseña'
                                            : 'Mostrar confirmación de contraseña'
                                    "
                                    @click="
                                        confirmationVisible =
                                            !confirmationVisible
                                    "
                                >
                                    <svg
                                        v-if="!confirmationVisible"
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
                                v-if="form.errors.password_confirmation"
                                class="text-xs font-normal tracking-normal text-red-600 normal-case"
                            >
                                {{ form.errors.password_confirmation }}
                            </span>
                        </label>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="mt-3 w-full rounded-full bg-brand-teal px-4 py-3.5 text-sm font-semibold text-white transition hover:bg-brand-teal-dark focus:ring-2 focus:ring-brand-teal/40 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50 sm:col-span-2"
                        >
                            Crear cuenta
                        </button>
                    </form>
                </div>
            </section>
        </div>
    </div>
</template>
