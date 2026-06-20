<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\Installment;

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

        $installment = Installment::with(['payment.courseFee'])
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
        $returnUrl = $request->input('return_url', url('/user'));

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
        ]);
    }
}
