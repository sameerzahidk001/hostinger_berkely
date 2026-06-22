<?php

namespace App\Services;

use App\Mail\UserMail;
use App\Models\Email;
use App\Models\Installment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RakBankCheckoutService
{
    public const SESSION_KEY = 'rakbank_pending_checkout';

    public function storePendingCheckout(int $installmentId, string $orderId, string $amount): void
    {
        session([
            self::SESSION_KEY => [
                'installment_id' => $installmentId,
                'order_id' => $orderId,
                'amount' => $amount,
                'user_id' => Auth::id(),
            ],
        ]);
    }

    public function pendingCheckout(): ?array
    {
        $pending = session(self::SESSION_KEY);

        return is_array($pending) ? $pending : null;
    }

    public function clearPendingCheckout(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function fetchOrder(string $orderId): ?array
    {
        $merchantId = config('services.rakbank.merchant_id');
        $apiPassword = config('services.rakbank.api_password');

        if (! $merchantId || ! $apiPassword) {
            return null;
        }

        $apiUsername = "merchant.{$merchantId}";
        $url = "https://rakbankpay-nam.gateway.mastercard.com/api/rest/version/100/merchant/{$merchantId}/order/{$orderId}";

        try {
            $response = Http::timeout(30)
                ->withBasicAuth($apiUsername, $apiPassword)
                ->get($url);

            if (! $response->successful()) {
                Log::warning('RakBank order retrieve failed', [
                    'order_id' => $orderId,
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('RakBank order retrieve exception: ' . $e->getMessage(), ['order_id' => $orderId]);

            return null;
        }
    }

    public function isOrderPaid(?array $orderData): bool
    {
        if (! is_array($orderData)) {
            return false;
        }

        $status = strtoupper((string) data_get($orderData, 'status', ''));
        $result = strtoupper((string) data_get($orderData, 'result', ''));

        if (in_array($status, ['CAPTURED', 'PAID', 'AUTHORIZED'], true)) {
            return true;
        }

        if ($result === 'SUCCESS') {
            return true;
        }

        $transactions = data_get($orderData, 'transaction', []);
        if (! is_array($transactions)) {
            $transactions = [$transactions];
        }

        foreach ($transactions as $transaction) {
            if (! is_array($transaction)) {
                continue;
            }

            $txResult = strtoupper((string) data_get($transaction, 'result', ''));
            $txStatus = strtoupper((string) data_get($transaction, 'order.status', data_get($transaction, 'status', '')));

            if ($txResult === 'SUCCESS' || in_array($txStatus, ['CAPTURED', 'PAID', 'AUTHORIZED'], true)) {
                return true;
            }
        }

        return false;
    }

    public function verifyOrderPaid(string $orderId): bool
    {
        return $this->isOrderPaid($this->fetchOrder($orderId));
    }

    public function markInstallmentPaid(Installment $installment, float $amount, Request $request): Installment
    {
        if ($installment->status === 'paid') {
            return $installment;
        }

        $installment->update([
            'paid_amount' => $amount,
            'remaining_amount' => 0,
            'paid_date' => now(),
            'status' => 'paid',
            'payment_method' => $installment->payment_method ?: 'rakbank',
        ]);

        $installment->loadMissing(['payment.course', 'payment.courseFee']);

        $user = $installment->user ?? Auth::user();
        $emailTemplate = Email::where('name', 'fees-paid')->first();

        if ($emailTemplate && $user) {
            $paidDisplay = format_payment_aed_amount($installment->payment, $amount);

            $emailBody = str_replace(
                ['{name}', '{email}', '{fees-paid}', '{fees-installment}'],
                [$user->name, $user->email, $paidDisplay, $installment->installment_number],
                $emailTemplate->body
            );

            $ccEmails = $emailTemplate->cc ? array_filter(array_map('trim', explode(',', $emailTemplate->cc))) : [];
            $bccEmails = $emailTemplate->bcc ? array_filter(array_map('trim', explode(',', $emailTemplate->bcc))) : [];

            try {
                Mail::to($user->email)
                    ->cc($ccEmails)
                    ->bcc($bccEmails)
                    ->send(new UserMail($user, $emailTemplate->subject, normalize_payment_email_body($emailBody)));
            } catch (\Throwable $e) {
                Log::warning('Installment paid email failed: ' . $e->getMessage(), [
                    'installment_id' => $installment->id,
                ]);
            }
        }

        $courseName = $installment->payment?->course?->title ?? 'Installment #' . $installment->installment_number;

        record_user_activity(
            'Receipt Recorded',
            'Receipt recorded for ' . $courseName . ' — ' . format_payment_aed_amount($installment->payment, $amount),
            route('user.home'),
            'student',
            $user?->id,
            null,
            $request
        );

        return $installment->fresh(['payment.course', 'payment.courseFee']);
    }

    public function completePendingCheckout(Request $request, ?string $orderId = null): ?Installment
    {
        $pending = $this->pendingCheckout();

        if (! $pending) {
            return null;
        }

        if ((int) ($pending['user_id'] ?? 0) !== (int) Auth::id()) {
            return null;
        }

        $orderId = $orderId ?: (string) ($pending['order_id'] ?? '');

        if ($orderId === '' || ! $this->verifyOrderPaid($orderId)) {
            return null;
        }

        $installment = Installment::with(['payment.courseFee', 'payment.course', 'user'])
            ->where('user_id', Auth::id())
            ->find($pending['installment_id'] ?? null);

        if (! $installment) {
            $this->clearPendingCheckout();

            return null;
        }

        $amount = (float) ($pending['amount'] ?? $installment->remaining_amount);
        $installment = $this->markInstallmentPaid($installment, $amount, $request);
        $this->clearPendingCheckout();

        return $installment;
    }
}
