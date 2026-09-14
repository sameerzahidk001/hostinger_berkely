<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use App\Models\Installment;
use App\Services\NoonCheckoutService;
use App\Services\RakBankCheckoutService;
use App\Services\StudyMaterialService;

class HomeController extends Controller
{
    public function index(StudyMaterialService $lms)
    {
        $user = Auth::user();
        $isInstructor = $user->roles()->where('name', 'instructor')->exists();

        if (! $isInstructor && ! $user->hasPermission('dashboard-read')) {
            return redirect()->route('user.profile');
        }

        $data = [];

        if (! $isInstructor && $user->hasPermission('installment-list')) {
            $data['installments'] = Installment::with(['payment.courseFee', 'payment.course'])
                ->where('user_id', Auth::id())
                ->whereHas('payment', function ($query) {
                    $query->where('status', 'Active');
                })
                ->orderByDesc('created_at')
                ->get();
        }

        $courseAccesses = collect();
        if (Schema::hasTable('study_material_student_access') || Schema::hasTable('study_material_instructor_access')) {
            $courseAccesses = $lms->portalAccessesForUser($user);
        }

        return view('user.home', compact('data', 'courseAccesses', 'isInstructor'));
    }

    public function payments()
    {
        $data = [];

        if (auth()->user()->hasPermission('installment-list')
            || auth()->user()->roles()->where('name', 'student')->exists()) {
            $data['installments'] = Installment::with(['payment.courseFee', 'payment.course'])
                ->where('user_id', Auth::id())
                ->whereHas('payment', function ($query) {
                    $query->where('status', 'Active');
                })
                ->orderByDesc('created_at')
                ->get();
        }

        return view('user.payments.index', compact('data'));
    }

    public function generateNoonCheckout(Request $request, NoonCheckoutService $checkout)
    {
        $request->validate([
            'installment_id' => 'required|exists:installments,id',
        ]);

        $installment = Installment::with(['payment.course', 'payment.courseFee', 'user'])
            ->where('user_id', Auth::id())
            ->findOrFail($request->installment_id);

        $settlingAed = round((float) $installment->remaining_amount, 2);
        if ($settlingAed <= 0) {
            return response()->json([
                'success' => false,
                'error' => 'This installment has no remaining balance.',
            ], 422);
        }

        if (! $checkout->isConfigured()) {
            Log::error('Noon credentials missing from config (check NOON_BUSINESS_ID / NOON_APP_ID / NOON_APP_KEY or NOON_AUTH_KEY in .env, then php artisan config:clear).');

            return response()->json([
                'success' => false,
                'error' => 'Online payment is not configured. Please contact support.',
            ], 503);
        }

        $chargeAmount = number_format($settlingAed, 2, '.', '');
        $returnUrl = route('user.noon.return');

        try {
            $session = $checkout->initiateCheckout($installment, $chargeAmount, $returnUrl, $request);
        } catch (\Throwable $e) {
            Log::error('Noon checkout initiate failed: ' . $e->getMessage(), [
                'installment_id' => $installment->id,
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage() ?: 'Payment session could not be started. Please try again.',
            ], 502);
        }

        return response()->json([
            'success' => true,
            'checkoutUrl' => $session['checkout_url'],
            'orderId' => $session['order_id'],
            'displayAmount' => format_payment_aed_amount($installment->payment, $settlingAed),
        ]);
    }

    public function handleNoonReturn(Request $request, NoonCheckoutService $checkout)
    {
        $orderId = $checkout->normalizeOrderId((string) $request->query('orderId', $request->query('order_id', '')));
        $orderData = $orderId !== '' ? $checkout->fetchOrder($orderId) : null;
        $reportedStatus = $checkout->orderStatus($orderData);

        if (in_array($reportedStatus, ['CANCELLED', 'CANCELED', 'FAILED', 'EXPIRED', 'REJECTED'], true)) {
            $checkout->clearPendingCheckout($orderId ?: null);

            $message = $reportedStatus === 'CANCELLED' || $reportedStatus === 'CANCELED'
                ? 'Payment was cancelled. No charge was made.'
                : 'Payment was not completed. Please try again.';

            return redirect()
                ->route('user.home')
                ->with('error', $message);
        }

        if ($orderId !== '') {
            $installment = $checkout->completePendingCheckout($request, $orderId);

            if ($installment) {
                return redirect()
                    ->route('user.installments.receipt', $installment->id)
                    ->with('success', 'Payment received. Your receipt is ready.');
            }
        }

        $pending = $checkout->pendingCheckout();
        if ($pending) {
            $installment = $checkout->completePendingCheckout($request, (string) ($pending['order_id'] ?? ''));

            if ($installment) {
                return redirect()
                    ->route('user.installments.receipt', $installment->id)
                    ->with('success', 'Payment received. Your receipt is ready.');
            }
        }

        $checkout->clearPendingCheckout($orderId ?: null);

        return redirect()
            ->route('user.home')
            ->with('error', 'Payment could not be confirmed automatically. If your card was charged, please contact support with your bank reference.');
    }

