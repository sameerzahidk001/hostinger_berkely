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
            $data['installments'] = Installment::with('payment')->where('user_id', Auth::id())
                ->whereHas('payment', function ($query) {
                    $query->where('status', 'Active');
                })
                ->get();
        }
        
        return view('user.home', compact('data'));
    }

    public function generateRakBankPaySession(Request $request)
    {
        $merchantId = env('RAKBANK_MERCHANT_ID');
        $apiPassword = env('RAKBANK_API_PASSWORD');
        $apiUsername = "merchant.$merchantId";
        // $url = "https://test-rakbankpay.mtf.gateway.mastercard.com/api/rest/version/100/merchant/{$merchantId}/session";
        $url = "https://rakbankpay-nam.gateway.mastercard.com/api/rest/version/100/merchant/{$merchantId}/session";

        $amount = number_format((float) $request->amount, 2, '.', '');
        $displayAmount = number_format((float) $request->amount, 2);
        $orderId = 'order-' . uniqid();

        $response = Http::withBasicAuth($apiUsername, $apiPassword)
            ->post($url, [
                "apiOperation" => "INITIATE_CHECKOUT",
                "order" => [
                    "description" => "({$displayAmount})",
                    "id" => $orderId,
                    "currency" => 'AED',
                    "amount" => $amount,
                ],
                "interaction" => [
                    "operation" => "PURCHASE",
                    "merchant" => [
                        "name" => "AED ({$displayAmount})",
                        "address" => [
                            "line1" => "200 Sample St",
                            "line2" => "1234 Example Town"
                        ]
                    ]
                ]
            ]);

        $responseData = $response->json();
        $responseData['orderId'] = $orderId;
        $responseData['displayAmount'] = "(AED {$displayAmount})";

        return response()->json($responseData);
    }
}