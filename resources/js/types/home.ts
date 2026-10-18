export type FeaturedVehicle = {
    id: number;
    label: string;
    category: string;
    dailyRateCents: number;
};

export type HomeSearch = {
    location: string;
    startDate: string;
    endDate: string;
};
