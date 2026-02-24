<?php

declare(strict_types=1);

namespace Veltix\LaravelMontonio\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Veltix\LaravelMontonio\Models\ShippingMethod;
use Veltix\LaravelMontonio\Support\SyncResult;
use Veltix\Montonio\Montonio;
use Veltix\Montonio\Shipping\Dto\Response\ShippingMethodConstraints;
use Veltix\Montonio\Shipping\Dto\Response\ShippingMethodSubtype;

final readonly class SyncShippingMethods
{
    public function __construct(
        private Montonio $montonio,
    ) {}

    public function handle(): SyncResult
    {
        $result = DB::transaction(function (): SyncResult {
            $response = $this->montonio->shipping()->getShippingMethods();

            $created = 0;
            $updated = 0;
            $seenIds = [];

            foreach ($response->countries as $country) {
                foreach ($country->carriers as $carrier) {
                    foreach ($carrier->shippingMethods as $method) {
                        $model = ShippingMethod::updateOrCreate(
                            [
                                'carrier_code' => $carrier->carrierCode,
                                'method_code' => $method->type,
                                'country' => $country->countryCode,
                            ],
                            [
                                'name' => Str::headline($method->type),
                                'metadata' => [
                                    'subtypes' => $this->mapSubtypes($method->subtypes),
                                    'constraints' => $this->mapConstraints($method->constraints),
                                ],
                                'active' => true,
                            ],
                        );

                        $this->trackUpsert($model, $seenIds, $created, $updated);
                    }
                }
            }

            $deactivated = ShippingMethod::whereNotIn('id', $seenIds)
                ->where('active', true)
                ->update(['active' => false]);

            return new SyncResult($created, $updated, $deactivated);
        });

        Cache::forget('montonio:shipping_methods');

        return $result;
    }

    /**
     * @param  list<int|string>  $seenIds
     */
    private function trackUpsert(Model $model, array &$seenIds, int &$created, int &$updated): void
    {
        $seenIds[] = $model->id;
        $model->wasRecentlyCreated ? $created++ : $updated++;
    }

    /**
     * @param  ShippingMethodSubtype[]|null  $subtypes
     * @return array<int, array{code: string, rate: float|null, currency: string|null}>|null
     */
    private function mapSubtypes(?array $subtypes): ?array
    {
        if ($subtypes === null) {
            return null;
        }

        return array_map(fn (ShippingMethodSubtype $subtype): array => [
            'code' => $subtype->code,
            'rate' => $subtype->rate,
            'currency' => $subtype->currency,
        ], $subtypes);
    }

    /**
     * @return array{parcel_dimensions_required: bool}|null
     */
    private function mapConstraints(?ShippingMethodConstraints $constraints): ?array
    {
        if (!$constraints instanceof \Veltix\Montonio\Shipping\Dto\Response\ShippingMethodConstraints) {
            return null;
        }

        return ['parcel_dimensions_required' => $constraints->parcelDimensionsRequired];
    }
}
