<?php

namespace App\Services;

use App\Models\Installment;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AccountantDashboardService
{
    public function overviewStats(): array
    {
        $invoice = app(PanelActivityService::class)->invoiceSummary();
        $receipts = Schema::hasTable('installments')
            ? (int) Installment::query()->where('status', 'paid')->where('paid_amount', '>', 0)->count()
            : 0;

        return [
            'invoice_total' => (int) ($invoice['invoice_total'] ?? 0),
            'invoice_unpaid' => (int) ($invoice['invoice_unpaid'] ?? 0),
            'invoice_partial' => (int) ($invoice['invoice_partial'] ?? 0),
            'invoice_paid' => (int) ($invoice['invoice_paid'] ?? 0),
            'receipts_total' => $receipts,
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function invoiceRows(?Carbon $from, ?Carbon $to): Collection
    {
        if (! Schema::hasTable('payments')) {
            return collect();
        }

        $query = Payment::query()
            ->with(['user', 'course', 'courseFee', 'installments'])
            ->orderByDesc('created_at');

        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        return $query->limit(5000)->get()->map(function (Payment $payment) {
            $status = payment_invoice_status($payment);
            $amount = format_payment_amount($payment);
            $installmentCount = max(1, (int) $payment->installments->count());
            $paidInstallments = (int) $payment->installments
                ->filter(function ($installment) {
                    return (float) $installment->paid_amount > 0
                        || in_array($installment->status, ['paid', 'partial'], true);
                })
                ->count();

            return [
                'id' => $payment->id,
                'student' => $payment->user?->name ?: ($payment->user?->email ?: '—'),
                'course' => $payment->course?->title ?: ('Course #' . $payment->course_id),
                'package' => $payment->courseFee?->package_name ?: '—',
                'amount' => $amount['display'] ?? ('AED ' . number_format((float) ($payment->price ?? 0), 2)),
                'installments' => $installmentCount === 1
                    ? '1 installment'
                    : $installmentCount . ' installments (' . $paidInstallments . ' with receipt)',
                'status' => $status,
                'invoice_status' => $payment->status ?: '—',
                'created_at' => optional($payment->created_at)?->format('Y-m-d H:i'),
                'url' => route('admin.payments.edit', $payment->id),
            ];
        });
    }

    public function rangeForTab(string $tab, ?string $dateFrom, ?string $dateTo): array
    {
        return match ($tab) {
            'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
            'last_month' => [
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth(),
            ],
            'this_year' => [now()->startOfYear(), now()->endOfYear()],
            'custom' => [
                $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : null,
                $dateTo ? Carbon::parse($dateTo)->endOfDay() : null,
            ],
            default => [null, null],
        };
    }
}
