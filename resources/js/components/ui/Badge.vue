<script setup lang="ts">
withDefaults(
    defineProps<{
        tone?: 'default' | 'success' | 'warning';
    }>(),
    { tone: 'default' },
);
</script>

<template>
    <span
        class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
        :class="{
            'bg-slate-100 text-slate-700': tone === 'default',
            'bg-emerald-100 text-emerald-700': tone === 'success',
            'bg-amber-100 text-amber-700': tone === 'warning',
        }"
    >
        <slot />
    </span>
</template>
