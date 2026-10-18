<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import Table from '@/components/ui/Table.vue';

type CobroLinea = {
    concepto: string;
    monto_centavos: number;
};

type Cobro = {
    cargos_centavos: number;
    subtotal_centavos: number;
    impuestos_centavos: number;
    total_centavos: number;
    desglose: CobroLinea[];
};

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
    estimatedChargesCents: number;
    cobro: Cobro;
    actaEntrega: {
        entregado_at: string;
        checklist: Record<string, unknown> | null;
    } | null;
    storeUrl: string;
    createUrl: string;
    filters: {
        devuelto_at: string;
        kilometraje: string;
        combustible: string;
    };
    nivelesCombustible: Array<{ value: string; label: string }>;
}>();

const form = useForm({
    devuelto_at: props.filters.devuelto_at,
    checklist: {
        kilometraje: props.filters.kilometraje,
        combustible: props.filters.combustible,
    },
    danos: [] as Array<{
        descripcion: string;
        monto_centavos: number | string;
    }>,
    observaciones: '',
    evidencia: null as File | null,
});

function pesos(centavos: number): string {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: props.reservation.moneda,
        maximumFractionDigits: 0,
    }).format(centavos / 100);
}

function agregarDano(): void {
    form.danos.push({ descripcion: '', monto_centavos: '' });
}

function quitarDano(indice: number): void {
    form.danos.splice(indice, 1);
}

function actualizarCosto(): void {
    router.get(
        props.createUrl,
        {
            devuelto_at: form.devuelto_at,
            kilometraje: form.checklist.kilometraje,
            combustible: form.checklist.combustible,
        },
        { preserveScroll: true },
    );
}

function enviar(): void {
    form.post(props.storeUrl, { forceFormData: true });
}
</script>

<template>
    <Head title="Registrar devolución" />
    <AppLayout role="coordinador">
        <div class="flex flex-col gap-8">
            <div>
                <p
                    class="text-sm font-semibold tracking-wide text-brand-gold uppercase"
                >
                    Devolución
                </p>
                <h1 class="mt-1 text-3xl font-bold text-brand-ink">
                    Registrar devolución #{{ reservation.id }}
                </h1>
                <p class="mt-2 max-w-2xl text-slate-600">
                    Compara el estado de entrega con el de regreso, registra
                    daños o una foto opcional y confirma el costo final.
                </p>
            </div>

            <div class="grid gap-5 lg:grid-cols-2">
                <Card>
                    <h2 class="text-lg font-semibold text-brand-ink">
                        Acta de entrega
                    </h2>
                    <p v-if="actaEntrega" class="mt-2 text-sm text-slate-500">
                        Entregado el {{ actaEntrega.entregado_at }}
                    </p>
                    <p v-else class="mt-2 text-sm text-slate-500">
                        Esta reserva no tiene acta de entrega.
                    </p>
                    <dl class="mt-4 grid gap-3 text-sm">
                        <div>
                            <dt class="text-slate-500">
                                Kilometraje de salida
                            </dt>
                            <dd class="font-medium text-brand-ink">
                                {{ checklist.kilometraje ?? 'No registrado' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">
                                Combustible de salida
                            </dt>
                            <dd class="font-medium text-brand-ink">
                                {{ checklist.combustible ?? 'No registrado' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Fin contractual</dt>
                            <dd class="font-medium text-brand-ink">
                                {{ reservation.fin }}
                            </dd>
                        </div>
                    </dl>
                </Card>

                <Card>
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-semibold text-brand-ink">
                            Costo final estimado
                        </h2>
                        <Badge
                            :tone="
                                estimatedChargesCents > 0
                                    ? 'warning'
                                    : 'success'
                            "
                        >
                            Cargos extra {{ pesos(estimatedChargesCents) }}
                        </Badge>
                    </div>
                    <p class="mt-4 text-3xl font-bold text-brand-ink">
                        {{ pesos(cobro.total_centavos) }}
                    </p>
                    <Table class="mt-5" :headers="['Concepto', 'Monto']">
                        <tr
                            v-for="linea in cobro.desglose"
                            :key="linea.concepto"
                        >
                            <td class="px-4 py-3 text-slate-600">
                                {{ linea.concepto }}
                            </td>
                            <td class="px-4 py-3 font-medium text-brand-ink">
                                {{ pesos(linea.monto_centavos) }}
                            </td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 text-slate-600">IVA</td>
                            <td class="px-4 py-3 font-medium text-brand-ink">
                                {{ pesos(cobro.impuestos_centavos) }}
                            </td>
                        </tr>
                    </Table>
                </Card>
            </div>

            <Card>
                <form class="flex flex-col gap-5" @submit.prevent="enviar">
                    <h2 class="text-lg font-semibold text-brand-ink">
                        Condiciones de regreso
                    </h2>

                    <label
                        class="flex flex-col gap-1.5 text-sm font-medium text-slate-700"
                    >
                        <span>Fecha y hora de devolución</span>
                        <input
                            v-model="form.devuelto_at"
                            type="datetime-local"
                            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 transition outline-none focus:border-brand-gold focus:ring-2 focus:ring-brand-gold/30"
                        />
                        <span
                            v-if="form.errors.devuelto_at"
                            class="text-xs font-normal text-red-600"
                            >{{ form.errors.devuelto_at }}</span
                        >
                    </label>

                    <div class="grid gap-5 md:grid-cols-2">
                        <label
                            class="flex flex-col gap-1.5 text-sm font-medium text-slate-700"
                        >
                            <span>Kilometraje final</span>
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

                    <div>
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold text-brand-ink">
                                Daños
                            </h3>
                            <Button
                                type="button"
                                variant="secondary"
                                @click="agregarDano"
                            >
                                Agregar daño
                            </Button>
                        </div>
                        <div
                            v-for="(dano, indice) in form.danos"
                            :key="indice"
                            class="mt-3 grid gap-3 md:grid-cols-[1fr_160px_auto]"
                        >
                            <input
                                v-model="dano.descripcion"
                                type="text"
                                placeholder="Descripción"
                                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 transition outline-none focus:border-brand-gold focus:ring-2 focus:ring-brand-gold/30"
                            />
                            <input
                                v-model="dano.monto_centavos"
                                type="number"
                                min="0"
                                placeholder="Centavos"
                                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 transition outline-none focus:border-brand-gold focus:ring-2 focus:ring-brand-gold/30"
                            />
                            <Button
                                type="button"
                                variant="ghost"
                                @click="quitarDano(indice)"
                            >
                                Quitar
                            </Button>
                        </div>
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
                                form.evidencia =
                                    ($event.target as HTMLInputElement)
                                        .files?.[0] ?? null
                            "
                        />
                        <span
                            v-if="form.errors.evidencia"
                            class="text-xs font-normal text-red-600"
                            >{{ form.errors.evidencia }}</span
                        >
                    </label>

                    <p v-if="form.errors.reserva" class="text-sm text-red-600">
                        {{ form.errors.reserva }}
                    </p>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Button
                            type="button"
                            variant="secondary"
                            @click="actualizarCosto"
                        >
                            Actualizar costo
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            Registrar devolución
                        </Button>
                    </div>
                </form>
            </Card>
        </div>
    </AppLayout>
</template>
