import { createInertiaApp } from '@inertiajs/vue3';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    withApp: (app) => {
        app.config.errorHandler = (error) => {
            console.error(error);

            const root = document.getElementById('app');

            if (
                root === null ||
                root.querySelector('[data-error-boundary]') !== null
            ) {
                return;
            }

            root.innerHTML = `
                <div data-error-boundary class="flex min-h-screen items-center justify-center bg-slate-50 px-6 py-16 text-center">
                    <div>
                        <p class="text-sm font-semibold tracking-wide text-teal-600 uppercase">RENT-GO</p>
                        <h1 class="mt-2 text-2xl font-extrabold text-slate-900">No se pudo mostrar esta pantalla</h1>
                        <p class="mt-3 text-slate-600">Vuelve al listado e inténtalo de nuevo. Si el problema continúa, recarga la página.</p>
                    </div>
                </div>
            `;
        };

        app.directive('focus', {
            mounted: (el: HTMLElement, shouldFocus) => {
                if (shouldFocus.value !== false) {
                    el.focus();
                }
            },
        });
    },
    progress: {
        color: '#4B5563',
    },
});
