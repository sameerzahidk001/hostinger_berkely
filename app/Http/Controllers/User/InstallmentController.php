<?php

namespace App\Http\Controllers\User;

use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;
use App\Models\Installment;
use App\Models\Payment;
use App\Models\Country;
use App\Mail\UserMail;
use App\Models\Email;
use App\Services\RakBankCheckoutService;

class InstallmentController extends Controller
{
    public function updateInstallment(Request $request, RakBankCheckoutService $checkout)
    {
        $request->validate([
            'installment_id' => 'required|exists:installments,id',
            'amount' => 'required|numeric|min:0.01',
            'order_id' => 'nullable|string|max:120',
        ]);

        $installment = Installment::with(['payment.courseFee', 'payment.course'])
            ->where('user_id', Auth::id())
            ->findOrFail($request->installment_id);

        if ($installment->status === 'paid') {
            return response()->json([
                'success' => true,
                'message' => 'Installment already paid.',
                'receipt_url' => route('user.installments.receipt', $installment->id),
            ]);
        }

        $orderId = $request->input('order_id') ?: (string) data_get($checkout->pendingCheckout(), 'order_id', '');

        if ($orderId !== '' && ! $checkout->verifyOrderPaid($orderId)) {
            return response()->json([
                'success' => false,
                'error' => 'Payment was not confirmed by the bank yet. Please wait a moment and refresh.',
            ], 422);
        }

        $amount = (float) $request->amount;
        $installment = $checkout->markInstallmentPaid($installment, $amount, $request);
        $checkout->clearPendingCheckout();

        return response()->json([
            'success' => true,
            'message' => 'Installment updated and receipt created.',
            'receipt_url' => route('user.installments.receipt', $installment->id),
        ]);
    }

    public function receipt($id)
    {
        $installment = Installment::with(['user', 'payment.course', 'payment.courseFee'])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return view('user.installments.receipt', compact('installment'));
    }

    public function viewInvoice($paymentId)
    {
        $payment = Payment::whereKey($paymentId)
            ->where('user_id', Auth::id())
            ->where('status', 'Active')
            ->firstOrFail();

        return $this->renderInvoice($payment);
    }

    public function generateInvoice(Request $request)
    {
        $payment = Payment::whereKey($request->payment_id)
            ->where('user_id', Auth::id())
            ->where('status', 'Active')
            ->firstOrFail();

        if ((int) $request->course_id !== (int) $payment->course_id) {
            abort(404);
        }

        return $this->renderInvoice($payment);
    }

    protected function renderInvoice(Payment $payment)
    {
        $installments = Installment::with(['payment.course', 'payment.courseFee', 'user'])
            ->where('user_id', Auth::id())
            ->where('payment_id', $payment->id)
            ->orderBy('installment_number')
            ->get();

        if ($installments->isEmpty()) {
            abort(404, 'No installments found for this payment.');
        }

        $user = $installments->first()->user;
        $course = $payment->course;
        $coursefee = $payment->courseFee;
        $payments = $payment;
        $totalPaidAmount = $installments->sum('paid_amount');
        $totalRemainingAmount = $installments->sum('remaining_amount');

        return view('user.installments.invoice', compact(
            'installments',
            'user',
            'course',
            'coursefee',
            'totalPaidAmount',
            'totalRemainingAmount',
            'payments'
        ));
    }
}
