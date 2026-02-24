<?php

declare(strict_types=1);

namespace Veltix\LaravelMontonio\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class PaymentMethod extends Model
{
    protected $table = 'montonio_payment_methods';

    protected $guarded = [];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'active' => 'boolean',
        ];
    }
}
