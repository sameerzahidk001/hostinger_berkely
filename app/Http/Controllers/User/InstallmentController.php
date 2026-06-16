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

    public function generateInvoice(Request $request)
    {
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

        return view('user.installments.invoice', compact('installments', 'user', 'course', 'coursefee', 'totalPaidAmount', 'totalRemainingAmount', 'payments'));
    }
}