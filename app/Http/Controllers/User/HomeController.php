<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\Installment;
use App\Services\RakBankCheckoutService;

class HomeController extends Controller
{
    public function index()
    {
        $data = [];

        if (auth()->user()->hasPermission('installment-list')) {
            $data['installments'] = Installment::with(['payment.courseFee', 'payment.course'])
                ->where('user_id', Auth::id())
                ->whereHas('payment', function ($query) {
                    $query->where('status', 'Active');
                })
                ->orderByDesc('created_at')
                ->get();
        }

        return view('user.home', compact('data'));
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