    public function generateRakBankPaySession(Request $request)
    {
        $request->validate([
            'installment_id' => 'required|exists:installments,id',
            'return_url' => 'nullable|url',
        ]);

        $installment = Installment::with(['payment.course', 'payment.courseFee'])
            ->where('user_id', Auth::id())
            ->findOrFail($request->installment_id);

        $payment = $installment->payment;
        $settlingAed = round((float) $installment->remaining_amount, 2);

        if ($settlingAed <= 0) {
            return response()->json([
                'success' => false,
                'error' => 'This installment has no remaining balance.',
            ], 422);
        }

        $merchantId = config('services.rakbank.merchant_id');
        $apiPassword = config('services.rakbank.api_password');
        $checkoutCurrency = strtoupper((string) config('services.rakbank.checkout_currency', 'AED'));

        if (! $merchantId || ! $apiPassword) {
            Log::error('RakBank credentials missing from config (check RAKBANK_MERCHANT_ID / RAKBANK_API_PASSWORD in .env).');

            return response()->json([
                'success' => false,
                'error' => 'Online payment is not configured. Please contact support.',
            ], 503);
        }

        $chargeAmount = number_format($settlingAed, 2, '.', '');
        $studentAmountLabel = format_payment_aed_amount($payment, $settlingAed);
        $orderId = 'order-' . uniqid();
        $returnUrl = route('user.rakbank.return', [
            'installment' => $installment->id,
        ]);

        app(RakBankCheckoutService::class)->storePendingCheckout(
            (int) $installment->id,
            $orderId,
            $chargeAmount
        );

        $apiUsername = "merchant.{$merchantId}";
        $url = "https://rakbankpay-nam.gateway.mastercard.com/api/rest/version/100/merchant/{$merchantId}/session";

        try {
            $response = Http::timeout(30)
                ->withBasicAuth($apiUsername, $apiPassword)
                ->post($url, [
                    'apiOperation' => 'INITIATE_CHECKOUT',
                    'order' => [
                        'description' => 'Course installment payment',
                        'id' => $orderId,
                        'currency' => $checkoutCurrency,
                        'amount' => $chargeAmount,
                    ],
                    'interaction' => [
                        'operation' => 'PURCHASE',
                        'returnUrl' => $returnUrl,
                        'cancelUrl' => $returnUrl,
                        'displayControl' => [
                            'billingAddress' => 'HIDE',
                        ],
                        'merchant' => [
                            'name' => 'Berkeley School of Business',
                            'address' => [
                                'line1' => 'Berkeley Square, Mayfair',
                                'line2' => 'London, W1J, UK',
                            ],
                        ],
                    ],
                ]);
        } catch (\Throwable $e) {
            Log::error('RakBank session request failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Payment session could not be started. Please try again.',
            ], 502);
        }

        $responseData = $response->json() ?? [];
        $sessionId = data_get($responseData, 'session.id');

        if (! $response->successful() || ! $sessionId) {
            $gatewayMessage = data_get($responseData, 'error.explanation')
                ?? data_get($responseData, 'error.cause')
                ?? data_get($responseData, 'result');

            Log::warning('RakBank INITIATE_CHECKOUT failed', [
                'status' => $response->status(),
                'order_id' => $orderId,
                'currency' => $checkoutCurrency,
                'amount' => $chargeAmount,
                'response' => $responseData,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Payment session could not be started. Please try again.',
                'gateway' => $gatewayMessage,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'session' => ['id' => $sessionId],
            'orderId' => $orderId,
            'displayAmount' => $studentAmountLabel,
            'settlingAmount' => $chargeAmount,
            'checkoutCurrency' => $checkoutCurrency,
            'summary' => [
                'invoice_no' => 'INV-' . str_pad((string) $installment->payment_id, 6, '0', STR_PAD_LEFT),
                'invoice_date' => $installment->created_at?->format('d-M-Y'),
                'invoice_amount' => format_payment_amount($payment)['display'],
                'payment_plan' => $installment->installment_number . '/' . $payment->total_installment,
                'due_date' => $installment->due_date
                    ? \Carbon\Carbon::parse($installment->due_date)->format('d-M-Y')
                    : 'N/A',
                'course_name' => $payment->course->title ?? 'N/A',
                'package_name' => $payment->courseFee->package_name ?? 'N/A',
            ],
        ]);
    }

    public function handleRakBankReturn(Request $request, RakBankCheckoutService $checkout)
    {
        $pending = $checkout->pendingCheckout();
        $orderId = (string) data_get($pending, 'order_id', '');

        if ($orderId !== '') {
            $installment = $checkout->completePendingCheckout($request, $orderId);

            if ($installment) {
                return redirect()
                    ->route('user.installments.receipt', $installment->id)
                    ->with('success', 'Payment received. Your receipt is ready.');
            }
        }

        $checkout->clearPendingCheckout();

        return redirect()
            ->route('user.home')
            ->with('error', 'Payment could not be confirmed automatically. If your card was charged, please contact support with your bank reference.');
    }
}
