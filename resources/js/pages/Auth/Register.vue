<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { store } from '@/actions/App/Http/Controllers/Auth/RegistroController';
import { login } from '@/routes';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import Input from '@/components/ui/Input.vue';

const form = useForm({
    name: '',
    email: '',
    tipo_documento: 'CC',
    numero_documento: '',
    telefono: '',
    password: '',
    password_confirmation: '',
});

const loginUrl = login.url();

function submit(): void {
    form.post(store.url());
}
</script>

<template>
    <Head title="Registro" />
    <div
        class="flex min-h-screen items-center justify-center bg-slate-50 px-6 py-12 text-slate-900"
    >
        <Card class="w-full max-w-md">
            <p
                class="text-sm font-semibold tracking-wide text-brand-gold uppercase"
            >
                Nuevo cliente
            </p>
            <h1 class="mt-1 text-3xl font-bold text-brand-ink">Registro</h1>
            <p class="mt-2 text-slate-600">
                Crea una cuenta mínima para consultar reservas y pagos.
            </p>

            <form class="mt-6 flex flex-col gap-4" @submit.prevent="submit">
                <Input
                    v-model="form.name"
                    label="Nombre"
                    :error="form.errors.name"
                />
                <Input
                    v-model="form.email"
                    label="Correo"
                    type="email"
                    :error="form.errors.email"
                />
                <label
                    class="flex flex-col gap-1.5 text-sm font-medium text-slate-700"
                >
                    <span>Tipo de documento</span>
                    <select
                        v-model="form.tipo_documento"
                        class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 transition outline-none focus:border-brand-gold focus:ring-2 focus:ring-brand-gold/30"
                    >
                        <option value="CC">Cédula</option>
                        <option value="CE">Cédula de extranjería</option>
                        <option value="PAS">Pasaporte</option>
                    </select>
                    <span
                        v-if="form.errors.tipo_documento"
                        class="text-xs font-normal text-red-600"
                    >
                        {{ form.errors.tipo_documento }}
                    </span>
                </label>
                <Input
                    v-model="form.numero_documento"
                    label="Número de documento"
                    :error="form.errors.numero_documento"
                />
                <Input
                    v-model="form.telefono"
                    label="Teléfono"
                    :error="form.errors.telefono"
                />
                <Input
                    v-model="form.password"
                    label="Contraseña"
                    type="password"
                    :error="form.errors.password"
                />
                <Input
                    v-model="form.password_confirmation"
                    label="Confirmar contraseña"
                    type="password"
                    :error="form.errors.password_confirmation"
                />
                <Button type="submit" :disabled="form.processing">
                    Crear cuenta
                </Button>
            </form>

            <p class="mt-4 text-sm text-slate-600">
                ¿Ya tienes cuenta?
                <Link
                    :href="loginUrl"
                    class="font-semibold text-brand-ink hover:text-brand-gold"
                >
                    Iniciar sesión
                </Link>
            </p>
        </Card>
    </div>
</template>
