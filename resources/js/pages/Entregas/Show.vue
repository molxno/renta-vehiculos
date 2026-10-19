<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { index as listarEntregas } from '@/actions/App/Http/Controllers/EntregaController';
import Badge from '@/components/ui/Badge.vue';
import Card from '@/components/ui/Card.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatFechaHora } from '@/lib/utils';

const props = defineProps<{
    reservation: {
        id: number;
        status: string;
        vehicleLabel: string;
        vehiclePlate: string;
        clientName: string;
        qrCode: string | null;
        currency: string;
    };
    acta: {
        entregadoAt: string | null;
        kilometraje: number | null;
        combustible: string | null;
    };
    anticipoCents: number;
    devolucionUrl: string;
}>();

function pesos(centavos: number): string {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: props.reservation.currency,
        maximumFractionDigits: 0,
    }).format(centavos / 100);
}

function fecha(value: string | null): string {
    return formatFechaHora(value);
}
</script>

<template>
    <Head title="Entrega registrada" />
    <AppLayout>
        <div class="flex flex-col gap-8">
            <div>
                <p
                    class="text-sm font-semibold tracking-wide text-brand-teal uppercase"
                >
                    Entrega confirmada
                </p>
                <h1 class="mt-1 text-3xl font-extrabold text-brand-navy">
                    El vehículo fue entregado
                </h1>
                <p class="mt-2 max-w-2xl text-slate-600">
                    La reserva #{{ reservation.id }} quedó entregada. El
                    vehículo {{ reservation.vehicleLabel }} ahora figura como
                    alquilado.
                </p>
            </div>

            <Card>
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-sm text-slate-500">Reserva</p>
                        <h2 class="mt-1 text-2xl font-bold text-brand-navy">
                            #{{ reservation.id }} ·
                            {{ reservation.vehicleLabel }}
                        </h2>
                        <p class="mt-1 text-sm text-slate-600">
                            {{ reservation.clientName }} · Placa
                            {{ reservation.vehiclePlate }}
                        </p>
                    </div>
                    <Badge tone="success">{{ reservation.status }}</Badge>
                </div>

                <dl class="mt-7 grid gap-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm text-slate-500">Hora de entrega</dt>
                        <dd class="mt-1 font-semibold text-brand-navy">
                            {{ fecha(acta.entregadoAt) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-slate-500">
                            Kilometraje inicial
                        </dt>
                        <dd class="mt-1 font-semibold text-brand-navy">
                            {{ acta.kilometraje ?? 'No registrado' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-slate-500">
                            Nivel de gasolina
                        </dt>
                        <dd class="mt-1 font-semibold text-brand-navy">
                            {{ acta.combustible ?? 'No registrado' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-slate-500">Anticipo</dt>
                        <dd class="mt-1 font-semibold text-brand-navy">
                            {{ pesos(anticipoCents) }}
                        </dd>
                    </div>
                </dl>

                <p
                    v-if="reservation.qrCode"
                    class="mt-6 font-mono text-sm text-slate-500"
                >
                    QR {{ reservation.qrCode }}
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <Link
                        :href="devolucionUrl"
                        class="inline-flex items-center justify-center rounded-lg bg-brand-teal px-4 py-2 text-sm font-semibold text-white hover:bg-brand-teal-dark"
                    >
                        Registrar devolución
                    </Link>
                    <Link
                        :href="listarEntregas.url()"
                        class="inline-flex items-center justify-center rounded-lg border border-brand-navy bg-white px-4 py-2 text-sm font-semibold text-brand-navy hover:bg-slate-50"
                    >
                        Volver a entregas
                    </Link>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
