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
     * @return array<string,mixed>
     */
    public function quote(Installment $installment): array
    {
        $installment->loadMissing(['payment.courseFee', 'payment.course']);
        $aed = round((float) $installment->remaining_amount, 2);
        $packageCurrency = function_exists('payment_display_currency')
            ? payment_display_currency($installment->payment)
            : 'AED';
        $usd = $this->tryAmountInCurrency($aed, $installment, 'USD');
        $gbp = $this->tryAmountInCurrency($aed, $installment, 'GBP');
        $stripeOn = $this->isEnabled();
        $usdReady = $stripeOn && $usd >= $this->stripeMinAmount('USD');
        $gbpReady = $stripeOn && $gbp >= $this->stripeMinAmount('GBP');
        $itemName = trim((string) ($installment->payment?->course?->title ?? ''));
        if ($itemName === '') {
            $itemName = trim((string) ($installment->payment?->courseFee?->package_name ?? 'Course fee'));
        }
        $picked = function_exists('format_payment_aed_amount')
            ? format_payment_aed_amount($installment->payment, $aed)
            : ($packageCurrency . ' ' . number_format($aed, 2));

        return [
            'aed' => $aed,
            'usd' => $usd,
            'gbp' => $gbp,
            'rate' => currency_rates_to_aed()['USD'] ?? null,
            'gbp_rate' => currency_rates_to_aed()['GBP'] ?? null,
            'aed_label' => 'AED ' . number_format($aed, 2),
            'usd_label' => $usd > 0 ? 'USD ' . number_format($usd, 2) : 'USD',
            'gbp_label' => $gbp > 0 ? 'GBP ' . number_format($gbp, 2) : 'GBP',
            'item_name' => $itemName,
            'amount_label' => $picked,
            'stripe_enabled' => $usdReady,
            'gbp_enabled' => $gbpReady,
            'package_currency' => $packageCurrency,
        ];
    }

    public function usdFromAed(float $aedAmount, Installment $installment): float
    {
        return $this->amountInCurrency($aedAmount, $installment, 'USD');
    }

    public function amountInCurrency(float $aedAmount, Installment $installment, string $currency): float
    {
        $installment->loadMissing('payment.courseFee');
        $code = strtoupper($currency);
        $packageCurrency = function_exists('payment_display_currency')
            ? payment_display_currency($installment->payment)
            : 'AED';

        if ($packageCurrency === $code && function_exists('payment_display_amount_from_aed')) {
            return payment_display_amount_from_aed($installment->payment, $aedAmount);
        }

        $rate = currency_rates_to_aed()[$code] ?? null;
        if (! $rate) {
            throw new \RuntimeException($code . ' currency rate is missing. Set ' . $code . ' in Currency Rate Setup.');
        }

        return convert_from_aed($aedAmount, $code);
    }

    public function createCheckoutSession(Installment $installment, Request $request, string $uiMode = 'hosted'): array
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

        $currency = $this->normalizeStripeCurrency(
            (string) ($request->input('pay_currency') ?: $request->query('currency', 'usd'))
        );
        $amount = $this->amountInCurrency($aed, $installment, $currency);
        $cents = (int) round($amount * 100);
        $minCents = (int) round($this->stripeMinAmount($currency) * 100);
        if ($cents < $minCents) {
            throw new \RuntimeException(strtoupper($currency) . ' amount is too small to charge on Stripe.');
        }

        $name = $this->lineItemName($installment);

        $successUrl = rtrim($request->getSchemeAndHttpHost(), '/')
            . '/user/stripe/return?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = rtrim($request->getSchemeAndHttpHost(), '/') . '/user/stripe/cancel';

        $payload = [
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'customer_email' => $installment->user?->email ?: null,
            'client_reference_id' => (string) $installment->id,
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($currency),
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
                'charge_currency' => strtoupper($currency),
                'charge_amount' => (string) $amount,
                'usd_amount' => $currency === 'USD' ? (string) $amount : '',
                'gbp_amount' => $currency === 'GBP' ? (string) $amount : '',
            ],
        ];
        if ($uiMode === 'embedded') {
            $payload['ui_mode'] = 'embedded';
            $payload['return_url'] = $successUrl;
            $payload['redirect_on_completion'] = 'always';
        } else {
            $payload['success_url'] = $successUrl;
            $payload['cancel_url'] = $cancelUrl;
        }

        $session = $this->client()->checkout->sessions->create($payload);

        $this->storeStripeMeta($installment, [
            'stripe_session_id' => $session->id,
            'charge_currency' => strtoupper($currency),
            'charge_amount' => $amount,
            'fx_rate_to_aed' => currency_rates_to_aed()[strtoupper($currency)] ?? null,
        ]);

        return [
            'checkout_url' => $session->url,
            'client_secret' => $session->client_secret ?? null,
            'session_id' => $session->id,
            'usd' => $currency === 'USD' ? $amount : $this->tryAmountInCurrency($aed, $installment, 'USD'),
            'gbp' => $currency === 'GBP' ? $amount : $this->tryAmountInCurrency($aed, $installment, 'GBP'),
            'charge_currency' => strtoupper($currency),
            'charge_amount' => $amount,
            'aed' => $aed,
        ];
    }

    public function normalizeStripeCurrency(?string $currency): string
    {
        $code = strtoupper(trim((string) $currency));

        return $code === 'GBP' ? 'GBP' : 'USD';
    }

    public function stripeMinAmount(string $currency): float
    {
        return strtoupper($currency) === 'GBP' ? 0.30 : 0.50;
    }

    public function tryAmountInCurrency(float $aedAmount, Installment $installment, string $currency): float
    {
        try {
            return $this->amountInCurrency($aedAmount, $installment, $currency);
        } catch (\Throwable $e) {
            return 0.0;
        }
    }

    public function lineItemName(Installment $installment): string
    {
        $name = trim((string) ($installment->payment?->course?->title ?? 'Course fee'));
        $total = 1;
        if ($installment->payment_id) {
            $total = (int) Installment::query()
                ->where('payment_id', $installment->payment_id)
                ->count();
        }
        if ($total < 1) {
            $total = 1;
        }
        $n = (int) ($installment->installment_number ?: 1);
        if ($n < 1) {
            $n = 1;
        }

        return $name . ' — installment ' . $n . ' of ' . $total;
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
        $chargeCurrency = $this->normalizeStripeCurrency(
            (string) ($session->metadata['charge_currency'] ?? $session->currency ?? 'USD')
        );
        $charged = isset($session->amount_total)
            ? round(((int) $session->amount_total) / 100, 2)
            : (float) ($session->metadata['charge_amount'] ?? $session->metadata['usd_amount'] ?? 0);
        $intentId = is_object($session->payment_intent ?? null)
            ? (string) $session->payment_intent->id
            : (string) ($session->payment_intent ?? '');

        $this->storeStripeMeta($installment, [
            'stripe_session_id' => $session->id,
            'stripe_payment_intent' => $intentId !== '' ? $intentId : null,
            'charge_currency' => $chargeCurrency,
            'charge_amount' => $charged,
            'fx_rate_to_aed' => currency_rates_to_aed()[$chargeCurrency] ?? null,
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
                'charge_currency' => $chargeCurrency,
                'charge_amount' => $charged,
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
