<?php

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('tenant con status active retorna true en isActive', function () {
    $tenant = Tenant::factory()->active()->create();

    expect($tenant->isActive())->toBeTrue();
    expect($tenant->canAccess())->toBeTrue();
});

test('tenant con status trial y trial_ends_at en el futuro retorna true en isActive', function () {
    $tenant = Tenant::factory()->trial(now()->addDays(30))->create();

    expect($tenant->isActive())->toBeTrue();
    expect($tenant->canAccess())->toBeTrue();
});

test('tenant con status trial y trial_ends_at hoy retorna true en isActive', function () {
    $tenant = Tenant::factory()->trial(now()->addHours(2))->create();

    expect($tenant->isActive())->toBeTrue();
    expect($tenant->canAccess())->toBeTrue();
});

test('tenant con status trial y trial_ends_at en el pasado retorna false en isActive', function () {
    $tenant = Tenant::factory()->trialExpired()->create();

    expect($tenant->isActive())->toBeFalse();
    expect($tenant->canAccess())->toBeFalse();
});

test('tenant con status trial sin trial_ends_at retorna false en isActive', function () {
    $tenant = Tenant::factory()->trialWithoutEnd()->create();

    expect($tenant->isActive())->toBeFalse();
    expect($tenant->canAccess())->toBeFalse();
});

test('tenant con status suspended retorna false en isActive', function () {
    $tenant = Tenant::factory()->suspended()->create();

    expect($tenant->isActive())->toBeFalse();
    expect($tenant->canAccess())->toBeFalse();
});

test('tenant con status expired retorna false en isActive', function () {
    $tenant = Tenant::factory()->expired()->create();

    expect($tenant->isActive())->toBeFalse();
    expect($tenant->canAccess())->toBeFalse();
});

test('tenant con status archived retorna false en isActive', function () {
    $tenant = Tenant::factory()->archived()->create();

    expect($tenant->isActive())->toBeFalse();
    expect($tenant->canAccess())->toBeFalse();
});

test('tenant con status inactive retorna false en isActive', function () {
    $tenant = Tenant::factory()->inactive()->create();

    expect($tenant->isActive())->toBeFalse();
    expect($tenant->canAccess())->toBeFalse();
});

test('canAccess es alias de isActive y retornan mismos resultados', function () {
    $statuses = [
        Tenant::STATUS_ACTIVE,
        Tenant::STATUS_TRIAL,
        Tenant::STATUS_SUSPENDED,
        Tenant::STATUS_EXPIRED,
        Tenant::STATUS_ARCHIVED,
        Tenant::STATUS_INACTIVE,
    ];

    foreach ($statuses as $status) {
        $tenant = match ($status) {
            Tenant::STATUS_ACTIVE => Tenant::factory()->active()->create(),
            Tenant::STATUS_TRIAL => Tenant::factory()->trial(now()->addDays(30))->create(),
            Tenant::STATUS_SUSPENDED => Tenant::factory()->suspended()->create(),
            Tenant::STATUS_EXPIRED => Tenant::factory()->expired()->create(),
            Tenant::STATUS_ARCHIVED => Tenant::factory()->archived()->create(),
            Tenant::STATUS_INACTIVE => Tenant::factory()->inactive()->create(),
        };

        expect($tenant->isActive())
            ->toBe($tenant->canAccess())
            ->toBe($status === Tenant::STATUS_ACTIVE ||
                  ($status === Tenant::STATUS_TRIAL && $tenant->trial_ends_at && $tenant->trial_ends_at->isFuture()));
    }
});

test('lógica completa de isActive con diferentes escenarios de tiempo', function () {
    // Escenario 1: Trial que termina exactamente ahora (debe ser true)
    $tenant1 = Tenant::factory()->trial(now()->addMinute())->create();
    expect($tenant1->isActive())->toBeTrue();

    // Escenario 2: Trial que terminó hace 1 segundo (debe ser false)
    $tenant2 = Tenant::factory()->trial(now()->subSecond())->create();
    expect($tenant2->isActive())->toBeFalse();

    // Escenario 3: Trial que termina en 1 hora (debe ser true)
    $tenant3 = Tenant::factory()->trial(now()->addHour())->create();
    expect($tenant3->isActive())->toBeTrue();

    // Escenario 4: Trial sin fecha de fin (debe ser false)
    $tenant4 = Tenant::factory()->trialWithoutEnd()->create();
    expect($tenant4->isActive())->toBeFalse();
});

test('validación de constantes de status', function () {
    $expectedStatuses = [
        'active',
        'trial',
        'suspended',
        'expired',
        'archived',
        'inactive',
    ];

    $actualStatuses = [
        Tenant::STATUS_ACTIVE,
        Tenant::STATUS_TRIAL,
        Tenant::STATUS_SUSPENDED,
        Tenant::STATUS_EXPIRED,
        Tenant::STATUS_ARCHIVED,
        Tenant::STATUS_INACTIVE,
    ];

    expect($actualStatuses)->toBe($expectedStatuses);
});

test('isActive maneja correctamente valores nulos en trial_ends_at', function () {
    $tenant = Tenant::factory()->create([
        'status' => Tenant::STATUS_TRIAL,
        'trial_ends_at' => null,
    ]);

    expect($tenant->isActive())->toBeFalse();
    expect($tenant->canAccess())->toBeFalse();
});

test('demostración del comportamiento de isFuture con diferentes momentos', function () {
    $now = now();

    // Crear un tenant con trial que termina en el futuro
    $futureTenant = Tenant::factory()->create([
        'status' => Tenant::STATUS_TRIAL,
        'trial_ends_at' => $now->copy()->addDays(1),
    ]);

    // Crear un tenant con trial que terminó en el pasado
    $pastTenant = Tenant::factory()->create([
        'status' => Tenant::STATUS_TRIAL,
        'trial_ends_at' => $now->copy()->subDays(1),
    ]);

    // Validar comportamiento
    expect($futureTenant->trial_ends_at->isFuture())->toBeTrue();
    expect($futureTenant->isActive())->toBeTrue();
    expect($futureTenant->canAccess())->toBeTrue();

    expect($pastTenant->trial_ends_at->isFuture())->toBeFalse();
    expect($pastTenant->isActive())->toBeFalse();
    expect($pastTenant->canAccess())->toBeFalse();
});
