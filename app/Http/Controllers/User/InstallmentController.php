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

class InstallmentController extends Controller
{
    // public function index(){
    //     $installments = Installment::with('payment')->where('user_id', Auth::id())
    //     ->whereHas('payment', function($query) {
    //     $query->where('status', 'Active');
    //     })
    //     ->get();

    //     return view('user.installments.index', compact('installments'));
    // }

    public function updateInstallment(Request $request)
    {
        $installment = Installment::findOrFail($request->installment_id);

        $installment->update([
            'paid_amount' => $request->amount,
            'remaining_amount' => 0,
            'paid_date' => now(),
            'status' => 'paid'
        ]);

        $user = Auth::user();
        $emailTemplate = Email::where('name', 'fees-paid')->first();

        if ($emailTemplate) {
            // Replace placeholders in the email body
            $emailBody = str_replace(
                ['{name}', '{email}', '{fees-paid}', '{fees-installment}'],
                [$user->name, $user->email, $request->amount, $installment->installment_number],
                $emailTemplate->body
            );

            $ccEmails = $emailTemplate->cc ? array_filter(array_map('trim', explode(',', $emailTemplate->cc))) : [];
            $bccEmails = $emailTemplate->bcc ? array_filter(array_map('trim', explode(',', $emailTemplate->bcc))) : [];

            Mail::to($user->email)
                ->cc($ccEmails)
                ->bcc($bccEmails)
                ->send(new UserMail($user, $emailTemplate->subject, $emailBody));
        }

        return response()->json(["success" => true, "message" => "Installment updated and email sent successfully."]);
    }

    public function receipt($id)
    {
        $installment = Installment::with(['user', 'payment.course', 'payment.courseFee'])->findOrFail($id);

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