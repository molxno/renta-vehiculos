<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import StorefrontCategories from '@/components/storefront/StorefrontCategories.vue';
import StorefrontCta from '@/components/storefront/StorefrontCta.vue';
import StorefrontFaq from '@/components/storefront/StorefrontFaq.vue';
import StorefrontFleet from '@/components/storefront/StorefrontFleet.vue';
import StorefrontHero from '@/components/storefront/StorefrontHero.vue';
import StorefrontHowItWorks from '@/components/storefront/StorefrontHowItWorks.vue';
import StorefrontPolicies from '@/components/storefront/StorefrontPolicies.vue';
import StorefrontQuality from '@/components/storefront/StorefrontQuality.vue';
import StorefrontTestimonials from '@/components/storefront/StorefrontTestimonials.vue';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import type { FeaturedVehicle, HomeSearch } from '@/types';

defineProps<{
    featuredVehicles: FeaturedVehicle[];
    vehiclesAvailableCount: number;
    search: HomeSearch;
}>();
</script>

<template>
    <StorefrontLayout>
        <Head title="RENT-GO" />
        <!-- TODO: REEMPLAZAR MOCK CON PROPS DINÁMICAS DEL BACKEND CUANDO EL CATÁLOGOS/RESERVAS ESTÉ LISTO -->
        <StorefrontHero
            :search="search"
            :vehicles-available-count="vehiclesAvailableCount"
        />
        <StorefrontFleet />
        <StorefrontCategories />
        <StorefrontQuality />
        <StorefrontHowItWorks />
        <StorefrontPolicies />
        <StorefrontTestimonials />
        <StorefrontFaq />
        <StorefrontCta />
    </StorefrontLayout>
</template>
