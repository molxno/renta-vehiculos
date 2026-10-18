export const heroImage =
    'https://images.unsplash.com/photo-1617814086906-d847a8bc6fca?w=1600&h=900&fit=crop&auto=format';

export const loginHeroImage =
    'https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=1600&h=1200&fit=crop&auto=format';

export const policyImage =
    'https://images.unsplash.com/photo-1606590526453-9b8ee4569a70?w=800&h=600&fit=crop&auto=format';

export const mockFleet = [
    {
        name: 'BMW 320i',
        category: 'Sedán',
        price: '$120 / día',
        image: 'https://images.unsplash.com/photo-1645400379459-f6fd3d963fd4?w=800&h=500&fit=crop&auto=format',
        passengers: '5',
        transmission: 'Automática',
        fuel: 'Gasolina',
    },
    {
        name: 'Mercedes GLC 300',
        category: 'SUV',
        price: '$120 / día',
        image: 'https://images.unsplash.com/photo-1568723331958-ce8c291349e7?w=800&h=500&fit=crop&auto=format',
        passengers: '5',
        transmission: 'Automática',
        fuel: 'Híbrido',
    },
    {
        name: 'Toyota Corolla',
        category: 'Sedán',
        price: '$120 / día',
        image: 'https://images.unsplash.com/photo-1550242499-b5171f56de56?w=800&h=500&fit=crop&auto=format',
        passengers: '5',
        transmission: 'Automática',
        fuel: 'Gasolina',
    },
] as const;

export const mockCategories = [
    {
        name: 'Sedán',
        description: 'Ciudad y viajes',
        icon: 'sedan',
    },
    {
        name: 'SUV',
        description: 'Familia y carretera',
        icon: 'suv',
    },
    {
        name: 'Deportivo',
        description: 'Alto desempeño',
        icon: 'sport',
    },
    {
        name: 'Camioneta',
        description: 'Carga y espacio',
        icon: 'truck',
    },
    {
        name: 'Eléctrico',
        description: 'Cero emisiones',
        icon: 'electric',
    },
    {
        name: 'Lujo',
        description: 'Experiencia premium',
        icon: 'luxury',
    },
] as const;

export const mockQualities = [
    {
        title: 'Seguro completo',
        description:
            'Cada reserva incluye cobertura básica y opciones de ampliación.',
        icon: 'shield',
    },
    {
        title: 'Disponibilidad 24/7',
        description:
            'Consulta, reserva y gestiona tu vehículo a cualquier hora.',
        icon: 'clock',
    },
    {
        title: 'Flota premium',
        description:
            'Modelos recientes, inspeccionados y listos para entregar.',
        icon: 'sparkle',
    },
    {
        title: 'Asistencia 24/7',
        description: 'Acompañamiento en carretera durante todo el alquiler.',
        icon: 'headset',
    },
] as const;

export const mockSteps = [
    {
        number: '01',
        title: 'Elige vehículo',
        description: 'Explora la flota y compara categorías.',
    },
    {
        number: '02',
        title: 'Selecciona fechas',
        description: 'Define recogida, devolución y sede.',
    },
    {
        number: '03',
        title: 'Confirma reserva',
        description: 'Revisa el resumen y confirma en minutos.',
    },
    {
        number: '04',
        title: '¡Disfruta!',
        description: 'Recoge el vehículo y comienza el viaje.',
    },
] as const;

export const mockPolicies = [
    {
        id: 'civil',
        title: 'Responsabilidad civil',
        description: 'Cubre daños a terceros durante el periodo de alquiler.',
    },
    {
        id: 'danos',
        title: 'Daños al vehículo',
        description:
            'Protección frente a colisiones, con deducible según plan.',
    },
    {
        id: 'asistencia',
        title: 'Asistencia en vía',
        description: 'Grúa, batería y soporte telefónico las 24 horas.',
    },
] as const;

export const mockTestimonials = [
    {
        name: 'Laura Gómez',
        role: 'Medellín',
        quote: 'Reservé en minutos y el SUV estaba impecable. El proceso fue claro de punta a punta.',
    },
    {
        name: 'Carlos Pérez',
        role: 'Bogotá',
        quote: 'La entrega puntual y el seguro incluido me dieron tranquilidad para un viaje de trabajo.',
    },
    {
        name: 'Ana Ruiz',
        role: 'Cali',
        quote: 'Volvería a rentar. El catálogo es amplio y el equipo resolvió cada duda al instante.',
    },
] as const;

export const mockFaqs = [
    {
        question: '¿Qué documentos necesito para rentar?',
        answer: 'Cédula vigente, licencia de conducción activa y un medio de pago.',
    },
    {
        question: '¿Puedo modificar o cancelar una reserva?',
        answer: 'Sí. Desde tu cuenta puedes ajustar fechas o cancelar según la política de la reserva.',
    },
    {
        question: '¿El seguro está incluido?',
        answer: 'Cada vehículo incluye una póliza básica. Puedes ampliar la cobertura al confirmar.',
    },
    {
        question: '¿Hay límite de kilometraje?',
        answer: 'El plan estándar incluye un cupo diario. Los kilómetros extra se liquidan al devolver.',
    },
] as const;
