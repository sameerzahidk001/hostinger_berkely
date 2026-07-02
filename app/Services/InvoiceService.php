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

        $emailTemplate = $this->resolveInvoiceEmailTemplate();

        $emailBody = str_replace(
            ['{name}', '{email}', '{password}', '{role}', '{invoice_no}'],
            [$user->name, $user->email, '', ucfirst($user->roles->first()->name ?? 'Student'), $invoiceNo],
            $emailTemplate->body ?? ''
        );

        $emailSubject = str_replace(
            ['{name}', '{email}', '{invoice_no}'],
            [$user->name, $user->email, $invoiceNo],
            $emailTemplate->subject ?? 'Invoice {invoice_no}'
        );

        $ccEmails = !empty($emailTemplate->cc) ? array_filter(array_map('trim', explode(',', $emailTemplate->cc))) : [];
        $bccEmails = !empty($emailTemplate->bcc) ? array_filter(array_map('trim', explode(',', $emailTemplate->bcc))) : [];

        try {
            $mailable = (new UserMail($user, $emailSubject, $emailBody))
                ->attachData($pdf->output(), $filename, ['mime' => 'application/pdf']);

            $mail = Mail::to($user->email);
            if ($ccEmails !== []) {
                $mail->cc($ccEmails);
            }
            if ($bccEmails !== []) {
                $mail->bcc($bccEmails);
            }
            $mail->send($mailable);
        } catch (\Throwable $e) {
            Log::error('Invoice email delivery failed', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);

            return false;
        }

        return true;
    }

    private function resolveInvoiceEmailTemplate(): Email
    {
        $template = Email::query()
            ->whereIn('name', ['send-invoice', 'Send Invoice', 'send_invoice'])
            ->first();

        if ($template) {
            return $template;
        }

        $template = Email::query()
            ->where('name', 'like', '%send%')
            ->where(function ($query) {
                $query->where('name', 'like', '%invoice%')
                    ->orWhere('subject', 'like', '%invoice%');
            })
            ->first();

        if ($template) {
            return $template;
        }

        Log::warning('Invoice email using built-in fallback template: send-invoice missing in Admin → Emails');

        $fallback = new Email();
        $fallback->subject = 'Invoice {invoice_no}';
        $fallback->body = '<p>Dear {name},</p><p>Please find attached your invoice <strong>{invoice_no}</strong>.</p><p>Regards,<br>Berkeley School of Business, Arts &amp; Sciences</p>';

        return $fallback;
    }
}
