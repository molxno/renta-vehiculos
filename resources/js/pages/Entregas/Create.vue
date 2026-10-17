<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';

const props = defineProps<{
    reservation: {
        id: number;
        estado: string;
        inicio: string;
        fin: string;
        total_centavos: number;
        moneda: string;
    };
    checklist: Record<string, unknown>;
    condicionesContrato: Record<string, unknown>;
    licencia: {
        numero: string;
        fecha_vencimiento: string;
        categoria: string;
        vigente: boolean;
    } | null;
    bloqueo: string | null;
    anticipoCents: number;
    currency: string;
    storeUrl: string;
    nivelesCombustible: Array<{ value: string; label: string }>;
}>();

const form = useForm({
    entregado_at: '',
    checklist: {
        kilometraje: String(props.checklist.kilometraje ?? ''),
        combustible: String(props.checklist.combustible ?? ''),
        documentos: Boolean(props.checklist.documentos ?? true),
        llantas: Boolean(props.checklist.llantas ?? true),
    },
    observaciones: '',
    aceptacion: false,
    foto: null as File | null,
});

function pesos(centavos: number): string {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: props.currency,
        maximumFractionDigits: 0,
    }).format(centavos / 100);
}

function enviar(): void {
    form.post(props.storeUrl, { forceFormData: true });
}
</script>

