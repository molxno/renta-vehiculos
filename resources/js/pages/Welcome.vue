<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import Input from '@/components/ui/Input.vue';
import Table from '@/components/ui/Table.vue';

const search = defineModel<string>({ default: '' });
</script>

<template>
    <Head title="Inicio" />
    <AppLayout role="coordinador">
        <div class="flex flex-col gap-8">
            <div
                class="flex flex-col justify-between gap-4 md:flex-row md:items-end"
            >
                <div>
                    <p
                        class="text-sm font-semibold tracking-wide text-brand-gold uppercase"
                    >
                        Operaciones
                    </p>
                    <h1 class="mt-1 text-3xl font-bold text-brand-ink">
                        Panel de reservas
                    </h1>
                    <p class="mt-2 max-w-2xl text-slate-600">
                        Consulta disponibilidad y acompaña cada reserva desde la
                        entrega hasta la devolución.
                    </p>
                </div>
                <Button>Crear reserva</Button>
            </div>

            <div class="grid gap-5 md:grid-cols-3">
                <Card>
                    <p class="text-sm text-slate-500">Reservas activas</p>
                    <p class="mt-2 text-3xl font-bold text-brand-ink">24</p>
                    <Badge tone="success">+8% esta semana</Badge>
                </Card>
                <Card>
                    <p class="text-sm text-slate-500">Entregas de hoy</p>
                    <p class="mt-2 text-3xl font-bold text-brand-ink">8</p>
                    <Badge tone="warning">3 pendientes</Badge>
                </Card>
                <Card>
                    <p class="text-sm text-slate-500">Vehículos disponibles</p>
                    <p class="mt-2 text-3xl font-bold text-brand-ink">42</p>
                    <Badge>Flota total: 56</Badge>
                </Card>
            </div>

            <Card>
                <div
                    class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between"
                >
                    <div>
                        <h2 class="text-lg font-semibold text-brand-ink">
                            Reservas recientes
                        </h2>
                        <p class="text-sm text-slate-500">
                            Seguimiento de las últimas solicitudes.
                        </p>
                    </div>
                    <Input
                        v-model="search"
                        label="Buscar"
                        placeholder="Cliente o reserva"
                    />
                </div>
                <div class="mt-5">
                    <Table :headers="['Reserva', 'Cliente', 'Estado']">
                        <tr>
                            <td class="px-4 py-3 font-medium text-brand-ink">
                                RV-1042
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                Laura Gómez
                            </td>
                            <td class="px-4 py-3">
                                <Badge tone="success">Confirmada</Badge>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-medium text-brand-ink">
                                RV-1041
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                Carlos Pérez
                            </td>
                            <td class="px-4 py-3">
                                <Badge tone="warning">Por confirmar</Badge>
                            </td>
                        </tr>
                    </Table>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
