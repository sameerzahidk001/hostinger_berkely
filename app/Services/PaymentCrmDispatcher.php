<?php

namespace App\Services;

use App\Models\Installment;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class PaymentCrmDispatcher
{
    public static function syncPayment(?Payment $payment): void
    {
        if (! $payment) {
            return;
        }

        try {
            app(ZohoCrmSyncService::class)->syncPayment($payment);
        } catch (\Throwable $e) {
            Log::warning('CRM payment dispatch skipped: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
            ]);
        }
    }

    public static function syncReceipt(?Installment $installment): void
    {
        if (! $installment) {
            return;
        }

        try {
            app(ZohoCrmSyncService::class)->upsertReceipt($installment);
        } catch (\Throwable $e) {
            Log::warning('CRM receipt dispatch skipped: ' . $e->getMessage(), [
                'installment_id' => $installment->id,
            ]);
        }
    }

    public static function cancelPayment(?Payment $payment): void
    {
        if (! $payment) {
            return;
        }

        try {
            app(ZohoCrmSyncService::class)->cancelInvoiceInCrm($payment);
        } catch (\Throwable $e) {
            Log::warning('CRM cancel dispatch skipped: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
            ]);
        }
    }
}
