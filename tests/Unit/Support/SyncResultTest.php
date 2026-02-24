<?php

declare(strict_types=1);

use Veltix\LaravelMontonio\Support\SyncResult;

it('stores created, updated, and deactivated counts', function (): void {
    $result = new SyncResult(created: 3, updated: 5, deactivated: 2);

    expect($result->created)->toBe(3)
        ->and($result->updated)->toBe(5)
        ->and($result->deactivated)->toBe(2);
});

it('accepts zero values', function (): void {
    $result = new SyncResult(created: 0, updated: 0, deactivated: 0);

    expect($result->created)->toBe(0)
        ->and($result->updated)->toBe(0)
        ->and($result->deactivated)->toBe(0);
});

it('is readonly', function (): void {
    $result = new SyncResult(created: 1, updated: 2, deactivated: 3);

    $result->created = 99;
})->throws(Error::class);
