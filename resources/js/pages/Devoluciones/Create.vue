<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { index as listarDevoluciones } from '@/actions/App/Http/Controllers/DevolucionController';
import Badge from '@/components/ui/Badge.vue';
import Card from '@/components/ui/Card.vue';
import Table from '@/components/ui/Table.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { DevolucionCobro, DevolucionDano } from '@/types';

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
    estimatedChargesCents: number;
    cobro: DevolucionCobro;
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
        danos: DevolucionDano[];
    };
    nivelesCombustible: Array<{ value: string; label: string }>;
}>();

function fechaHoraLocal(): string {
    const now = new Date();
    const offset = now.getTimezoneOffset() * 60_000;

    return new Date(now.getTime() - offset).toISOString().slice(0, 16);
}

function aFechaHoraLocal(valor: string): string {
    if (valor === '') {
        return fechaHoraLocal();
    }

    const fecha = new Date(
        valor.includes('T') ? valor : valor.replace(' ', 'T'),
    );

    if (Number.isNaN(fecha.getTime())) {
        return fechaHoraLocal();
    }

    const offset = fecha.getTimezoneOffset() * 60_000;

    return new Date(fecha.getTime() - offset).toISOString().slice(0, 16);
}

const form = useForm({
    devuelto_at: aFechaHoraLocal(props.filters.devuelto_at),
    checklist: {
        kilometraje: props.filters.kilometraje,
        combustible: props.filters.combustible,
    },
    danos: (props.filters.danos ?? []).map((dano) => ({
        descripcion: dano.descripcion,
        monto_pesos: String(Math.round(dano.monto_centavos / 100)),
    })),
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

function danosParaEnvio(): Array<{
    descripcion: string;
    monto_centavos: number;
}> {
    return form.danos
        .filter(
            (dano) => dano.descripcion.trim() !== '' || dano.monto_pesos !== '',
        )
        .map((dano) => ({
            descripcion: dano.descripcion,
            monto_centavos: Math.round(Number(dano.monto_pesos || 0) * 100),
        }));
}

function agregarDano(): void {
    form.danos.push({ descripcion: '', monto_pesos: '' });
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
            danos: danosParaEnvio(),
        },
        { preserveScroll: true },
    );
}

function enviar(): void {
    form.transform((data) => ({
        ...data,
        danos: danosParaEnvio(),
    })).post(props.storeUrl, { forceFormData: true });
}
</script>

<template>
    <Head title="Registrar devolución" />
    <AppLayout>
        <div class="flex flex-col gap-8">
            <div>
                <p
                    class="text-sm font-semibold tracking-wide text-brand-teal uppercase"
                >
                    Devolución
                </p>
                <h1 class="mt-1 text-3xl font-extrabold text-brand-navy">
                    Registrar devolución #{{ reservation.id }}
                </h1>
                <p class="mt-2 text-sm text-slate-500">
                    Estado {{ reservation.estado }} · Renta
                    {{ pesos(reservation.total_centavos) }}
                </p>
                <p class="mt-2 max-w-2xl text-slate-600">
                    Compara el acta de entrega, registra kilometraje, gasolina y
                    daños, y confirma el desglose del cargo antes de facturar.
                </p>
                <Link
                    :href="listarDevoluciones.url()"
                    class="mt-3 inline-block text-sm font-semibold text-brand-teal hover:text-brand-teal-dark"
                >
                    Volver al listado de devoluciones
                </Link>
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
                    <h2 class="text-lg font-semibold text-brand-navy">
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
                            <dd class="font-medium text-brand-navy">
                                {{ checklist.kilometraje ?? 'No registrado' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">
                                Combustible de salida
                            </dt>
                            <dd class="font-medium text-brand-navy">
                                {{ checklist.combustible ?? 'No registrado' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Fin contractual</dt>
                            <dd class="font-medium text-brand-navy">
                                {{ reservation.fin }}
                            </dd>
                        </div>
                    </dl>
                </Card>
            </div>

            <Card>
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold text-brand-navy">
                        Vista previa del cargo
                    </h2>
                    <Badge
                        :tone="
                            estimatedChargesCents > 0 ? 'warning' : 'success'
                        "
                    >
                        Cargos extra {{ pesos(estimatedChargesCents) }}
                    </Badge>
                </div>
                <p class="mt-4 text-3xl font-extrabold text-brand-navy">
                    {{ pesos(cobro.total_centavos) }}
                </p>
                <p class="mt-2 text-sm text-slate-500">
                    El desglose lo calcula el servidor. Actualiza el costo
                    después de cambiar hora, kilometraje, gasolina o daños.
                </p>
                <Table class="mt-5" :headers="['Concepto', 'Monto']">
                    <tr v-for="linea in cobro.desglose" :key="linea.concepto">
                        <td class="px-4 py-3 text-slate-600">
                            {{ linea.concepto }}
                        </td>
                        <td class="px-4 py-3 font-medium text-brand-navy">
                            {{ pesos(linea.monto_centavos) }}
                        </td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 text-slate-600">Subtotal</td>
                        <td class="px-4 py-3 font-medium text-brand-navy">
                            {{ pesos(cobro.subtotal_centavos) }}
                        </td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 text-slate-600">IVA</td>
                        <td class="px-4 py-3 font-medium text-brand-navy">
                            {{ pesos(cobro.impuestos_centavos) }}
                        </td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-semibold text-brand-navy">
                            Total
                        </td>
                        <td class="px-4 py-3 font-semibold text-brand-navy">
                            {{ pesos(cobro.total_centavos) }}
                        </td>
                    </tr>
                </Table>
            </Card>

            <Card>
                <form class="flex flex-col gap-5" @submit.prevent="enviar">
                    <h2 class="text-lg font-semibold text-brand-navy">
                        Condiciones de regreso
                    </h2>

                    <label
                        class="flex flex-col gap-1.5 text-sm font-medium text-slate-700"
                    >
                        <span>Fecha y hora de devolución</span>
                        <input
                            v-model="form.devuelto_at"
                            type="datetime-local"
                            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 outline-none focus:border-brand-teal focus:ring-2 focus:ring-brand-teal/30"
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

                    <div>
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold text-brand-navy">
                                Daños
                            </h3>
                            <button
                                type="button"
                                class="rounded-lg border border-brand-navy bg-white px-3 py-1.5 text-xs font-semibold text-brand-navy hover:bg-slate-50"
                                @click="agregarDano"
                            >
                                Agregar daño
                            </button>
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
                                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-brand-teal focus:ring-2 focus:ring-brand-teal/30"
                            />
                            <input
                                v-model="dano.monto_pesos"
                                type="number"
                                min="0"
                                placeholder="Monto COP"
                                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-brand-teal focus:ring-2 focus:ring-brand-teal/30"
                            />
                            <button
                                type="button"
                                class="text-sm font-semibold text-slate-500 hover:text-red-600"
                                @click="quitarDano(indice)"
                            >
                                Quitar
                            </button>
                        </div>
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
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-lg border border-brand-navy bg-white px-4 py-2 text-sm font-semibold text-brand-navy hover:bg-slate-50"
                            @click="actualizarCosto"
                        >
                            Actualizar costo
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center justify-center rounded-lg bg-brand-teal px-4 py-2 text-sm font-semibold text-white hover:bg-brand-teal-dark disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Confirmar devolución
                        </button>
                    </div>
                </form>
            </Card>
        </div>
    </AppLayout>
</template>
