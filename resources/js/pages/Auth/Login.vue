<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { store } from '@/actions/App/Http/Controllers/Auth/SesionController';
import { create as register } from '@/actions/App/Http/Controllers/Auth/RegistroController';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import Input from '@/components/ui/Input.vue';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit(): void {
    form.post(store.url());
}
</script>

<template>
    <Head title="Iniciar sesión" />
    <div
        class="flex min-h-screen items-center justify-center bg-slate-50 px-6 py-12 text-slate-900"
    >
        <Card class="w-full max-w-md">
            <p
                class="text-sm font-semibold tracking-wide text-brand-gold uppercase"
            >
                Acceso
            </p>
            <h1 class="mt-1 text-3xl font-bold text-brand-ink">
                Iniciar sesión
            </h1>
            <p class="mt-2 text-slate-600">
                Entra con el usuario de tu rol para continuar.
            </p>

            <form class="mt-6 flex flex-col gap-4" @submit.prevent="submit">
                <Input
                    v-model="form.email"
                    label="Correo"
                    type="email"
                    placeholder="cliente@renta.test"
                    :error="form.errors.email"
                />
                <Input
                    v-model="form.password"
                    label="Contraseña"
                    type="password"
                    :error="form.errors.password"
                />
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input
                        v-model="form.remember"
                        type="checkbox"
                        class="rounded border-slate-300 text-brand-ink focus:ring-brand-gold"
                    />
                    Recordarme
                </label>
                <Button type="submit" :disabled="form.processing">
                    Entrar
                </Button>
            </form>

            <p class="mt-4 text-sm text-slate-600">
                ¿No tienes cuenta?
                <Link
                    :href="register.url()"
                    class="font-semibold text-brand-ink hover:text-brand-gold"
                >
                    Crear registro
                </Link>
            </p>
        </Card>
    </div>
</template>