<template>
    <Head title="Registrar entrega" />
    <AppLayout role="coordinador">
        <div class="flex flex-col gap-8">
            <div>
                <p
                    class="text-sm font-semibold tracking-wide text-brand-gold uppercase"
                >
                    Entrega
                </p>
                <h1 class="mt-1 text-3xl font-bold text-brand-ink">
                    Registrar entrega #{{ reservation.id }}
                </h1>
                <p class="mt-2 text-sm text-slate-500">
                    Estado {{ reservation.estado }} · Renta
                    {{ pesos(reservation.total_centavos) }}
                </p>
                <p class="mt-2 max-w-2xl text-slate-600">
                    Valida la licencia, registra el acta, acepta el contrato y
                    cobra el anticipo antes de entregar el vehículo.
                </p>
            </div>

            <div class="grid gap-5 lg:grid-cols-2">
                <Card>
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-semibold text-brand-ink">
                            Licencia de conducción
                        </h2>
                        <Badge
                            :tone="licencia?.vigente ? 'success' : 'warning'"
                        >
                            {{ licencia?.vigente ? 'Vigente' : 'Bloqueada' }}
                        </Badge>
                    </div>
                    <dl v-if="licencia" class="mt-4 grid gap-3 text-sm">
                        <div>
                            <dt class="text-slate-500">Número</dt>
                            <dd class="font-medium text-brand-ink">
                                {{ licencia.numero }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Categoría</dt>
                            <dd class="font-medium text-brand-ink">
                                {{ licencia.categoria }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Vencimiento</dt>
                            <dd class="font-medium text-brand-ink">
                                {{ licencia.fecha_vencimiento }}
                            </dd>
                        </div>
                    </dl>
                    <p v-else class="mt-4 text-sm text-slate-500">
                        Este cliente no tiene una licencia registrada.
                    </p>
                    <p
                        v-if="bloqueo"
                        class="mt-4 text-sm font-medium text-red-600"
                    >
                        {{ bloqueo }}
                    </p>
                </Card>

                <Card>
                    <h2 class="text-lg font-semibold text-brand-ink">
                        Anticipo
                    </h2>
                    <p class="mt-4 text-3xl font-bold text-brand-ink">
                        {{ pesos(anticipoCents) }}
                    </p>
                    <p class="mt-2 text-sm text-slate-500">
                        Se registra como pago en efectivo al confirmar la
                        entrega. Reserva del {{ reservation.inicio }} al
                        {{ reservation.fin }}.
                    </p>
                    <p class="mt-2 text-sm text-slate-500">
                        Kilometraje incluido:
                        {{ condicionesContrato.kilometraje_incluido ?? 200 }}
                        km · Combustible:
                        {{ condicionesContrato.combustible ?? 'lleno' }}
                    </p>
                </Card>
            </div>

            <Card>
                <form class="flex flex-col gap-5" @submit.prevent="enviar">
                    <h2 class="text-lg font-semibold text-brand-ink">
                        Acta de entrega
                    </h2>

                    <label
                        class="flex flex-col gap-1.5 text-sm font-medium text-slate-700"
                    >
                        <span>Fecha y hora de entrega</span>
                        <input
                            v-model="form.entregado_at"
                            type="datetime-local"
                            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 transition outline-none focus:border-brand-gold focus:ring-2 focus:ring-brand-gold/30"
                        />
                        <span
                            v-if="form.errors.entregado_at"
                            class="text-xs font-normal text-red-600"
                            >{{ form.errors.entregado_at }}</span
                        >
                    </label>

                    <div class="grid gap-5 md:grid-cols-2">
                        <label
                            class="flex flex-col gap-1.5 text-sm font-medium text-slate-700"
                        >
                            <span>Kilometraje de salida</span>
                            <input
                                v-model="form.checklist.kilometraje"
                                type="number"
                                min="0"
                                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 transition outline-none focus:border-brand-gold focus:ring-2 focus:ring-brand-gold/30"
                            />
                            <span
                                v-if="form.errors['checklist.kilometraje']"
                                class="text-xs font-normal text-red-600"
                                >{{
                                    form.errors['checklist.kilometraje']
                                }}</span
                            >
                        </label>

                        <label
                            class="flex flex-col gap-1.5 text-sm font-medium text-slate-700"
                        >
                            <span>Nivel de combustible</span>
                            <select
                                v-model="form.checklist.combustible"
                                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 transition outline-none focus:border-brand-gold focus:ring-2 focus:ring-brand-gold/30"
                            >
                                <option value="">Seleccione</option>
                                <option
                                    v-for="nivel in nivelesCombustible"
                                    :key="nivel.value"
                                    :value="nivel.value"
                                >
                                    {{ nivel.label }}
                                </option>
                            </select>
                            <span
                                v-if="form.errors['checklist.combustible']"
                                class="text-xs font-normal text-red-600"
                                >{{
                                    form.errors['checklist.combustible']
                                }}</span
                            >
                        </label>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        <label
                            class="flex items-center gap-2 text-sm font-medium text-slate-700"
                        >
                            <input
                                v-model="form.checklist.documentos"
                                type="checkbox"
                                class="rounded border-slate-300 text-brand-ink focus:ring-brand-gold"
                            />
                            <span>Documentos del vehículo</span>
                        </label>
                        <label
                            class="flex items-center gap-2 text-sm font-medium text-slate-700"
                        >
                            <input
                                v-model="form.checklist.llantas"
                                type="checkbox"
                                class="rounded border-slate-300 text-brand-ink focus:ring-brand-gold"
                            />
                            <span>Llantas en buen estado</span>
                        </label>
                    </div>

                    <label
                        class="flex flex-col gap-1.5 text-sm font-medium text-slate-700"
                    >
                        <span>Observaciones</span>
                        <textarea
                            v-model="form.observaciones"
                            rows="3"
                            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 transition outline-none focus:border-brand-gold focus:ring-2 focus:ring-brand-gold/30"
                        />
                    </label>

                    <label
                        class="flex flex-col gap-1.5 text-sm font-medium text-slate-700"
                    >
                        <span>Foto opcional</span>
                        <input
                            type="file"
                            accept="image/*"
                            class="text-sm text-slate-600"
                            @change="
                                form.foto =
                                    ($event.target as HTMLInputElement)
                                        .files?.[0] ?? null
                            "
                        />
                        <span
                            v-if="form.errors.foto"
                            class="text-xs font-normal text-red-600"
                            >{{ form.errors.foto }}</span
                        >
                    </label>

                    <label
                        class="flex items-start gap-2 text-sm font-medium text-slate-700"
                    >
                        <input
                            v-model="form.aceptacion"
                            type="checkbox"
                            class="mt-1 rounded border-slate-300 text-brand-ink focus:ring-brand-gold"
                        />
                        <span>
                            Acepto las condiciones del contrato, incluido el
                            anticipo de {{ pesos(anticipoCents) }}.
                        </span>
                    </label>
                    <span
                        v-if="form.errors.aceptacion"
                        class="text-xs font-normal text-red-600"
                        >{{ form.errors.aceptacion }}</span
                    >

                    <p v-if="form.errors.reserva" class="text-sm text-red-600">
                        {{ form.errors.reserva }}
                    </p>
                    <p v-if="form.errors.licencia" class="text-sm text-red-600">
                        {{ form.errors.licencia }}
                    </p>

                    <div>
                        <Button
                            type="submit"
                            :disabled="form.processing || Boolean(bloqueo)"
                        >
                            Registrar entrega
                        </Button>
                    </div>
                </form>
            </Card>
        </div>
    </AppLayout>
</template>
