export type UserRole =
    | 'cliente'
    | 'coordinador'
    | 'cajero'
    | 'administrador'
    | 'gerente';

export type User = {
    id: number;
    name: string;
    email?: string;
    role: UserRole;
    avatar?: string;
    email_verified_at?: string | null;
    created_at?: string;
    updated_at?: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User | null;
};

export type NavigationItem = {
    label: string;
    href: string;
};
