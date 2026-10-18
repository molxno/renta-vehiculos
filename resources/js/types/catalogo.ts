export type CatalogVehicle = {
    id: number;
    label: string;
    brand: string;
    model: string;
    year: number;
    category: string;
    status: 'disponible' | 'alquilado' | 'contingencia' | 'mantenimiento';
    dailyRateCents: number;
};

export type CatalogFilters = {
    category: string;
    startDate: string;
    endDate: string;
    location: string;
};
