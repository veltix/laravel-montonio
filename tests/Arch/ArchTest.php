<?php

declare(strict_types=1);

// ────────────────────────────────────────────────────
// Section 1 — Strict Types
// ────────────────────────────────────────────────────

arch('all source files use strict types')
    ->expect('Veltix\LaravelMontonio')
    ->toUseStrictTypes();

// ────────────────────────────────────────────────────
// Section 2 — Immutability
// ────────────────────────────────────────────────────

arch('SyncResult is final and readonly')
    ->expect(\Veltix\LaravelMontonio\Support\SyncResult::class)
    ->toBeFinal()
    ->toBeReadonly();

// ────────────────────────────────────────────────────
// Section 3 — Service Provider
// ────────────────────────────────────────────────────

arch('service provider extends base ServiceProvider')
    ->expect(\Veltix\LaravelMontonio\MontonioServiceProvider::class)
    ->toExtend(\Illuminate\Support\ServiceProvider::class);

// ────────────────────────────────────────────────────
// Section 4 — Facade
// ────────────────────────────────────────────────────

arch('facade extends base Facade')
    ->expect(\Veltix\LaravelMontonio\Facades\Montonio::class)
    ->toExtend(\Illuminate\Support\Facades\Facade::class);

// ────────────────────────────────────────────────────
// Section 5 — Models
// ────────────────────────────────────────────────────

arch('models extend Eloquent Model')
    ->expect('Veltix\LaravelMontonio\Models')
    ->toExtend(\Illuminate\Database\Eloquent\Model::class);

// ────────────────────────────────────────────────────
// Section 6 — Events
// ────────────────────────────────────────────────────

arch('events use Dispatchable trait')
    ->expect('Veltix\LaravelMontonio\Events')
    ->toUseTrait(\Illuminate\Foundation\Events\Dispatchable::class);

arch('events use SerializesModels trait')
    ->expect('Veltix\LaravelMontonio\Events')
    ->toUseTrait(\Illuminate\Queue\SerializesModels::class);

// ────────────────────────────────────────────────────
// Section 7 — Jobs
// ────────────────────────────────────────────────────

arch('jobs implement ShouldQueue')
    ->expect('Veltix\LaravelMontonio\Jobs')
    ->toImplement(\Illuminate\Contracts\Queue\ShouldQueue::class);

// ────────────────────────────────────────────────────
// Section 8 — Console Commands
// ────────────────────────────────────────────────────

arch('commands extend base Command')
    ->expect('Veltix\LaravelMontonio\Console')
    ->toExtend(\Illuminate\Console\Command::class);

arch('commands have Command suffix')
    ->expect('Veltix\LaravelMontonio\Console')
    ->toHaveSuffix('Command');

// ────────────────────────────────────────────────────
// Section 9 — Actions
// ────────────────────────────────────────────────────

arch('actions are final')
    ->expect('Veltix\LaravelMontonio\Actions')
    ->toBeFinal();

arch('actions have a handle method')
    ->expect('Veltix\LaravelMontonio\Actions')
    ->toHaveMethod('handle');

arch('actions do not depend on Http controllers')
    ->expect('Veltix\LaravelMontonio\Actions')
    ->not->toUse('Veltix\LaravelMontonio\Http');

// ────────────────────────────────────────────────────
// Section 10 — Services
// ────────────────────────────────────────────────────

arch('services are final')
    ->expect('Veltix\LaravelMontonio\Services')
    ->toBeFinal();

arch('services have Service suffix')
    ->expect('Veltix\LaravelMontonio\Services')
    ->toHaveSuffix('Service');

arch('services do not depend on Http controllers')
    ->expect('Veltix\LaravelMontonio\Services')
    ->not->toUse('Veltix\LaravelMontonio\Http');

// ────────────────────────────────────────────────────
// Section 11 — Dependency Rules
// ────────────────────────────────────────────────────

arch('models do not depend on Http controllers')
    ->expect('Veltix\LaravelMontonio\Models')
    ->not->toUse('Veltix\LaravelMontonio\Http');

arch('events do not depend on Http controllers')
    ->expect('Veltix\LaravelMontonio\Events')
    ->not->toUse('Veltix\LaravelMontonio\Http');

arch('support classes do not depend on Http controllers')
    ->expect('Veltix\LaravelMontonio\Support')
    ->not->toUse('Veltix\LaravelMontonio\Http');

// ────────────────────────────────────────────────────
// Section 12 — No Debugging Code
// ────────────────────────────────────────────────────

arch('no debugging functions are used')
    ->expect(['dd', 'dump', 'var_dump', 'print_r', 'die', 'exit', 'ray'])
    ->not->toBeUsed();
