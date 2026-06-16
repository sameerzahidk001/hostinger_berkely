<?php

namespace App\Services;

use App\Mail\UserMail;
use App\Models\Email;
use App\Models\InstallmentRequest;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Facades\Mail;

class InvoiceService
{
    public function activateAndSend(Payment $payment): bool
    {
        $payment->status = 'Active';
        $payment->save();

        $installmentRequest = InstallmentRequest::where('payment_id', $payment->id)->first();
        if ($installmentRequest) {
            $installmentRequest->status = 1;
            $installmentRequest->save();
        }

        return $this->sendInvoiceEmail($payment);
    }

    public function sendInvoiceEmail(Payment $payment): bool
    {
        $payment->loadMissing(['user.roles', 'course', 'courseFee', 'installments']);

        if (!$payment->user) {
            return false;
        }

        $payments = $payment;
        $installments = $payment->installments;
        $user = $payment->user;
        $course = $payment->course;
        $coursefee = $payment->courseFee;

        $pdf = PDF::loadView('admin.payments.invoice-pdf', compact('payments', 'installments', 'user', 'course', 'coursefee'))
            ->setPaper('a4');

        $filename = 'INV-' . str_pad($payments->id, 6, '0', STR_PAD_LEFT) . '.pdf';

        $emailTemplate = Email::where('name', 'send-invoice')->first();
        if (!$emailTemplate) {
            return false;
        }

        $emailBody = str_replace(
            ['{name}', '{email}', '{password}', '{role}'],
            [$user->name, $user->email, '', ucfirst($user->roles->first()->name ?? '')],
            $emailTemplate->body
        );

        $ccEmails = !empty($emailTemplate->cc) ? array_filter(array_map('trim', explode(',', $emailTemplate->cc))) : [];
        $bccEmails = !empty($emailTemplate->bcc) ? array_filter(array_map('trim', explode(',', $emailTemplate->bcc))) : [];

        Mail::to($user->email)
            ->cc($ccEmails)
            ->bcc($bccEmails)
            ->send(
                (new UserMail($user, $emailTemplate->subject, $emailBody))
                    ->attachData($pdf->output(), $filename, ['mime' => 'application/pdf'])
            );

        return true;
    }
}
