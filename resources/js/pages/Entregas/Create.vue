<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { index as listarEntregas } from '@/actions/App/Http/Controllers/EntregaController';
import Badge from '@/components/ui/Badge.vue';
import Card from '@/components/ui/Card.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { EntregaLicencia } from '@/types';

const props = defineProps<{
    reservation: {
        id: number;
        estado: string;
        inicio: string;
        fin: string;
        total_centavos: number;
        moneda: string;
    };
    cliente: {
        nombre: string;
        documento: string;
    };
    vehiculo: {
        label: string;
        placa: string;
    };
    qrCodigo: string | null;
    checklist: Record<string, unknown>;
    condicionesContrato: Record<string, unknown>;
    licencia: EntregaLicencia;
    bloqueo: string | null;
    anticipoCents: number;
    currency: string;
    storeUrl: string;
    nivelesCombustible: Array<{ value: string; label: string }>;
}>();

function fechaHoraLocal(): string {
    const now = new Date();
    const offset = now.getTimezoneOffset() * 60_000;

    return new Date(ahora.getTime() - offset).toISOString().slice(0, 16);
}

const form = useForm({
    entregado_at: fechaHoraLocal(),
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
    <AppLayout>
        <div class="flex flex-col gap-8">
            <div>
                <p
                    class="text-sm font-semibold tracking-wide text-brand-teal uppercase"
                >
                    Entrega
                </p>
                <h1 class="mt-1 text-3xl font-extrabold text-brand-navy">
                    Registrar entrega #{{ reservation.id }}
                </h1>
                <p class="mt-2 text-sm text-slate-500">
                    Estado {{ reservation.estado }} · Renta
                    {{ pesos(reservation.total_centavos) }}
                </p>
                <p class="mt-2 max-w-2xl text-slate-600">
                    Valida la licencia, registra kilometraje y combustible,
                    cobra el anticipo y confirma la firma del cliente.
                </p>
                <Link
                    :href="listarEntregas.url()"
                    class="mt-3 inline-block text-sm font-semibold text-brand-teal hover:text-brand-teal-dark"
                >
                    Volver al listado de entregas
                </Link>
            </div>

            <div
                v-if="bloqueo"
                class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800"
                role="alert"
            >
                <p class="font-semibold">Entrega bloqueada</p>
                <p class="mt-1">{{ bloqueo }}</p>
            </div>

            <div class="grid gap-5 lg:grid-cols-3">
                <Card>
                    <h2 class="text-lg font-semibold text-brand-navy">
                        Reserva y cliente
                    </h2>
                    <dl class="mt-4 grid gap-3 text-sm">
                        <div>
                            <dt class="text-slate-500">Cliente</dt>
                            <dd class="font-medium text-brand-navy">
                                {{ cliente.nombre }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Documento</dt>
                            <dd class="font-medium text-brand-navy">
                                {{ cliente.documento }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Código QR</dt>
                            <dd class="font-mono font-medium text-brand-navy">
                                {{ qrCodigo ?? 'Sin QR' }}
                            </dd>
                        </div>
                    </dl>
                </Card>

                <Card>
                    <h2 class="text-lg font-semibold text-brand-navy">
                        Vehículo
                    </h2>
                    <dl class="mt-4 grid gap-3 text-sm">
                        <div>
                            <dt class="text-slate-500">Modelo</dt>
                            <dd class="font-medium text-brand-navy">
                                {{ vehiculo.label }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Placa</dt>
                            <dd class="font-medium text-brand-navy">
                                {{ vehiculo.placa }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Periodo</dt>
                            <dd class="font-medium text-brand-navy">
                                {{ reservation.inicio }} —
                                {{ reservation.fin }}
                            </dd>
                        </div>
                    </dl>
                </Card>

                <Card>
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-semibold text-brand-navy">
                            Licencia de conducción
                        </h2>
                        <Badge :tone="bloqueo ? 'warning' : 'success'">
                            {{ bloqueo ? 'Bloqueada' : 'Vigente' }}
                        </Badge>
                    </div>
                    <dl v-if="licencia" class="mt-4 grid gap-3 text-sm">
                        <div>
                            <dt class="text-slate-500">Número</dt>
                            <dd class="font-medium text-brand-navy">
                                {{ licencia.numero }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Categoría</dt>
                            <dd class="font-medium text-brand-navy">
                                {{ licencia.categoria }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Vencimiento</dt>
                            <dd class="font-medium text-brand-navy">
                                {{ licencia.fecha_vencimiento }}
                            </dd>
                        </div>
                    </dl>
                    <p v-else class="mt-4 text-sm text-slate-500">
                        Este cliente no tiene una licencia registrada.
                    </p>
                </Card>
            </div>

            <Card>
                <h2 class="text-lg font-semibold text-brand-navy">Anticipo</h2>
                <p class="mt-4 text-3xl font-extrabold text-brand-navy">
                    {{ pesos(anticipoCents) }}
                </p>
                <p class="mt-2 text-sm text-slate-500">
                    Se registra como pago en efectivo al confirmar la entrega.
                    Kilometraje incluido:
                    {{ condicionesContrato.kilometraje_incluido ?? 200 }} km ·
                    Combustible:
                    {{ condicionesContrato.combustible ?? 'lleno' }}.
                </p>
            </Card>

            <Card>
                <form class="flex flex-col gap-5" @submit.prevent="enviar">
                    <h2 class="text-lg font-semibold text-brand-navy">
                        Acta de entrega
                    </h2>

                    <label
                        class="flex flex-col gap-1.5 text-sm font-medium text-slate-700"
                    >
                        <span>Fecha y hora de entrega</span>
                        <input
                            v-model="form.entregado_at"
                            type="datetime-local"
                            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 outline-none focus:border-brand-teal focus:ring-2 focus:ring-brand-teal/30"
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
                            <span>Kilometraje inicial</span>
                            <input
                                v-model="form.checklist.kilometraje"
                                type="number"
                                min="0"
                                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 outline-none focus:border-brand-teal focus:ring-2 focus:ring-brand-teal/30"
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
                            <span>Nivel de gasolina</span>
                            <select
                                v-model="form.checklist.combustible"
                                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 outline-none focus:border-brand-teal focus:ring-2 focus:ring-brand-teal/30"
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
                                class="rounded border-slate-300 text-brand-teal focus:ring-brand-teal"
                            />
                            <span>Documentos del vehículo</span>
                        </label>
                        <label
                            class="flex items-center gap-2 text-sm font-medium text-slate-700"
                        >
                            <input
                                v-model="form.checklist.llantas"
                                type="checkbox"
                                class="rounded border-slate-300 text-brand-teal focus:ring-brand-teal"
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
                            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 outline-none focus:border-brand-teal focus:ring-2 focus:ring-brand-teal/30"
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
                            class="mt-1 rounded border-slate-300 text-brand-teal focus:ring-brand-teal"
                        />
                        <span>
                            Firma digital: el cliente acepta las condiciones del
                            contrato, incluido el anticipo de
                            {{ pesos(anticipoCents) }}.
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
                        <button
                            type="submit"
                            :disabled="form.processing || Boolean(bloqueo)"
                            class="inline-flex items-center justify-center rounded-lg bg-brand-teal px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-teal-dark focus:ring-2 focus:ring-brand-teal focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Registrar entrega
                        </button>
                    </div>
                </form>
            </Card>
        </div>
    </AppLayout>
</template>
