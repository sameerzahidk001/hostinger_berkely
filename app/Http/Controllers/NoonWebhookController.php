<?php

namespace App\Http\Controllers;

use App\Services\NoonCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NoonWebhookController extends Controller
{
    public function handle(Request $request, NoonCheckoutService $checkout)
    {
        $payload = $request->all();
        $orderId = $checkout->normalizeOrderId((string) (
            data_get($payload, 'orderId')
            ?? data_get($payload, 'order_id')
            ?? data_get($payload, 'event.orderId')
            ?? data_get($payload, 'result.order.id')
            ?? ''
        ));

        if ($orderId === '') {
            $decoded = json_decode($request->getContent(), true);
            if (is_array($decoded)) {
                $orderId = $checkout->normalizeOrderId((string) (
                    data_get($decoded, 'orderId')
                    ?? data_get($decoded, 'order_id')
                    ?? data_get($decoded, 'result.order.id')
                    ?? ''
                ));
            }
        }

        Log::info('Noon webhook received', [
            'order_id' => $orderId,
            'status' => $request->input('orderStatus', $request->input('status')),
        ]);

        if ($orderId === '') {
            return response()->json(['ok' => false, 'error' => 'Missing orderId'], 422);
        }

        try {
            $installment = $checkout->completeOrder($request, $orderId, false);
        } catch (\Throwable $e) {
            Log::error('Noon webhook processing failed: ' . $e->getMessage(), [
                'order_id' => $orderId,
            ]);

            return response()->json(['ok' => false], 500);
        }

        return response()->json([
            'ok' => true,
            'paid' => (bool) $installment,
        ]);
    }
}
