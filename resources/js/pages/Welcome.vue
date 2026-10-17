<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import StorefrontCategories from '@/components/storefront/StorefrontCategories.vue';
import StorefrontCta from '@/components/storefront/StorefrontCta.vue';
import StorefrontFaq from '@/components/storefront/StorefrontFaq.vue';
import StorefrontFleet from '@/components/storefront/StorefrontFleet.vue';
import StorefrontFooter from '@/components/storefront/StorefrontFooter.vue';
import StorefrontHeader from '@/components/storefront/StorefrontHeader.vue';
import StorefrontHero from '@/components/storefront/StorefrontHero.vue';
import StorefrontHowItWorks from '@/components/storefront/StorefrontHowItWorks.vue';
import StorefrontPolicies from '@/components/storefront/StorefrontPolicies.vue';
import StorefrontQuality from '@/components/storefront/StorefrontQuality.vue';
import StorefrontTestimonials from '@/components/storefront/StorefrontTestimonials.vue';
import type { FeaturedVehicle, HomeSearch } from '@/types';

defineProps<{
    featuredVehicles: FeaturedVehicle[];
    search: HomeSearch;
}>();
</script>

<template>
    <Head title="RENT-GO" />
    <!-- TODO: REEMPLAZAR MOCK CON PROPS DINÁMICAS DEL BACKEND CUANDO EL CATÁLOGOS/RESERVAS ESTÉ LISTO -->
    <div class="min-h-screen bg-neutral-100 text-slate-900">
        <StorefrontHeader />
        <StorefrontHero :search="search" />
        <StorefrontFleet />
        <StorefrontCategories />
        <StorefrontQuality />
        <StorefrontHowItWorks />
        <StorefrontPolicies />
        <StorefrontTestimonials />
        <StorefrontFaq />
        <StorefrontCta />
        <StorefrontFooter />
    </div>
</template>
