<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Card from '@/components/ui/Card.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatFecha } from '@/lib/utils';

const props = defineProps<{
    perfil: {
        name: string;
        email: string;
        tipo_documento: string;
        numero_documento: string;
        telefono: string;
    };
    licencia: {
        numero: string;
        categoria: string;
        fecha_vencimiento: string;
        vigente: boolean;
    } | null;
    categoriasLicencia: string[];
    updateUrl: string;
}>();

const form = useForm({
    licencia_numero: props.licencia?.numero ?? '',
    licencia_categoria: props.licencia?.categoria ?? '',
    licencia_vencimiento: props.licencia?.fecha_vencimiento ?? '',
});

function enviar(): void {
    form.put(props.updateUrl);
}
</script>

<template>
    <Head title="Mi cuenta" />
    <AppLayout>
        <div class="flex flex-col gap-8">
            <div>
                <p
                    class="text-sm font-semibold tracking-wide text-brand-gold uppercase"
                >
                    Cuenta
                </p>
                <h1 class="mt-1 text-3xl font-bold text-brand-ink">
                    Mi cuenta
                </h1>
                <p class="mt-2 max-w-2xl text-slate-600">
                    Revisa tus datos y registra o actualiza tu licencia de
                    conducción para poder recibir el vehículo.
                </p>
            </div>

            <Card>
                <h2 class="text-lg font-semibold text-brand-navy">
                    Datos del perfil
                </h2>
                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-slate-500">Nombre</dt>
                        <dd class="font-medium text-brand-navy">
                            {{ perfil.name }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Correo</dt>
                        <dd class="font-medium text-brand-navy">
                            {{ perfil.email }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Documento</dt>
                        <dd class="font-medium text-brand-navy">
                            {{ perfil.tipo_documento }}
                            {{ perfil.numero_documento }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Teléfono</dt>
                        <dd class="font-medium text-brand-navy">
                            {{ perfil.telefono }}
                        </dd>
                    </div>
                </dl>
            </Card>

            <Card>
                <h2 class="text-lg font-semibold text-brand-navy">
                    Licencia de conducción
                </h2>
                <p v-if="licencia" class="mt-2 text-sm text-slate-600">
                    Número {{ licencia.numero }} · Categoría
                    {{ licencia.categoria }} · Vence
                    {{ formatFecha(licencia.fecha_vencimiento) }}
                    ·
                    {{ licencia.vigente ? 'Vigente' : 'Vencida' }}
                </p>
                <p v-else class="mt-2 text-sm text-slate-600">
                    Aún no tienes una licencia registrada.
                </p>

                <form
                    class="mt-5 grid gap-4 sm:grid-cols-2"
                    @submit.prevent="enviar"
                >
                    <label
                        class="flex flex-col gap-1.5 text-sm font-medium text-slate-700"
                    >
                        Número
                        <input
                            v-model="form.licencia_numero"
                            type="text"
                            name="licencia_numero"
                            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 outline-none focus:border-brand-teal focus:ring-2 focus:ring-brand-teal/30"
                        />
                        <span
                            v-if="form.errors.licencia_numero"
                            class="text-xs font-normal text-red-600"
                        >
                            {{ form.errors.licencia_numero }}
                        </span>
                    </label>

                    <label
                        class="flex flex-col gap-1.5 text-sm font-medium text-slate-700"
                    >
                        Categoría
                        <select
                            v-model="form.licencia_categoria"
                            name="licencia_categoria"
                            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 outline-none focus:border-brand-teal focus:ring-2 focus:ring-brand-teal/30"
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
                            class="text-xs font-normal text-red-600"
                        >
                            {{ form.errors.licencia_categoria }}
                        </span>
                    </label>

                    <label
                        class="flex flex-col gap-1.5 text-sm font-medium text-slate-700 sm:col-span-2"
                    >
                        Fecha de vencimiento
                        <input
                            v-model="form.licencia_vencimiento"
                            type="date"
                            name="licencia_vencimiento"
                            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 outline-none focus:border-brand-teal focus:ring-2 focus:ring-brand-teal/30"
                        />
                        <span
                            v-if="form.errors.licencia_vencimiento"
                            class="text-xs font-normal text-red-600"
                        >
                            {{ form.errors.licencia_vencimiento }}
                        </span>
                    </label>

                    <div class="sm:col-span-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center justify-center rounded-lg bg-brand-teal px-4 py-2 text-sm font-semibold text-white hover:bg-brand-teal-dark disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Guardar licencia
                        </button>
                    </div>
                </form>
            </Card>
        </div>
    </AppLayout>
</template>
