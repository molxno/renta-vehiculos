<script setup lang="ts">
withDefaults(
    defineProps<{
        compact?: boolean;
    }>(),
    { compact: false },
);
</script>

<template>
    <span class="inline-flex items-center gap-2 font-semibold tracking-tight">
        <span
            class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-teal text-[10px] font-bold text-white"
        >
            GO
        </span>
        <span :class="compact ? 'text-base' : 'text-lg'">
            RENT<span class="text-brand-teal">-GO</span>
        </span>
    </span>
</template>
