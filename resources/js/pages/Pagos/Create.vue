<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import Input from '@/components/ui/Input.vue';

type Metodo = 'tarjeta' | 'transferencia' | 'efectivo';

const props = defineProps<{
    reservation: {
        id: number;
        estado: string;
        moneda: string;
    };
    amountCents: number;
    currency: string;
    methods: Metodo[];
    lastPayment: {
        estado: string;
        mensaje: string | null;
    } | null;
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

function enviar(): void {
    form.post(props.storeUrl);
}
</script>

<template>
    <Head title="Registrar pago" />
    <AppLayout role="cajero">
        <div class="flex flex-col gap-8">
            <div>
                <p
                    class="text-sm font-semibold tracking-wide text-brand-gold uppercase"
                >
                    Pagos
                </p>
                <h1 class="mt-1 text-3xl font-bold text-brand-ink">
                    Registrar pago #{{ reservation.id }}
                </h1>
                <p class="mt-2 max-w-2xl text-slate-600">
                    La pasarela de prueba aprueba cualquier tarjeta que no
                    termine en 0000. Tras un rechazo puedes reintentar.
                </p>
            </div>

            <div class="grid gap-5 lg:grid-cols-2">
                <Card>
                    <h2 class="text-lg font-semibold text-brand-ink">
                        Factura pendiente
                    </h2>
                    <p class="mt-4 text-3xl font-bold text-brand-ink">
                        {{ pesos(amountCents) }}
                    </p>
                    <p class="mt-2 text-sm text-slate-500">
                        Estado de la reserva: {{ reservation.estado }}
                    </p>
                    <div v-if="lastPayment" class="mt-4">
                        <Badge
                            :tone="
                                lastPayment.estado === 'rechazado'
                                    ? 'warning'
                                    : 'success'
                            "
                        >
                            Último intento: {{ lastPayment.estado }}
                        </Badge>
                        <p
                            v-if="lastPayment.mensaje"
                            class="mt-2 text-sm text-slate-600"
                        >
                            {{ lastPayment.mensaje }}
                        </p>
                    </div>
                </Card>

                <Card>
                    <form class="flex flex-col gap-4" @submit.prevent="enviar">
                        <label
                            class="flex flex-col gap-1.5 text-sm font-medium text-slate-700"
                        >
                            <span>Método</span>
                            <select
                                v-model="form.metodo"
                                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 transition outline-none focus:border-brand-gold focus:ring-2 focus:ring-brand-gold/30"
                            >
                                <option
                                    v-for="method in methods"
                                    :key="method"
                                    :value="method"
                                >
                                    {{ method }}
                                </option>
                            </select>
                            <span
                                v-if="form.errors.metodo"
                                class="text-xs font-normal text-red-600"
                            >
                                {{ form.errors.metodo }}
                            </span>
                        </label>
                        <Input
                            v-if="form.metodo === 'tarjeta'"
                            v-model="form.numero_tarjeta"
                            label="Número de tarjeta"
                            placeholder="4111111111111111"
                            :error="form.errors.numero_tarjeta"
                        />
                        <p
                            v-if="form.errors.monto_centavos"
                            class="text-xs text-red-600"
                        >
                            {{ form.errors.monto_centavos }}
                        </p>
                        <Button type="submit" :disabled="form.processing">
                            Cobrar {{ pesos(amountCents) }}
                        </Button>
                    </form>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
