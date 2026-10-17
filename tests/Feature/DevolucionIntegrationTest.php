<?php

use App\Enums\ReservaEstado;
use App\Models\ActaDevolucion;
use App\Models\Reserva;

describe('registro de devolución', function () {
    it('stores the return record and advances the reservation state', function () {
        $reservation = Reserva::factory()->create([
            'estado' => ReservaEstado::Entregada,
        ]);

        $this->post(route('devoluciones.store', $reservation), [
            'devuelto_at' => '2026-10-16 10:00:00',
            'checklist' => ['documentos' => true, 'llantas' => true],
            'observaciones' => 'Sin novedades',
        ])->assertRedirectToRoute('reservas.show', $reservation);

        $record = ActaDevolucion::query()->where('reserva_id', $reservation->id)->sole();

        expect($record->observaciones)->toBe('Sin novedades')
            ->and($record->checklist)->toBe(['documentos' => true, 'llantas' => true])
            ->and($record->devuelto_at->toDateTimeString())->toBe('2026-10-16 10:00:00')
            ->and($reservation->fresh()->estado)->toBe(ReservaEstado::Devuelta);
    });

    it('rejects a second return for the same reservation without failing', function () {
        $reservation = Reserva::factory()->create([
            'estado' => ReservaEstado::Devuelta,
        ]);
        ActaDevolucion::factory()->create([
            'reserva_id' => $reservation->id,
            'observaciones' => 'Original',
        ]);

        $this->from(route('devoluciones.create', $reservation))
            ->post(route('devoluciones.store', $reservation), [
                'devuelto_at' => '2026-10-16 11:00:00',
                'observaciones' => 'Duplicada',
            ])
            ->assertRedirectToRoute('devoluciones.create', $reservation)
            ->assertSessionHasErrors(['reserva' => 'La reserva ya tiene una devolución registrada.']);

        expect(ActaDevolucion::query()->where('reserva_id', $reservation->id)->count())->toBe(1)
            ->and(ActaDevolucion::query()->where('reserva_id', $reservation->id)->value('observaciones'))->toBe('Original');
    });

    it('does not persist anything when the input is invalid', function () {
        $reservation = Reserva::factory()->create([
            'estado' => ReservaEstado::Entregada,
        ]);

        $this->post(route('devoluciones.store', $reservation), [
            'observaciones' => 'Sin fecha',
        ])->assertSessionHasErrors('devuelto_at');

        expect(ActaDevolucion::query()->where('reserva_id', $reservation->id)->exists())->toBeFalse()
            ->and($reservation->fresh()->estado)->toBe(ReservaEstado::Entregada);
    });
});
