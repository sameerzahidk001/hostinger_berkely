<?php

namespace App\Services;

use App\Mail\UserMail;
use App\Models\Email;
use App\Models\Installment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class NoonCheckoutService
{
    public const SESSION_KEY = 'noon_pending_checkout';

    public function isConfigured(): bool
    {
        return $this->authKey() !== '';
    }

    public function storePendingCheckout(int $installmentId, string $orderId, string $amount, string $reference, ?int $userId = null): void
    {
        $payload = [
            'installment_id' => $installmentId,
            'order_id' => $orderId,
            'amount' => $amount,
            'reference' => $reference,
            'user_id' => $userId ?? Auth::id(),
        ];

        session([self::SESSION_KEY => $payload]);
        Cache::put($this->cacheKey('order', $orderId), $payload, now()->addHours(12));
        Cache::put($this->cacheKey('reference', $reference), $payload, now()->addHours(12));
    }

    public function pendingCheckout(): ?array
    {
        $pending = session(self::SESSION_KEY);

        return is_array($pending) ? $pending : null;
    }

    public function clearPendingCheckout(?string $orderId = null, ?string $reference = null): void
    {
        $pending = $this->pendingCheckout();
        $orderId = $orderId ?: (string) ($pending['order_id'] ?? '');
        $reference = $reference ?: (string) ($pending['reference'] ?? '');

        session()->forget(self::SESSION_KEY);

        if ($orderId !== '') {
            Cache::forget($this->cacheKey('order', $orderId));
        }

        if ($reference !== '') {
            Cache::forget($this->cacheKey('reference', $reference));
        }
    }

    public function makeMerchantReference(int $installmentId): string
    {
        return 'INST-' . $installmentId . '-' . Str::lower(Str::random(8));
    }

    public function initiateCheckout(Installment $installment, string $chargeAmount, string $returnUrl, Request $request): array
    {
        $currency = strtoupper((string) config('services.noon.currency', 'AED'));
        $reference = $this->makeMerchantReference((int) $installment->id);
        $orderName = $this->orderName($installment);
        $user = $installment->user ?? Auth::user();

        $payload = [
            'apiOperation' => 'INITIATE',
            'order' => [
                'amount' => (float) $chargeAmount,
                'currency' => $currency,
                'channel' => (string) config('services.noon.channel', 'web'),
                'category' => (string) config('services.noon.category', 'pay'),
                'name' => $orderName,
                'reference' => $reference,
                'ipAddress' => $this->clientIp($request),
            ],
            'configuration' => [
                'locale' => 'en',
                'returnUrl' => $returnUrl,
                'paymentAction' => (string) config('services.noon.payment_action', 'SALE'),
                'allowedRetries' => 2,
                'webhookUrl' => url('/noon/webhook'),
            ],
        ];

        if ($user) {
            [$firstName, $lastName] = $this->splitName((string) $user->name);

            $payload['billing'] = [
                'address' => [
                    'country' => 'AE',
                ],
                'contact' => array_filter([
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'email' => $this->billingEmail($user->email),
                ]),
            ];
        }

        $response = $this->http()->post($this->apiUrl('order'), $payload);
        $data = $response->json() ?? [];

        $resultCode = data_get($data, 'resultCode', data_get($data, 'result.code'));
        $checkoutUrl = (string) data_get($data, 'result.checkoutData.postUrl', data_get($data, 'checkoutData.postUrl', ''));
        $orderId = (string) data_get($data, 'result.order.id', data_get($data, 'order.id', ''));
        $apiFailed = $resultCode !== null && $resultCode !== '' && (int) $resultCode !== 0;

        if (! $response->successful() || $apiFailed || $checkoutUrl === '' || $orderId === '') {
            Log::warning('Noon INITIATE failed', [
                'status' => $response->status(),
                'installment_id' => $installment->id,
                'reference' => $reference,
                'response' => $data,
            ]);

            $apiMessage = (string) (
                data_get($data, 'message')
                ?: data_get($data, 'result.description')
                ?: data_get($data, 'resultCodeMessage')
                ?: 'Payment session could not be started.'
            );

            if ((int) $resultCode === 1505 || stripos($apiMessage, 'not authorized') !== false) {
                $apiMessage = 'Noon rejected payment credentials (error 1505). Check live Application roles include order INITIATE/SALE, and that NOON_* keys match the live portal.';
            }

            throw new \RuntimeException($apiMessage);
        }

        $this->storePendingCheckout(
            (int) $installment->id,
            $orderId,
            $chargeAmount,
            $reference,
            (int) $installment->user_id
        );

        return [
            'order_id' => $orderId,
            'reference' => $reference,
            'checkout_url' => $checkoutUrl,
            'currency' => $currency,
        ];
    }

    public function fetchOrder(string $orderId): ?array
    {
        if ($orderId === '' || ! $this->isConfigured()) {
            return null;
        }

        try {
            $response = $this->http()->get($this->apiUrl('order/' . $orderId));

            if (! $response->successful()) {
                Log::warning('Noon GET ORDER failed', [
                    'order_id' => $orderId,
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('Noon GET ORDER exception: ' . $e->getMessage(), ['order_id' => $orderId]);

            return null;
        }
    }

    public function isOrderPaid(?array $orderData): bool
    {
        if (! is_array($orderData)) {
            return false;
        }

        $status = strtoupper((string) data_get(
            $orderData,
            'result.order.status',
            data_get($orderData, 'order.status', '')
        ));

        return in_array($status, ['CAPTURED', 'PARTIALLY_CAPTURED', 'AUTHORIZED'], true);
    }

    public function orderStatus(?array $orderData): string
    {
        return strtoupper((string) data_get(
            $orderData,
            'result.order.status',
            data_get($orderData, 'order.status', '')
        ));
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
            'payment_method' => $installment->payment_method ?: 'noon',
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

        if ($installment->payment && class_exists(\App\Services\StudyMaterialService::class)) {
            try {
                app(\App\Services\StudyMaterialService::class)->tryGrantAccessForPaidPayment($installment->payment);
            } catch (\Throwable $e) {
                Log::warning('Study material grant after Noon payment failed: ' . $e->getMessage(), [
                    'installment_id' => $installment->id,
                ]);
            }
        }

        return $installment->fresh(['payment.course', 'payment.courseFee']);
    }

    public function completeOrder(Request $request, ?string $orderId = null, bool $requireAuthenticatedUser = true): ?Installment
    {
        $orderId = $this->normalizeOrderId($orderId ?: (string) $request->query('orderId', $request->input('orderId', '')));
        $pending = $this->pendingFromOrderId($orderId);

        if ($orderId === '') {
            $orderId = (string) ($pending['order_id'] ?? '');
        }

        if ($orderId === '') {
            return null;
        }

        $orderData = $this->fetchOrder($orderId);

        if (! $this->isOrderPaid($orderData)) {
            return null;
        }

        if (! $pending) {
            $reference = (string) data_get(
                $orderData,
                'result.order.reference',
                data_get($orderData, 'order.reference', '')
            );
            $pending = $this->pendingFromReference($reference);
        }

        $installmentId = (int) ($pending['installment_id'] ?? $this->installmentIdFromReference(
            (string) data_get($orderData, 'result.order.reference', data_get($orderData, 'order.reference', ''))
        ));

        if ($installmentId <= 0) {
            return null;
        }

        $installment = Installment::with(['payment.courseFee', 'payment.course', 'user'])
            ->find($installmentId);

        if (! $installment) {
            return null;
        }

        if ($requireAuthenticatedUser && (int) $installment->user_id !== (int) Auth::id()) {
            return null;
        }

        $amount = (float) ($pending['amount'] ?? $installment->remaining_amount);
        $installment = $this->markInstallmentPaid($installment, $amount, $request);
        $this->clearPendingCheckout($orderId, $pending['reference'] ?? null);

        return $installment;
    }

    public function completePendingCheckout(Request $request, ?string $orderId = null): ?Installment
    {
        return $this->completeOrder($request, $orderId, true);
    }

    public function pendingFromOrderId(string $orderId): ?array
    {
        if ($orderId !== '') {
            $cached = Cache::get($this->cacheKey('order', $orderId));
            if (is_array($cached)) {
                return $cached;
            }
        }

        $pending = $this->pendingCheckout();
        if ($pending && ($orderId === '' || (string) ($pending['order_id'] ?? '') === $orderId)) {
            return $pending;
        }

        return null;
    }

    public function pendingFromReference(string $reference): ?array
    {
        if ($reference === '') {
            return null;
        }

        $cached = Cache::get($this->cacheKey('reference', $reference));

        return is_array($cached) ? $cached : null;
    }

    public function installmentIdFromReference(string $reference): ?int
    {
        if (preg_match('/^INST-(\d+)-/i', $reference, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    public function normalizeOrderId(string $orderId): string
    {
        return preg_replace('/\D+/', '', $orderId) ?: $orderId;
    }

    protected function http()
    {
        $client = Http::timeout(30)
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'Authorization' => $this->authorizationHeader(),
            ]);

        $caBundle = base_path('.tools/php/extras/ssl/cacert.pem');
        if (is_file($caBundle)) {
            $client = $client->withOptions(['verify' => $caBundle]);
        }

        return $client;
    }

    protected function authorizationHeader(): string
    {
        // Official Noon docs: Authorization = "Key " + Base64(BusinessId.AppId:AppKey)
        // Environment is selected by API URL (api vs api-test), not by Key_Live/Key_Test.
        // Legacy Key_Live / Key_Test schemes cause error 1505 on INITIATE.
        $scheme = trim((string) config('services.noon.auth_scheme', 'Key'));
        if ($scheme === '' || preg_match('/^Key([_-]|$)/i', $scheme)) {
            $scheme = 'Key';
        }

        return $scheme . ' ' . $this->authKey();
    }

    protected function authKey(): string
    {
        $preencoded = trim((string) config('services.noon.auth_key', ''));
        if ($preencoded !== '') {
            return $preencoded;
        }

        $businessId = trim((string) config('services.noon.business_id', ''));
        $appId = trim((string) config('services.noon.app_id', ''));
        $appKey = trim((string) config('services.noon.app_key', ''));

        if ($businessId === '' || $appId === '' || $appKey === '') {
            return '';
        }

        return base64_encode($businessId . '.' . $appId . ':' . $appKey);
    }

    protected function apiUrl(string $path): string
    {
        $base = rtrim((string) config('services.noon.api_url'), '/');

        return $base . '/' . ltrim($path, '/');
    }

    protected function cacheKey(string $type, string $value): string
    {
        return 'noon_checkout:' . $type . ':' . $value;
    }

    protected function orderName(Installment $installment): string
    {
        $course = trim((string) ($installment->payment?->course?->title ?: 'Course installment'));
        $name = trim(preg_replace('/\s+/', ' ', $course) ?? $course);

        return Str::limit($name, 50, '');
    }

    protected function splitName(string $fullName): array
    {
        $fullName = trim(preg_replace('/\s+/', ' ', $fullName) ?? $fullName);

        if ($fullName === '') {
            return ['Student', 'Berkeley'];
        }

        $parts = explode(' ', $fullName, 2);

        return [$parts[0], $parts[1] ?? $parts[0]];
    }

    protected function clientIp(Request $request): string
    {
        $ip = (string) $request->ip();

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $ip;
        }

        return '8.8.8.8';
    }

    protected function billingEmail(?string $email): string
    {
        $email = strtolower(trim((string) $email));

        if (
            $email !== ''
            && filter_var($email, FILTER_VALIDATE_EMAIL)
            && ! preg_match('/\.(local|test|invalid)$/i', $email)
        ) {
            return $email;
        }

        return 'operations@berkeleyme.com';
    }
}
