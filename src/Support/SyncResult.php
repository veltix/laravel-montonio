<?php

declare(strict_types=1);

namespace Veltix\LaravelMontonio\Support;

final readonly class SyncResult
{
    public function __construct(
        public int $created,
        public int $updated,
        public int $deactivated,
    ) {}
}
