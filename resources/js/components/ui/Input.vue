<script setup lang="ts">
defineProps<{
    modelValue?: string | number;
    label?: string;
    type?: string;
    placeholder?: string;
    error?: string;
    min?: string | number;
    max?: string | number;
    step?: string | number;
}>();

defineEmits<{
    'update:modelValue': [value: string];
}>();
</script>

<template>
    <label class="flex flex-col gap-1.5 text-sm font-medium text-slate-700">
        <span v-if="label">{{ label }}</span>
        <input
            :type="type ?? 'text'"
            :value="modelValue"
            :placeholder="placeholder"
            :min="min"
            :max="max"
            :step="step"
            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-brand-gold focus:ring-2 focus:ring-brand-gold/30"
            @input="
                $emit(
                    'update:modelValue',
                    ($event.target as HTMLInputElement).value,
                )
            "
        />
        <span v-if="error" class="text-xs font-normal text-red-600">{{
            error
        }}</span>
    </label>
</template>
