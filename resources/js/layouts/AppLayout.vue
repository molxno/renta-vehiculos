<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import StorefrontFooter from '@/components/storefront/StorefrontFooter.vue';
import StorefrontHeader from '@/components/storefront/StorefrontHeader.vue';

const page = usePage();
const flashError = computed(() => page.props.flash?.error ?? null);
const flashSuccess = computed(() => page.props.flash?.success ?? null);
</script>

<template>
    <div
        class="flex min-h-screen flex-col bg-brand-navy text-slate-800 antialiased"
    >
        <StorefrontHeader />
        <main class="w-full flex-1 bg-slate-50">
            <div class="mx-auto max-w-7xl px-6 py-8">
                <div
                    v-if="flashError"
                    class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800"
                    role="alert"
                >
                    {{ flashError }}
                </div>
                <div
                    v-if="flashSuccess"
                    class="mb-6 rounded-xl border border-teal-200 bg-teal-50 p-4 text-sm text-teal-900"
                    role="status"
                >
                    {{ flashSuccess }}
                </div>
                <slot />
            </div>
        </main>
        <StorefrontFooter />
    </div>
</template>
