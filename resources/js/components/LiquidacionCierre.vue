<script setup lang="ts">
import type { AnticipoLiquidacion } from '@/types';

withDefaults(
    defineProps<{
        anticipos: AnticipoLiquidacion[];
        saldoCentavos: number;
        pesos: (centavos: number) => string;
        cellClass?: string;
    }>(),
    {
        cellClass: 'text-brand-navy',
    },
);
</script>

<template>
    <tr v-for="anticipo in anticipos" :key="anticipo.referencia">
        <td class="px-4 py-3 text-slate-600">
            Anticipo pagado en la entrega ({{ anticipo.referencia }})
        </td>
        <td :class="['px-4 py-3 font-medium', cellClass]">
            {{ pesos(anticipo.monto_centavos) }}
        </td>
    </tr>
    <tr>
        <td :class="['px-4 py-3 font-semibold', cellClass]">Saldo a pagar</td>
        <td :class="['px-4 py-3 font-semibold', cellClass]">
            {{ pesos(saldoCentavos) }}
        </td>
    </tr>
</template>
