<?php

declare(strict_types=1);

namespace App\Mail;

use App\Enums\ReservaEstado;
use App\Events\ReservaEstadoCambiado;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class ReservaEstadoCambiadoMail extends Mailable
{
    use SerializesModels;

    public function __construct(public ReservaEstadoCambiado $evento) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->asunto(),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.reservas.estado-cambiado',
            with: [
                'reservaId' => $this->evento->reserva->id,
                'etiquetaEstado' => $this->etiquetaEstado(),
                'urlReserva' => route('reservas.show', $this->evento->reserva),
            ],
        );
    }

    private function asunto(): string
    {
        return match ($this->evento->estadoNuevo) {
            ReservaEstado::Confirmada => 'Tu reserva fue confirmada',
            ReservaEstado::Entregada => 'Tu vehículo fue entregado',
            ReservaEstado::Facturada => 'Tu factura está lista',
            ReservaEstado::Pagada => 'Tu pago fue registrado',
            default => 'Actualización de tu reserva',
        };
    }

    private function etiquetaEstado(): string
    {
        return match ($this->evento->estadoNuevo) {
            ReservaEstado::Confirmada => 'confirmada',
            ReservaEstado::Entregada => 'entregada',
            ReservaEstado::Facturada => 'facturada',
            ReservaEstado::Pagada => 'pagada',
            default => $this->evento->estadoNuevo->value,
        };
    }
}
