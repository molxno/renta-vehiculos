<script setup lang="ts">
defineProps<{
    name: string;
}>();
</script>

<template>
    <svg
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.75"
        stroke-linecap="round"
        stroke-linejoin="round"
        class="h-6 w-6"
        aria-hidden="true"
    >
        <path
            v-if="name === 'sedan'"
            d="M4 16h16M5 16l1.5-6h11L19 16M7 16v2M17 16v2M8 10 9.5 7h5L16 10"
        />
        <path
            v-else-if="name === 'suv'"
            d="M4 16h16M5 16l2-7h7l5 7M8 16v2M16 16v2M7 9h8V7H9z"
        />
        <path
            v-else-if="name === 'sport'"
            d="M4 16h16M5 16l3-6h7l4 6M8 16v2M16 16v2M12 6l2 4"
        />
        <path
            v-else-if="name === 'truck'"
            d="M3 16h13V8H3zM16 11h4l2 3v2h-6M6 16v2M18 16v2"
        />
        <g v-else-if="name === 'electric'">
            <path d="M13 2 6 13h6l-1 9 7-11h-6z" />
        </g>
        <g v-else-if="name === 'luxury'">
            <path
                d="M12 3l2.2 6.6H21l-5.4 3.9 2.1 6.5L12 16.6 6.3 20l2.1-6.5L3 9.6h6.8z"
            />
        </g>
        <g v-else-if="name === 'shield'">
            <path d="M12 3 5 6v6c0 4.4 3 7.4 7 9 4-1.6 7-4.6 7-9V6z" />
            <path d="m9 12 2 2 4-4" />
        </g>
        <g v-else-if="name === 'clock'">
            <circle cx="12" cy="12" r="8" />
            <path d="M12 8v4l3 2" />
        </g>
        <g v-else-if="name === 'sparkle'">
            <path
                d="M12 3v4M12 17v4M4.9 6.9l2.8 2.8M16.3 14.3l2.8 2.8M3 12h4M17 12h4M4.9 17.1l2.8-2.8M16.3 9.7l2.8-2.8"
            />
            <circle cx="12" cy="12" r="2.5" />
        </g>
        <g v-else>
            <path d="M5 15a4 4 0 0 1 4-4h1a4 4 0 0 1 4 4v3H5z" />
            <path d="M14 14h1a4 4 0 0 1 4 4v2" />
            <circle cx="10" cy="7" r="3" />
        </g>
    </svg>
</template>
