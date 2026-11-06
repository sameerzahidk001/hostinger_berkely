<?php

namespace App\Http\Controllers;

use App\Services\StripeCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function handle(Request $request, StripeCheckoutService $stripe)
    {
        $payload = $request->getContent();
        $sig = (string) $request->header('Stripe-Signature', '');

        try {
            $event = $stripe->constructWebhookEvent($payload, $sig);
        } catch (\Throwable $e) {
            Log::warning('Stripe webhook signature failed: ' . $e->getMessage());

            return response()->json(['ok' => false], 400);
        }

        $type = is_object($event) ? (string) ($event->type ?? '') : (string) ($event['type'] ?? '');
        if (! in_array($type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            return response()->json(['ok' => true]);
        }

        $sessionId = is_object($event)
            ? (string) data_get($event, 'data.object.id', '')
            : (string) data_get($event, 'data.object.id', '');

        if ($sessionId === '') {
            return response()->json(['ok' => true]);
        }

        try {
            $stripe->fulfillSession($sessionId, $request);
        } catch (\Throwable $e) {
            Log::error('Stripe webhook fulfill failed: ' . $e->getMessage(), ['session' => $sessionId]);

            return response()->json(['ok' => false], 500);
        }

        return response()->json(['ok' => true]);
    }
}
