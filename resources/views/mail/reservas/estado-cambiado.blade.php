<x-mail::message>
# Actualización de tu reserva

Tu reserva **#{{ $reservaId }}** quedó **{{ $etiquetaEstado }}**.

<x-mail::button :url="$urlReserva">
Ver reserva
</x-mail::button>

Gracias por confiar en {{ config('app.name') }}.
</x-mail::message>
