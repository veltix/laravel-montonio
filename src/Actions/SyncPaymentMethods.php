<?php

declare(strict_types=1);

namespace Veltix\LaravelMontonio\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Veltix\LaravelMontonio\Models\PaymentMethod;
use Veltix\LaravelMontonio\Support\SyncResult;
use Veltix\Montonio\Montonio;
use Veltix\Montonio\Payments\Dto\Response\BankPaymentMethod;

final readonly class SyncPaymentMethods
{
    public function __construct(
        private Montonio $montonio,
    ) {}

    public function handle(): SyncResult
    {
        $result = DB::transaction(function (): SyncResult {
            $response = $this->montonio->payments()->getPaymentMethods();

            $created = 0;
            $updated = 0;
            $seenIds = [];

            foreach ($response->paymentMethods as $methodCode => $detail) {
                if ($detail->setup === null) {
                    $model = PaymentMethod::updateOrCreate(
                        [
                            'method_code' => $methodCode,
                            'country' => null,
                        ],
                        [
                            'name' => Str::headline($methodCode),
                            'currency' => null,
                            'metadata' => [
                                'processor' => $detail->processor,
                                'logo_url' => $detail->logoUrl,
                            ],
                            'active' => true,
                        ],
                    );

                    $this->trackUpsert($model, $seenIds, $created, $updated);

                    continue;
                }

                foreach ($detail->setup as $countryCode => $countryMethods) {
                    $banks = array_map(fn (BankPaymentMethod $bank): array => [
                        'code' => $bank->code,
                        'name' => $bank->name,
                        'logo_url' => $bank->logoUrl,
                        'supported_currencies' => $bank->supportedCurrencies,
                        'ui_position' => $bank->uiPosition,
                    ], $countryMethods->paymentMethods);

                    $model = PaymentMethod::updateOrCreate(
                        [
                            'method_code' => $methodCode,
                            'country' => $countryCode,
                        ],
                        [
                            'name' => Str::headline($methodCode),
                            'currency' => $countryMethods->supportedCurrencies[0] ?? null,
                            'metadata' => [
                                'processor' => $detail->processor,
                                'logo_url' => $detail->logoUrl,
                                'supported_currencies' => $countryMethods->supportedCurrencies,
                                'banks' => $banks,
                            ],
                            'active' => true,
                        ],
                    );

                    $this->trackUpsert($model, $seenIds, $created, $updated);
                }
            }

            $deactivated = PaymentMethod::whereNotIn('id', $seenIds)
                ->where('active', true)
                ->update(['active' => false]);

            return new SyncResult($created, $updated, $deactivated);
        });

        Cache::forget('montonio:payment_methods');

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
}
