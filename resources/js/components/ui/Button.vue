<script setup lang="ts">
withDefaults(
    defineProps<{
        type?: 'button' | 'submit' | 'reset';
        variant?: 'primary' | 'secondary' | 'ghost';
        disabled?: boolean;
    }>(),
    {
        type: 'button',
        variant: 'primary',
        disabled: false,
    },
);
</script>

<template>
    <button
        :type="type"
        :disabled="disabled"
        class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold transition focus:ring-2 focus:ring-brand-gold focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
        :class="{
            'bg-brand-ink text-white hover:bg-brand-ink/90':
                variant === 'primary',
            'border border-brand-ink bg-white text-brand-ink hover:bg-brand-ink/5':
                variant === 'secondary',
            'text-brand-ink hover:bg-brand-ink/5': variant === 'ghost',
        }"
    >
        <slot />
    </button>
</template>
