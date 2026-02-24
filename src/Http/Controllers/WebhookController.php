<?php

declare(strict_types=1);

namespace Veltix\LaravelMontonio\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;
use Veltix\LaravelMontonio\Jobs\ProcessMontonioWebhook;
use Veltix\LaravelMontonio\Support\WebhookDispatcher;
use Veltix\Montonio\Montonio;

final class WebhookController
{
    public function __invoke(Request $request, Montonio $montonio, WebhookDispatcher $dispatcher): JsonResponse
    {
        /** @var string|null $orderToken */
        $orderToken = $request->input('orderToken');

        /** @var string|null $payload */
        $payload = $request->input('payload');

        if ($orderToken !== null) {
            return $this->handlePayment($orderToken, $montonio, $dispatcher);
        }

        if ($payload !== null) {
            return $this->handleShipping($payload, $montonio, $dispatcher);
        }

        return response()->json(['error' => 'Invalid webhook payload'], 422);
    }

    private function handlePayment(string $token, Montonio $montonio, WebhookDispatcher $dispatcher): JsonResponse
    {
        /** @var string|null $queue */
        $queue = config('montonio.webhooks.queue');

        if ($queue !== null) {
            ProcessMontonioWebhook::dispatch('payment', $token)->onQueue($queue);

            return response()->json(['status' => 'queued']);
        }

        try {
            $payload = $montonio->webhooks()->verifyPaymentWebhook($token);
        } catch (Throwable) {
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $dispatcher->dispatchPayment($payload);

        return response()->json(['status' => 'ok']);
    }

    private function handleShipping(string $token, Montonio $montonio, WebhookDispatcher $dispatcher): JsonResponse
    {
        /** @var string|null $queue */
        $queue = config('montonio.webhooks.queue');

        if ($queue !== null) {
            ProcessMontonioWebhook::dispatch('shipping', $token)->onQueue($queue);

            return response()->json(['status' => 'queued']);
        }

        try {
            $payload = $montonio->webhooks()->verifyShippingWebhook($token);
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (Throwable) {
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $dispatcher->dispatchShipping($payload);

        return response()->json(['status' => 'ok']);
    }
}
