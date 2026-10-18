import type { CatalogVehicle } from '@/types';

export const pickupLocations = [
    'Bogotá — Aeropuerto El Dorado',
    'Bogotá — Zona T',
    'Medellín — Aeropuerto José María Córdova',
    'Cali — Aeropuerto Alfonso Bonilla Aragón',
] as const;

export function isPickupLocation(value: string): boolean {
    return (pickupLocations as readonly string[]).includes(value);
}

export function sedeFromQuery(url: string): string {
    const query = url.includes('?') ? url.slice(url.indexOf('?') + 1) : '';
    const location = new URLSearchParams(query).get('location') ?? '';

    return isPickupLocation(location) ? location : pickupLocations[0];
}

const fallbackImage =
    'https://images.unsplash.com/photo-1617814086906-d847a8bc6fca?w=1200&h=800&fit=crop&auto=format';

const galleryByCategory: Record<string, string[]> = {
    sedan: [
        'https://images.unsplash.com/photo-1550242499-b5171f56de56?w=1200&h=800&fit=crop&auto=format',
        'https://images.unsplash.com/photo-1645400379459-f6fd3d963fd4?w=1200&h=800&fit=crop&auto=format',
        'https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=1200&h=800&fit=crop&auto=format',
        'https://images.unsplash.com/photo-1617531653332-bd46c24f2068?w=1200&h=800&fit=crop&auto=format',
    ],
    camioneta: [
        'https://images.unsplash.com/photo-1606590526453-9b8ee4569a70?w=1200&h=800&fit=crop&auto=format',
        'https://images.unsplash.com/photo-1617814086906-d847a8bc6fca?w=1200&h=800&fit=crop&auto=format',
        'https://images.unsplash.com/photo-1568723331958-ce8c291349e7?w=1200&h=800&fit=crop&auto=format',
        'https://images.unsplash.com/photo-1519641471654-76ce0107ad1b?w=1200&h=800&fit=crop&auto=format',
    ],
    compacto: [
        'https://images.unsplash.com/photo-1672024110512-f7028b49db28?w=1200&h=800&fit=crop&auto=format',
        'https://images.unsplash.com/photo-1680542993889-078ce9c8538a?w=1200&h=800&fit=crop&auto=format',
        'https://images.unsplash.com/photo-1550355291-bbee04a92027?w=1200&h=800&fit=crop&auto=format',
        'https://images.unsplash.com/photo-1541899481282-d53bffe3c35d?w=1200&h=800&fit=crop&auto=format',
    ],
};

const specPresetByCategory: Record<
    string,
    {
        color: string;
        passengers: string;
        doors: string;
        transmission: string;
        fuel: string;
        consumption: string;
        license: string;
    }
> = {
    sedan: {
        color: 'Blanco perlado',
        passengers: '5',
        doors: '4',
        transmission: 'Automática',
        fuel: 'Gasolina',
        consumption: '14 km/l',
        license: 'B',
    },
    camioneta: {
        color: 'Gris grafito',
        passengers: '7',
        doors: '5',
        transmission: 'Automática',
        fuel: 'Gasolina',
        consumption: '10 km/l',
        license: 'B',
    },
    compacto: {
        color: 'Rojo',
        passengers: '4',
        doors: '5',
        transmission: 'Manual',
        fuel: 'Gasolina',
        consumption: '16 km/l',
        license: 'B',
    },
};

export const statusLabels: Record<CatalogVehicle['status'], string> = {
    disponible: 'Disponible',
    alquilado: 'Alquilado',
    contingencia: 'Contingencia',
    mantenimiento: 'Mantenimiento',
};

export function categoryLabel(category: string): string {
    const labels: Record<string, string> = {
        sedan: 'Sedán',
        camioneta: 'Camioneta',
        compacto: 'Compacto',
    };

    return labels[category] ?? category;
}

export function formatMoney(centavos: number, currency = 'COP'): string {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(centavos / 100);
}

export function formatDailyRate(centavos: number, currency = 'COP'): string {
    if (centavos <= 0) {
        return 'Consultar';
    }

    return `${formatMoney(centavos, currency)}/día`;
}

export function vehicleGallery(vehicle: CatalogVehicle): string[] {
    return galleryByCategory[vehicle.category] ?? [fallbackImage];
}

export function vehicleDescription(vehicle: CatalogVehicle): string {
    return `Alquila el ${vehicle.brand} ${vehicle.model} ${vehicle.year}. Un ${categoryLabel(vehicle.category).toLowerCase()} listo para entrega en sede, con seguro básico incluido en la tarifa diaria.`;
}

export function vehicleSpecifications(
    vehicle: CatalogVehicle,
): { label: string; value: string }[] {
    const preset =
        specPresetByCategory[vehicle.category] ?? specPresetByCategory.sedan;
    const kilometers = Math.max(
        3500,
        (2026 - vehicle.year) * 8500 + (vehicle.id % 17) * 320,
    );

    return [
        { label: 'Año', value: String(vehicle.year) },
        { label: 'Color', value: preset.color },
        { label: 'Pasajeros', value: preset.passengers },
        { label: 'Puertas', value: preset.doors },
        { label: 'Transmisión', value: preset.transmission },
        { label: 'Combustible', value: preset.fuel },
        { label: 'Consumo', value: preset.consumption },
        {
            label: 'Kilometraje',
            value: `${kilometers.toLocaleString('es-CO')} km`,
        },
        { label: 'Categoría de conducción', value: preset.license },
    ];
}

export function quotedDays(
    subtotalCents: number,
    dailyRateCents: number,
): number {
    if (dailyRateCents <= 0) {
        return 0;
    }

    return Math.max(1, Math.round(subtotalCents / dailyRateCents));
}
