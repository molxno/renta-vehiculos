<script setup lang="ts">
defineProps<{
    headers: string[];
}>();
</script>

<template>
    <div class="overflow-x-auto rounded-xl border border-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th
                        v-for="header in headers"
                        :key="header"
                        scope="col"
                        class="px-4 py-3 font-semibold text-slate-700"
                    >
                        {{ header }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                <slot />
            </tbody>
        </table>
    </div>
</template>
