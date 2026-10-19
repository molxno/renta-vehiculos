<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import type { EntregaLicencia } from '@/types';
import { formatFechaHora } from '@/lib/utils';

defineProps<{
    acta: {
        numero: string;
        entregadoAt: string | null;
        firmadoAt: string | null;
        firmanteNombre: string | null;
        licenciaResultado: string | null;
        kilometraje: number | null;
        combustible: string | null;
    };
    reservation: {
        id: number;
        qrCode: string | null;
    };
    cliente: {
        nombre: string;
        documento: string;
    };
    vehiculo: {
        label: string;
        placa: string;
    };
    licencia: EntregaLicencia;
    coordinador: string | null;
    aviso: string;
}>();

function fecha(value: string | null): string {
    return formatFechaHora(value);
}

function imprimir(): void {
    window.print();
}
</script>

<template>
    <Head :title="`Acta ${acta.numero}`" />
    <div class="min-h-screen bg-slate-50 text-slate-800">
        <header class="no-print bg-brand-navy text-white print:hidden">
            <div
                class="mx-auto flex max-w-3xl items-center justify-between px-6 py-4"
            >
                <p class="text-sm font-semibold tracking-wide text-brand-teal">
                    RENT-GO
                </p>
                <button
                    type="button"
                    class="rounded-lg bg-brand-teal px-4 py-2 text-sm font-semibold text-white hover:bg-brand-teal-dark"
                    @click="imprimir"
                >
                    Imprimir / guardar PDF
                </button>
            </div>
        </header>

        <article
            class="acta-documento mx-auto my-8 max-w-3xl bg-white px-8 py-10 shadow-sm print:my-0 print:max-w-none print:shadow-none"
        >
            <p
                class="text-sm font-semibold tracking-wide text-brand-teal uppercase"
            >
                Acta de entrega
            </p>
            <h1 class="mt-2 text-3xl font-extrabold text-brand-navy">
                {{ acta.numero }}
            </h1>
            <p class="mt-2 text-sm text-slate-600">
                Reserva #{{ reservation.id }}
            </p>

            <dl class="mt-8 grid gap-5 sm:grid-cols-2">
                <div>
                    <dt class="text-sm text-slate-500">Fecha y hora real</dt>
                    <dd class="mt-1 font-semibold text-brand-navy">
                        {{ fecha(acta.entregadoAt) }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Coordinador</dt>
                    <dd class="mt-1 font-semibold text-brand-navy">
                        {{ coordinador ?? 'No registrado' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Cliente</dt>
                    <dd class="mt-1 font-semibold text-brand-navy">
                        {{ cliente.nombre }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Documento</dt>
                    <dd class="mt-1 font-semibold text-brand-navy">
                        {{ cliente.documento }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Licencia</dt>
                    <dd class="mt-1 font-semibold text-brand-navy">
                        <template v-if="licencia">
                            {{ licencia.numero }} · {{ licencia.categoria }} ·
                            vence {{ licencia.fecha_vencimiento }}
                        </template>
                        <template v-else>Sin licencia registrada</template>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">
                        Verificación de licencia
                    </dt>
                    <dd class="mt-1 font-semibold text-brand-navy">
                        {{ acta.licenciaResultado ?? 'Sin verificación' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Vehículo</dt>
                    <dd class="mt-1 font-semibold text-brand-navy">
                        {{ vehiculo.label }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Placa</dt>
                    <dd class="mt-1 font-semibold text-brand-navy">
                        {{ vehiculo.placa }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Kilometraje inicial</dt>
                    <dd class="mt-1 font-semibold text-brand-navy">
                        {{ acta.kilometraje ?? 'No registrado' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Nivel de gasolina</dt>
                    <dd class="mt-1 font-semibold text-brand-navy">
                        {{ acta.combustible ?? 'No registrado' }}
                    </dd>
                </div>
            </dl>

            <section class="mt-8 border-t border-slate-200 pt-6">
                <h2 class="text-lg font-semibold text-brand-navy">
                    Firma digital
                </h2>
                <p class="mt-2 font-medium text-brand-navy">
                    {{ acta.firmanteNombre ?? 'Sin firmante' }}
                </p>
                <p class="mt-1 text-sm text-slate-600">
                    Firmada el {{ fecha(acta.firmadoAt) }}
                </p>
            </section>

            <section
                class="mt-8 flex flex-col items-center border-t border-slate-200 pt-6 text-center"
            >
                <h2 class="text-lg font-semibold text-brand-navy">
                    Código QR de la reserva
                </h2>
                <div
                    class="mt-4 flex h-44 w-44 flex-col items-center justify-center gap-3 border-8 border-brand-navy bg-white p-3"
                    role="img"
                    :aria-label="`Código QR de demostración ${reservation.qrCode ?? ''}`"
                >
                    <svg
                        aria-hidden="true"
                        viewBox="0 0 64 64"
                        class="h-20 w-20 text-brand-navy"
                        fill="currentColor"
                    >
                        <path
                            d="M4 4h20v20H4V4Zm6 6v8h8v-8h-8Zm30-6h20v20H40V4Zm6 6v8h8v-8h-8ZM4 40h20v20H4V40Zm6 6v8h8v-8h-8Zm24-42h6v6h-6V4Zm-6 12h6v6h-6v-6Zm6 6h6v6h-6v-6Zm6 6h6v6h-6v-6Zm12 0h6v6h-6v-6Zm-18 6h6v6h-6v-6Zm12 0h6v6h-6v-6Zm12 6h6v6h-6v-6Zm-24 6h6v6h-6v-6Zm12 0h6v6h-6v-6Zm6 6h6v6h-6v-6Zm6-6h6v6h-6v-6Zm-24 6h6v6h-6v-6Z"
                        />
                    </svg>
                    <span
                        class="text-xs font-bold tracking-widest text-brand-navy"
                    >
                        QR DEMO
                    </span>
                </div>
                <p
                    class="mt-4 font-mono text-sm font-semibold break-all text-brand-navy"
                >
                    {{ reservation.qrCode ?? 'Código no disponible' }}
                </p>
            </section>

            <p class="mt-10 text-center text-xs tracking-wide text-slate-500">
                {{ aviso }}
            </p>
        </article>
    </div>
</template>

<style>
@media print {
    header.no-print,
    .no-print {
        display: none !important;
    }

    body,
    html {
        background: white !important;
    }

    .acta-documento {
        margin: 0 !important;
        box-shadow: none !important;
    }
}
</style>
