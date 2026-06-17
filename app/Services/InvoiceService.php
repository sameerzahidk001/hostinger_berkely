<?php

namespace App\Services;

use App\Mail\UserMail;
use App\Models\Email;
use App\Models\InstallmentRequest;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class InvoiceService
{
    public function activateAndSend(Payment $payment): bool
    {
        if (!$this->sendInvoiceEmail($payment)) {
            return false;
        }

        $payment->status = 'Active';
        $payment->save();

        $installmentRequest = InstallmentRequest::where('payment_id', $payment->id)->first();
        if ($installmentRequest) {
            $installmentRequest->status = 1;
            $installmentRequest->save();
        }

        return true;
    }

    public function sendInvoiceEmail(Payment $payment): bool
    {
        $payment->loadMissing(['user.roles', 'course', 'courseFee', 'installments']);

        if (!$payment->user) {
            Log::warning('Invoice email skipped: payment has no user', ['payment_id' => $payment->id]);

            return false;
        }

        $payments = $payment;
        $installments = $payment->installments;
        $user = $payment->user;
        $course = $payment->course;
        $coursefee = $payment->courseFee;

        $invoiceNo = 'INV-' . str_pad($payments->id, 6, '0', STR_PAD_LEFT);

        try {
            $pdf = PDF::loadView('admin.payments.invoice-pdf', compact('payments', 'installments', 'user', 'course', 'coursefee'))
                ->setPaper('a4');
        } catch (\Throwable $e) {
            Log::error('Invoice PDF generation failed', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);

            return false;
        }

        $filename = $invoiceNo . '.pdf';

        $emailTemplate = Email::where('name', 'send-invoice')->first();
        if (!$emailTemplate) {
            Log::warning('Invoice email skipped: send-invoice template missing');

            return false;
        }

        $emailBody = str_replace(
            ['{name}', '{email}', '{password}', '{role}', '{invoice_no}'],
            [$user->name, $user->email, '', ucfirst($user->roles->first()->name ?? ''), $invoiceNo],
            $emailTemplate->body
        );

        $ccEmails = !empty($emailTemplate->cc) ? array_filter(array_map('trim', explode(',', $emailTemplate->cc))) : [];
        $bccEmails = !empty($emailTemplate->bcc) ? array_filter(array_map('trim', explode(',', $emailTemplate->bcc))) : [];

        try {
            Mail::to($user->email)
                ->cc($ccEmails)
                ->bcc($bccEmails)
                ->send(
                    (new UserMail($user, $emailTemplate->subject, $emailBody))
                        ->attachData($pdf->output(), $filename, ['mime' => 'application/pdf'])
                );
        } catch (\Throwable $e) {
            Log::error('Invoice email delivery failed', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);

            return false;
        }

        return true;
    }
}
