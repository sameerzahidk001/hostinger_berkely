<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;
use App\Models\Installment;
use App\Models\Country;

class HomeController extends Controller
{
    public function index()
    {
        $data = [];

        if(auth()->user()->hasPermission('installment-list')) {
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
        ]);

        $installment = Installment::with(['payment.courseFee'])
            ->where('user_id', Auth::id())
            ->findOrFail($request->installment_id);

        $merchantId = env('RAKBANK_MERCHANT_ID');
        $apiPassword = env('RAKBANK_API_PASSWORD');
        $apiUsername = "merchant.$merchantId";
        $url = "https://rakbankpay-nam.gateway.mastercard.com/api/rest/version/100/merchant/{$merchantId}/session";

        $amount = number_format((float) $installment->remaining_amount, 2, '.', '');
        $studentAmountLabel = format_payment_aed_amount($installment->payment, (float) $installment->remaining_amount);
        $orderId = 'order-' . uniqid();

        $response = Http::withBasicAuth($apiUsername, $apiPassword)
            ->post($url, [
                "apiOperation" => "INITIATE_CHECKOUT",
                "order" => [
                    "description" => "Course installment payment",
                    "id" => $orderId,
                    "currency" => 'AED',
                    "amount" => $amount,
                ],
                "interaction" => [
                    "operation" => "PURCHASE",
                    "merchant" => [
                        "name" => "Berkeley School of Business",
                        "address" => [
                            "line1" => "Berkeley Square, Mayfair",
                            "line2" => "London, W1J, UK"
                        ]
                    ]
                ]
            ]);

        $responseData = $response->json();
        $responseData['orderId'] = $orderId;
        $responseData['displayAmount'] = $studentAmountLabel;
        $responseData['settlingAmount'] = $amount;

        return response()->json($responseData);
    }
}