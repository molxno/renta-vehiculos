<?php

declare(strict_types=1);

use App\Models\Trm;
use App\Models\User;
use Illuminate\Support\Carbon;

function inertiaHeadersForTrm(): array
{
    return [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json')),
        'X-Requested-With' => 'XMLHttpRequest',
    ];
}

describe('administración de la TRM', function () {
    it('renders the current exchange rate for an administrator', function () {
        $this->travelTo(Carbon::parse('2026-11-01 10:00:00'));
        $administrador = User::factory()->administrador()->create();
        Trm::factory()->create([
            'fecha' => '2026-10-31',
            'valor' => '4200.0000',
        ]);

        $this->actingAs($administrador)
            ->withHeaders(inertiaHeadersForTrm())
            ->get(route('admin.trm.edit'))
            ->assertOk()
            ->assertJsonPath('component', 'Administracion/Trm/Edit')
            ->assertJsonPath('props.currentTrm.fecha', '2026-10-31')
            ->assertJsonPath('props.currentTrm.valor', '4200.0000');
    });

    it('renders the exchange rate form when no rate has been saved', function () {
        $administrador = User::factory()->administrador()->create();

        $this->actingAs($administrador)
            ->withHeaders(inertiaHeadersForTrm())
            ->get(route('admin.trm.edit'))
            ->assertOk()
            ->assertJsonPath('component', 'Administracion/Trm/Edit')
            ->assertJsonPath('props.currentTrm', null);
    });

    it('forbids a customer from viewing the exchange rate form', function () {
        $cliente = User::factory()->cliente()->create();

        $this->actingAs($cliente)
            ->get(route('admin.trm.edit'))
            ->assertForbidden();
    });

    it('redirects a guest to sign in before viewing the exchange rate form', function () {
        $this->get(route('admin.trm.edit'))
            ->assertRedirectToRoute('login');
    });

    it('persists the administrator rate for its effective date', function () {
        $this->travelTo(Carbon::parse('2026-11-01 10:00:00'));
        $administrador = User::factory()->administrador()->create();

        $this->actingAs($administrador)
            ->put(route('admin.trm.update'), [
                'fecha' => '2026-11-01',
                'valor' => '4300.2500',
            ])
            ->assertRedirectToRoute('admin.trm.edit');

        expect(Trm::query()->whereDate('fecha', '2026-11-01')->firstOrFail()->valor)
            ->toBe('4300.2500');
    });

    it('updates the existing rate instead of duplicating its effective date', function () {
        $this->travelTo(Carbon::parse('2026-11-01 10:00:00'));
        $administrador = User::factory()->administrador()->create();
        Trm::factory()->create([
            'fecha' => '2026-11-01',
            'valor' => '4200.0000',
        ]);

        $this->actingAs($administrador)
            ->put(route('admin.trm.update'), [
                'fecha' => '2026-11-01',
                'valor' => '4300.2500',
            ])
            ->assertRedirectToRoute('admin.trm.edit');

        expect(Trm::query()->whereDate('fecha', '2026-11-01')->count())->toBe(1);
        expect(Trm::query()->whereDate('fecha', '2026-11-01')->firstOrFail()->valor)
            ->toBe('4300.2500');
    });

    it('rejects invalid and future exchange rates without persisting them', function (array $data, string $field, string $message) {
        $this->travelTo(Carbon::parse('2026-11-01 10:00:00'));
        $administrador = User::factory()->administrador()->create();

        $this->actingAs($administrador)
            ->put(route('admin.trm.update'), $data)
            ->assertSessionHasErrors([$field => $message]);

        expect(Trm::query()->count())->toBe(0);
    })->with([
        'zero rate' => [
            ['fecha' => '2026-11-01', 'valor' => '0'],
            'valor',
            'El valor de la TRM debe ser mayor que cero.',
        ],
        'too many decimal places' => [
            ['fecha' => '2026-11-01', 'valor' => '4200.12345'],
            'valor',
            'La TRM admite hasta cuatro decimales.',
        ],
        'rate exceeds decimal column precision' => [
            ['fecha' => '2026-11-01', 'valor' => '100000000'],
            'valor',
            'El valor de la TRM excede el límite permitido.',
        ],
        'future date' => [
            ['fecha' => '2026-11-02', 'valor' => '4200.0000'],
            'fecha',
            'La fecha de vigencia no puede ser futura.',
        ],
    ]);

    it('does not permit a non-administrator to change the exchange rate', function () {
        $cliente = User::factory()->cliente()->create();

        $this->actingAs($cliente)
            ->put(route('admin.trm.update'), [
                'fecha' => now()->toDateString(),
                'valor' => '4300.2500',
            ])
            ->assertForbidden();

        expect(Trm::query()->count())->toBe(0);
    });
});
