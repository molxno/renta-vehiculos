<script setup lang="ts">
import StorefrontFooter from '@/components/storefront/StorefrontFooter.vue';
import StorefrontHeader from '@/components/storefront/StorefrontHeader.vue';
</script>

<template>
    <div
        class="flex min-h-screen flex-col bg-brand-navy text-slate-800 antialiased"
    >
        <StorefrontHeader />
        <main class="w-full flex-1 bg-slate-50">
            <slot />
        </main>
        <StorefrontFooter />
    </div>
</template>
