<script setup lang="ts">
import RentGoLogo from '@/components/storefront/RentGoLogo.vue';

const copyrightYear = new Date().getFullYear();
</script>

<template>
    <footer id="contacto" class="bg-brand-navy text-white">
        <div class="mx-auto grid max-w-7xl gap-10 px-6 py-16 md:grid-cols-4">
            <div>
                <RentGoLogo />
                <p class="mt-4 text-sm text-white/70">
                    Renta de vehículos con cobertura, disponibilidad y atención
                    24/7.
                </p>
                <div class="mt-4 flex gap-3 text-sm">
                    <a
                        href="https://instagram.com"
                        class="hover:text-brand-teal"
                        rel="noreferrer"
                        target="_blank"
                        >Instagram</a
                    >
                    <a
                        href="https://facebook.com"
                        class="hover:text-brand-teal"
                        rel="noreferrer"
                        target="_blank"
                        >Facebook</a
                    >
                    <a
                        href="https://x.com"
                        class="hover:text-brand-teal"
                        rel="noreferrer"
                        target="_blank"
                        >X</a
                    >
                </div>
            </div>
            <div>
                <p class="text-sm font-semibold tracking-wide uppercase">
                    Plataforma
                </p>
                <ul class="mt-4 space-y-2 text-sm text-white/75">
                    <li>
                        <a href="#vehiculos" class="hover:text-brand-teal"
                            >Catálogo</a
                        >
                    </li>
                    <li>
                        <a href="#categorias" class="hover:text-brand-teal"
                            >Precios</a
                        >
                    </li>
                </ul>
            </div>
            <div>
                <p class="text-sm font-semibold tracking-wide uppercase">
                    Empresa
                </p>
                <ul class="mt-4 space-y-2 text-sm text-white/75">
                    <li>
                        <a href="#inicio" class="hover:text-brand-teal"
                            >Nosotros</a
                        >
                    </li>
                    <li>
                        <a href="#contacto" class="hover:text-brand-teal"
                            >Sedes</a
                        >
                    </li>
                </ul>
            </div>
            <div>
                <p class="text-sm font-semibold tracking-wide uppercase">
                    Legal
                </p>
                <ul class="mt-4 space-y-2 text-sm text-white/75">
                    <li>
                        <a href="#contacto" class="hover:text-brand-teal"
                            >Términos</a
                        >
                    </li>
                    <li>
                        <a href="#contacto" class="hover:text-brand-teal"
                            >Privacidad</a
                        >
                    </li>
                    <li>
                        <a href="#contacto" class="hover:text-brand-teal"
                            >Pólizas</a
                        >
                    </li>
                </ul>
            </div>
        </div>
        <p
            class="border-t border-white/10 py-6 text-center text-xs text-white/50"
        >
            © {{ copyrightYear }} RENT-GO. Todos los derechos reservados.
        </p>
    </footer>
</template>
