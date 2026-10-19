import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export const APP_TIMEZONE = 'America/Bogota';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function formatFechaHora(value: string | null): string {
    if (value === null || value === '') {
        return 'Sin registrar';
    }

    return new Intl.DateTimeFormat('es-CO', {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone: APP_TIMEZONE,
    }).format(new Date(value));
}

export function fechaHoraLocal(fecha: Date = new Date()): string {
    const offset = fecha.getTimezoneOffset() * 60_000;

    return new Date(fecha.getTime() - offset).toISOString().slice(0, 16);
}
