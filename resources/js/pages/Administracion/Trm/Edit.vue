<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { update as actualizarTrm } from '@/actions/App/Http/Controllers/TrmController';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import Input from '@/components/ui/Input.vue';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{
    currentTrm: {
        fecha: string;
        valor: string;
    } | null;
    today: string;
}>();

const form = useForm({
    fecha: props.today,
    valor: props.currentTrm?.valor ?? '',
});

function guardar(): void {
    form.put(actualizarTrm.url(), { preserveScroll: true });
}
</script>

<template>
    <Head title="Administrar TRM" />
    <AppLayout role="administrador">
        <div class="flex flex-col gap-6">
            <div>
                <p
                    class="text-sm font-semibold tracking-wide text-brand-gold uppercase"
                >
                    Administración
                </p>
                <h1 class="mt-1 text-3xl font-bold text-brand-ink">
                    Tasa representativa del mercado
                </h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-600">
                    Registra la TRM COP/USD que se usará para cotizar reservas
                    en dólares. El sistema no consulta proveedores externos.
                </p>
            </div>

            <Card>
                <div class="flex flex-col gap-5">
                    <div
                        v-if="currentTrm"
                        class="rounded-lg border border-slate-200 bg-slate-50 p-4"
                    >
                        <p class="text-sm text-slate-500">
                            Última TRM vigente ({{ currentTrm.fecha }})
                        </p>
                        <p class="mt-1 text-xl font-semibold text-brand-ink">
                            {{ currentTrm.valor }} COP por USD
                        </p>
                    </div>
                    <p v-else class="text-sm text-slate-600">
                        Aún no hay una TRM registrada. Agrega una para habilitar
                        las cotizaciones en USD.
                    </p>

                    <form
                        class="grid gap-5 md:max-w-xl"
                        @submit.prevent="guardar"
                    >
                        <Input
                            v-model="form.fecha"
                            label="Fecha de vigencia"
                            type="date"
                            :max="today"
                            :error="form.errors.fecha"
                        />
                        <Input
                            v-model="form.valor"
                            label="TRM (COP por USD)"
                            type="number"
                            min="0.0001"
                            max="99999999.9999"
                            step="0.0001"
                            placeholder="4200.0000"
                            :error="form.errors.valor"
                        />
                        <div>
                            <Button type="submit" :disabled="form.processing">
                                {{
                                    form.processing
                                        ? 'Guardando…'
                                        : 'Guardar TRM'
                                }}
                            </Button>
                        </div>
                    </form>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
