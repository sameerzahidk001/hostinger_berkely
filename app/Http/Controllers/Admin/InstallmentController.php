<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use App\Models\Installment;
use Stripe\StripeClient;
use App\Models\Country;
use App\Models\Payment;
use Stripe\Stripe;

class InstallmentController extends Controller
{
    // Show Installments Page
    public function index()
    {
        $installments = Installment::with(['user', 'payment.course', 'payment.courseFee'])->get();
        return view('admin.installment.installments', compact('installments'));
    }

    // Pay Installment
    public function payInstallment(Request $request, $id)
    {
        $installment = Installment::findOrFail($id);
        $amount = $request->amount;
        $card = $request->card;

        // Get payment gateway config
        $stripeGateway = PaymentGateway::where('slug', 'stripe')->first();
        $paypalGateway = PaymentGateway::where('slug', 'paypal')->first();

        if ($request->method === 'stripe') {
            $secret = $stripeGateway->test_keys['secret_key'] ?? null;

            if (!$secret) {
                return response()->json(['success' => false, 'message' => 'Stripe key missing']);
            }

            $stripe = new StripeClient($secret);
            $session = $stripe->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => "Installment: {$installment->name}",
                        ],
                        'unit_amount' => $amount * 100,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('stripe.success', $installment->id),
                'cancel_url' => route('stripe.cancel'),
            ]);

            return response()->json(['success' => true, 'url' => $session->url]);
        }

        if ($request->method === 'paypal') {
            $paypalConfig = $paypalGateway->test_keys ?? [];

            $clientId = $paypalConfig['client_id'] ?? null;
            $secret = $paypalConfig['secret_key'] ?? null;

            if (!$clientId || !$secret) {
                return response()->json(['success' => false, 'message' => 'PayPal credentials missing']);
            }

            // Auth & create order
            $paypalUrl = 'https://api-m.sandbox.paypal.com';
            $auth = Http::withBasicAuth($clientId, $secret)
                ->asForm()
                ->post("$paypalUrl/v1/oauth2/token", ['grant_type' => 'client_credentials']);

            $accessToken = $auth->json()['access_token'] ?? null;

            if (!$accessToken) {
                return response()->json(['success' => false, 'message' => 'PayPal auth failed']);
            }

            $order = Http::withHeaders([
                'Authorization' => "Bearer $accessToken",
                'Content-Type' => 'application/json',
            ])->post("$paypalUrl/v2/checkout/orders", [
                "intent" => "CAPTURE",
                "purchase_units" => [[
                    "amount" => ["currency_code" => "USD", "value" => $amount],
                    "description" => "Installment Payment for {$installment->name}"
                ]]
            ]);

            $orderData = $order->json();
            $approvalUrl = collect($orderData['links'])->firstWhere('rel', 'approve')['href'] ?? null;

            return response()->json([
                'success' => true,
                'approval_url' => $approvalUrl
            ]);
        }

        if ($request->method === 'simplify') {
            $token = $request->token;

            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Missing Simplify token']);
            }

            $amountInCents = (int) ($amount * 100);

            $response = Http::withBasicAuth(env('SIMPLIFY_PRIVATE'), '')
                ->post('https://api.simplify.com/v1/api/payments', [
                    'amount'      => $amountInCents,
                    'currency'    => 'USD',
                    'token'       => $token,
                    'description' => "Installment Payment #{$installment->id}",
                    'reference'   => "INST-{$installment->id}"
                ]);

            if ($response->successful()) {
                $installment->paid_amount = $installment->remaining_amount;
                $installment->remaining_amount = 0;
                $installment->status = 'paid';
                $installment->save();

                $this->logStudentInstallmentPayment($installment, $request);

                return response()->json(['success' => true, 'message' => 'Payment successfully completed.']);
            } else {
                $error = $response->json()['error']['message'] ?? 'Unknown error occurred.';
                return response()->json(['success' => false, 'message' => "Simplify Error: $error"]);
            }
        }

        return response()->json(['success' => false, 'message' => 'Invalid method']);
    }

    public function paymentSuccess($id)
    {
        $installment = Installment::findOrFail($id);
        $installment->paid_amount = $installment->remaining_amount;
        $installment->remaining_amount = 0;
        $installment->status = 'paid';
        $installment->save();

        $this->logStudentInstallmentPayment($installment, request());

        return redirect()->route('student.installments');
    }

    private function logStudentInstallmentPayment(Installment $installment, Request $request): void
    {
        $installment->loadMissing(['payment.course']);

        $payment = $installment->payment;
        $courseName = $payment?->course?->title ?? 'Installment #' . $installment->installment_number;
        $paidAmount = (float) $installment->paid_amount;

        record_user_activity(
            'Receipt Recorded',
            'Receipt recorded for ' . $courseName . ' — ' . ($payment ? format_payment_aed_amount($payment, $paidAmount) : number_format($paidAmount, 2) . ' AED'),
            route('user.home'),
            'student',
            $installment->user_id,
            null,
            $request
        );
    }

    public function receipt($id)
    {
        $installment = Installment::with(['user', 'payment.course', 'payment.courseFee'])->findOrFail($id);
        return view('admin.payments.receipt', compact('installment'));
    }

    public function generateInvoice(Request $request)
    {
        // Function call from helper
        $location = getUserLocation();
        $countryCode = $location['country'] ?? 'US';

        // Get the currency for the detected country
        $country = Country::where('iso_code', $countryCode)->first();
        
        $currency = $country->currency ? $country->currency->code : 'USD';

        $course_id = $request->course_id;
        $installments = Installment::with(['payment.course', 'payment.courseFee'])
            ->where('user_id', $request->user_id)
            ->where('payment_id', $request->payment_id)
            ->whereHas('payment', function ($query) use ($course_id) {
                $query->where('course_id', $course_id);
            })
            ->orderBy('installment_number')
            ->get();

        if ($installments->isEmpty()) {
            abort(404, 'No installments found for this course, user, and payment.');
        }

        $user = $installments->first()->user;
        $course = $installments->first()->payment->course;
        $coursefee = $installments->first()->payment->courseFee;
        $payments = $installments->first()->payment;

        // Sum Paid Amount and Remaining Amount
        $totalPaidAmount = $installments->sum('paid_amount');
        $totalRemainingAmount = $installments->sum('remaining_amount');

        return view('admin.payments.invoice', compact('installments', 'user', 'course', 'coursefee', 'totalPaidAmount', 'totalRemainingAmount', 'payments', 'currency'));

        // $pdf = PDF::loadView('admin.payments.pdf', compact(
        //     'installments',
        //     'user',
        //     'course',
        //     'coursefee',
        //     'totalPaidAmount',
        //     'totalRemainingAmount',
        //     'payments',
        //     'currency'
        // ));

        // return $pdf->download('invoice.pdf');
    }
}