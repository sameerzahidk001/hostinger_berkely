<?php

namespace App\Services;

use App\Models\Installment;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeCheckoutService
{
    public function ensureGateway(): PaymentGateway
    {
        PaymentGateway::query()->firstOrCreate(
            ['slug' => 'stripe'],
            [
                'name' => 'Stripe (USD)',
                'status' => 'test',
                'active' => false,
                'test_keys' => ['client_id' => '', 'secret_key' => '', 'webhook_secret' => ''],
                'production_keys' => ['client_id' => '', 'secret_key' => '', 'webhook_secret' => ''],
            ]
        );

        return PaymentGateway::query()->where('slug', 'stripe')->first();
    }

    public function gateway(): ?PaymentGateway
    {
        return PaymentGateway::query()->where('slug', 'stripe')->first();
    }

    public function isEnabled(): bool
    {
        $g = $this->gateway();

        return (bool) ($g && $g->active && $this->secretKey());
    }

    public function secretKey(): ?string
    {
        $g = $this->gateway();
        if (! $g) {
            return null;
        }
        $keys = $this->activeKeys($g);
        $secret = trim((string) ($keys['secret_key'] ?? ''));

        return $secret !== '' ? $secret : null;
    }

    public function publishableKey(): ?string
    {
        $g = $this->gateway();
        if (! $g) {
            return null;
        }
        $keys = $this->activeKeys($g);
        $pub = trim((string) ($keys['client_id'] ?? ''));

        return $pub !== '' ? $pub : null;
    }

    public function webhookSecret(): ?string
    {
        $g = $this->gateway();
        if (! $g) {
            return null;
        }
        $keys = $this->activeKeys($g);
        $secret = trim((string) ($keys['webhook_secret'] ?? ''));

        return $secret !== '' ? $secret : null;
    }

    public function client(): StripeClient
    {
        $secret = $this->secretKey();
        if (! $secret) {
            throw new \RuntimeException('Stripe is not configured. Add USD Stripe keys in Payment Gateway.');
        }

        return new StripeClient($secret);
    }

    /**
     * @return array{aed:float,usd:float,rate:?float,aed_label:string,usd_label:string,stripe_enabled:bool,package_currency:string}
     */
    public function quote(Installment $installment): array
    {
        $installment->loadMissing('payment.courseFee');
        $aed = round((float) $installment->remaining_amount, 2);
        $packageCurrency = function_exists('payment_display_currency')
            ? payment_display_currency($installment->payment)
            : 'AED';
        $usd = $this->usdFromAed($aed, $installment);
        $rate = currency_rates_to_aed()['USD'] ?? null;

        return [
            'aed' => $aed,
            'usd' => $usd,
            'rate' => $rate ? (float) $rate : null,
            'aed_label' => format_payment_aed_amount($installment->payment, $aed),
            'usd_label' => 'USD ' . number_format($usd, 2),
            'stripe_enabled' => $this->isEnabled(),
            'package_currency' => $packageCurrency,
        ];
    }

    public function usdFromAed(float $aedAmount, Installment $installment): float
    {
        $installment->loadMissing('payment.courseFee');
        $packageCurrency = function_exists('payment_display_currency')
            ? payment_display_currency($installment->payment)
            : 'AED';

        if ($packageCurrency === 'USD' && function_exists('payment_display_amount_from_aed')) {
            return payment_display_amount_from_aed($installment->payment, $aedAmount);
        }

        $rate = currency_rates_to_aed()['USD'] ?? null;
        if (! $rate) {
            throw new \RuntimeException('USD currency rate is missing. Set USD in Currency Rate Setup.');
        }

        return convert_from_aed($aedAmount, 'USD');
    }

    public function createCheckoutSession(Installment $installment, Request $request): array
    {
        Installment::ensureStripeColumns();
        $installment->loadMissing(['payment.course', 'payment.courseFee', 'user']);

        if ($installment->status === 'paid') {
            throw new \RuntimeException('This installment is already paid.');
        }

        $aed = round((float) $installment->remaining_amount, 2);
        if ($aed <= 0) {
            throw new \RuntimeException('This installment has no remaining balance.');
        }

        $usd = $this->usdFromAed($aed, $installment);
        $cents = (int) round($usd * 100);
        if ($cents < 50) {
            throw new \RuntimeException('USD amount is too small to charge on Stripe.');
        }

        $name = trim((string) ($installment->payment?->course?->title ?? 'Course fee'));
        $name .= ' — installment #' . $installment->installment_number;

        $successUrl = rtrim($request->getSchemeAndHttpHost(), '/')
            . '/user/stripe/return?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = rtrim($request->getSchemeAndHttpHost(), '/') . '/user/stripe/cancel';

        $session = $this->client()->checkout->sessions->create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'customer_email' => $installment->user?->email ?: null,
            'client_reference_id' => (string) $installment->id,
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'unit_amount' => $cents,
                    'product_data' => [
                        'name' => $name,
                    ],
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'installment_id' => (string) $installment->id,
                'payment_id' => (string) ($installment->payment_id ?? ''),
                'user_id' => (string) $installment->user_id,
                'aed_amount' => (string) $aed,
                'usd_amount' => (string) $usd,
            ],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);

        $this->storeStripeMeta($installment, [
            'stripe_session_id' => $session->id,
            'charge_currency' => 'USD',
            'charge_amount' => $usd,
            'fx_rate_to_aed' => currency_rates_to_aed()['USD'] ?? null,
        ]);

        return [
            'checkout_url' => $session->url,
            'session_id' => $session->id,
            'usd' => $usd,
            'aed' => $aed,
        ];
    }

    public function fulfillSession(string $sessionId, Request $request): ?Installment
    {
        Installment::ensureStripeColumns();
        $session = $this->client()->checkout->sessions->retrieve($sessionId, [
            'expand' => ['payment_intent'],
        ]);

        if (($session->payment_status ?? '') !== 'paid' && ($session->status ?? '') !== 'complete') {
            return null;
        }

        $installmentId = (int) ($session->metadata['installment_id'] ?? $session->client_reference_id ?? 0);
        if ($installmentId <= 0) {
            return null;
        }

        $installment = Installment::query()->with(['payment.course', 'payment.courseFee', 'user'])->find($installmentId);
        if (! $installment) {
            return null;
        }

        $aed = (float) ($session->metadata['aed_amount'] ?? $installment->remaining_amount);
        $usd = isset($session->amount_total) ? round(((int) $session->amount_total) / 100, 2) : (float) ($session->metadata['usd_amount'] ?? 0);
        $intentId = is_object($session->payment_intent ?? null)
            ? (string) $session->payment_intent->id
            : (string) ($session->payment_intent ?? '');

        $this->storeStripeMeta($installment, [
            'stripe_session_id' => $session->id,
            'stripe_payment_intent' => $intentId !== '' ? $intentId : null,
            'charge_currency' => 'USD',
            'charge_amount' => $usd,
            'fx_rate_to_aed' => currency_rates_to_aed()['USD'] ?? null,
        ]);

        if ($installment->status !== 'paid') {
            $installment->payment_method = 'stripe';
            $installment->save();
            app(NoonCheckoutService::class)->markInstallmentPaid($installment->fresh(), $aed > 0 ? $aed : (float) $installment->remaining_amount, $request);
            $installment = $installment->fresh();
            $installment->payment_method = 'stripe';
            $installment->save();
            $this->storeStripeMeta($installment, [
                'stripe_session_id' => $session->id,
                'stripe_payment_intent' => $intentId !== '' ? $intentId : null,
                'charge_currency' => 'USD',
                'charge_amount' => $usd,
            ]);
        }

        return $installment->fresh();
    }

    public function constructWebhookEvent(string $payload, string $sigHeader)
    {
        $secret = $this->webhookSecret();
        if (! $secret) {
            $decoded = json_decode($payload, false);
            if (! $decoded) {
                throw new \RuntimeException('Invalid Stripe webhook payload.');
            }

            return $decoded;
        }

        return Webhook::constructEvent($payload, $sigHeader, $secret);
    }

    /**
     * @param  array<string,mixed>  $values
     */
    public function storeStripeMeta(Installment $installment, array $values): void
    {
        Installment::ensureStripeColumns();
        foreach ($values as $col => $val) {
            if ($val === null) {
                continue;
            }
            if (Schema::hasColumn('installments', $col)) {
                $installment->{$col} = $val;
            }
        }
        $installment->save();
    }

    /**
     * @return array<string,mixed>
     */
    private function activeKeys(PaymentGateway $gateway): array
    {
        $live = strtolower((string) $gateway->status) === 'live';
        $keys = $live ? ($gateway->production_keys ?? []) : ($gateway->test_keys ?? []);

        return is_array($keys) ? $keys : [];
    }
}
