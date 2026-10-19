<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { index as listarPagos } from '@/actions/App/Http/Controllers/PagoController';
import Badge from '@/components/ui/Badge.vue';
import Card from '@/components/ui/Card.vue';
import Table from '@/components/ui/Table.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { PagoFactura, PagoIntento, PagoMetodo } from '@/types';

type Metodo = PagoMetodo['value'];

const props = defineProps<{
    reservation: {
        id: number;
        estado: string;
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
    invoice: PagoFactura | null;
    amountCents: number;
    currency: string;
    methods: PagoMetodo[];
    lastPayment: PagoIntento | null;
    storeUrl: string;
}>();

const form = useForm({
    metodo: 'tarjeta' as Metodo,
    monto_centavos: props.amountCents,
    numero_tarjeta: '',
});

function pesos(centavos: number): string {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: props.currency,
        maximumFractionDigits: 0,
    }).format(centavos / 100);
}

function etiquetaMetodo(metodo: string): string {
    return props.methods.find((item) => item.value === metodo)?.label ?? metodo;
}

function enviar(): void {
    form.post(props.storeUrl);
}
</script>

<template>
    <Head title="Registrar pago" />
    <AppLayout>
        <div class="flex flex-col gap-8">
            <div>
                <p
                    class="text-sm font-semibold tracking-wide text-brand-teal uppercase"
                >
                    Cobro
                </p>
                <h1 class="mt-1 text-3xl font-extrabold text-brand-navy">
                    Registrar pago #{{ reservation.id }}
                </h1>
                <p class="mt-2 text-sm text-slate-500">
                    Estado {{ reservation.estado }} · Moneda {{ currency }}
                </p>
                <p class="mt-2 max-w-2xl text-slate-600">
                    La pasarela es simulada: una tarjeta que termina en 0000 se
                    rechaza y la factura sigue pendiente para reintentar.
                </p>
                <Link
                    :href="listarPagos.url()"
                    class="mt-3 inline-block text-sm font-semibold text-brand-teal hover:text-brand-teal-dark"
                >
                    Volver al listado de pagos
                </Link>
            </div>

            <div
                v-if="lastPayment?.estado === 'rechazado'"
                class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800"
                role="alert"
            >
                <p class="font-semibold">Pago rechazado</p>
                <p class="mt-1">
                    {{
                        lastPayment.mensaje ??
                        'La pasarela simulada rechazó el cobro.'
                    }}
                    La factura sigue pendiente. Puedes reintentar con otro
                    método o con otra tarjeta.
                </p>
            </div>

            <div class="grid gap-5 lg:grid-cols-3">
                <Card>
                    <h2 class="text-lg font-semibold text-brand-navy">
                        Cliente
                    </h2>
                    <dl class="mt-4 grid gap-3 text-sm">
                        <div>
                            <dt class="text-slate-500">Nombre</dt>
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
                    </dl>
                </Card>

                <Card>
                    <h2 class="text-lg font-semibold text-brand-navy">
                        Factura
                    </h2>
                    <p class="mt-4 text-3xl font-extrabold text-brand-navy">
                        {{ pesos(amountCents) }}
                    </p>
                    <p class="mt-2 text-sm text-slate-500">
                        {{ invoice?.numero ?? 'Sin factura' }}
                    </p>
                    <Badge
                        v-if="invoice"
                        class="mt-3"
                        :tone="
                            invoice.estado === 'pagada' ? 'success' : 'warning'
                        "
                    >
                        {{ invoice.estado }}
                    </Badge>
                </Card>
            </div>

            <Card>
                <h2 class="text-lg font-semibold text-brand-navy">
                    Desglose de la factura
                </h2>
                <Table
                    v-if="invoice"
                    class="mt-5"
                    :headers="['Concepto', 'Monto']"
                >
                    <tr v-for="linea in invoice.desglose" :key="linea.concepto">
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
                            {{ pesos(invoice.subtotalCents) }}
                        </td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 text-slate-600">IVA</td>
                        <td class="px-4 py-3 font-medium text-brand-navy">
                            {{ pesos(invoice.taxCents) }}
                        </td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-semibold text-brand-navy">
                            Total
                        </td>
                        <td class="px-4 py-3 font-semibold text-brand-navy">
                            {{ pesos(invoice.totalCents) }}
                        </td>
                    </tr>
                </Table>
                <p v-else class="mt-4 text-sm text-slate-500">
                    Esta reserva no tiene una factura pendiente.
                </p>
            </Card>

            <Card>
                <form class="flex flex-col gap-5" @submit.prevent="enviar">
                    <h2 class="text-lg font-semibold text-brand-navy">
                        Registrar cobro
                    </h2>

                    <label
                        class="flex flex-col gap-1.5 text-sm font-medium text-slate-700"
                    >
                        <span>Método</span>
                        <select
                            v-model="form.metodo"
                            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 outline-none focus:border-brand-teal focus:ring-2 focus:ring-brand-teal/30"
                        >
                            <option
                                v-for="method in methods"
                                :key="method.value"
                                :value="method.value"
                            >
                                {{ method.label }}
                            </option>
                        </select>
                        <span
                            v-if="form.errors.metodo"
                            class="text-xs font-normal text-red-600"
                        >
                            {{ form.errors.metodo }}
                        </span>
                    </label>

                    <label
                        v-if="form.metodo === 'tarjeta'"
                        class="flex flex-col gap-1.5 text-sm font-medium text-slate-700"
                    >
                        <span>Número de tarjeta</span>
                        <input
                            v-model="form.numero_tarjeta"
                            type="text"
                            inputmode="numeric"
                            placeholder="4111111111111111"
                            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 outline-none focus:border-brand-teal focus:ring-2 focus:ring-brand-teal/30"
                        />
                        <span class="text-xs font-normal text-slate-500">
                            Simulación: termina en 0000 para rechazo; cualquier
                            otra para aprobación.
                        </span>
                        <span
                            v-if="form.errors.numero_tarjeta"
                            class="text-xs font-normal text-red-600"
                        >
                            {{ form.errors.numero_tarjeta }}
                        </span>
                    </label>

                    <p
                        v-if="form.errors.monto_centavos"
                        class="text-sm text-red-600"
                    >
                        {{ form.errors.monto_centavos }}
                    </p>

                    <p v-if="lastPayment" class="text-sm text-slate-500">
                        Último intento:
                        {{ etiquetaMetodo(lastPayment.metodo) }} ·
                        {{ lastPayment.estado }}
                        <span v-if="lastPayment.referencia">
                            · {{ lastPayment.referencia }}
                        </span>
                    </p>

                    <div>
                        <button
                            type="submit"
                            :disabled="form.processing || amountCents === 0"
                            class="inline-flex items-center justify-center rounded-lg bg-brand-teal px-4 py-2 text-sm font-semibold text-white hover:bg-brand-teal-dark disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Cobrar {{ pesos(amountCents) }}
                        </button>
                    </div>
                </form>
            </Card>
        </div>
    </AppLayout>
</template>
