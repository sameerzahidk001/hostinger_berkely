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

        [$thisMonthFrom, $thisMonthTo] = $this->rangeForTab('this_month', null, null);
        [$lastMonthFrom, $lastMonthTo] = $this->rangeForTab('last_month', null, null);
        [$thisYearFrom, $thisYearTo] = $this->rangeForTab('this_year', null, null);

        return [
            'invoice_total' => (int) ($invoice['invoice_total'] ?? 0),
            'invoice_unpaid' => (int) ($invoice['invoice_unpaid'] ?? 0),
            'invoice_partial' => (int) ($invoice['invoice_partial'] ?? 0),
            'invoice_paid' => (int) ($invoice['invoice_paid'] ?? 0),
            'receipts_total' => $receipts,
            'sales_this_month' => $this->salesTotalAed($thisMonthFrom, $thisMonthTo),
            'sales_last_month' => $this->salesTotalAed($lastMonthFrom, $lastMonthTo),
            'sales_this_year' => $this->salesTotalAed($thisYearFrom, $thisYearTo),
        ];
    }

    public function salesTotalAed(?Carbon $from, ?Carbon $to): float
    {
        if (! Schema::hasTable('installments')) {
            return 0.0;
        }

        return round((float) $this->paidSalesQuery($from, $to)->sum('paid_amount'), 2);
    }

    /**
     * Sales rows = paid installment amounts only (unpaid invoices/installments excluded).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function salesRows(?Carbon $from, ?Carbon $to): Collection
    {
        if (! Schema::hasTable('installments')) {
            return collect();
        }

        return $this->paidSalesQuery($from, $to)
            ->with([
                'user',
                'payment.course',
                'payment.courseFee',
                'payment.page',
                'payment.pageFeeService',
                'payment.pageFeePackage',
            ])
            ->orderByDesc('paid_date')
            ->orderByDesc('id')
            ->limit(5000)
            ->get()
            ->map(function (Installment $installment) {
                $payment = $installment->payment;
                $paidAed = (float) $installment->paid_amount;
                $currency = $payment ? payment_display_currency($payment) : 'AED';
                $displayAmount = $payment
                    ? payment_display_amount_from_aed($payment, $paidAed)
                    : $paidAed;
                $installmentNo = (int) ($installment->installment_number ?: 1);
                $totalInstallments = max(1, (int) ($payment?->total_installment ?: 1));

                $receiptDate = $this->formatSaleDate($installment->paid_date ?? $installment->created_at);
                $invoiceNo = $payment
                    ? ('INV-' . str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT))
                    : '—';
                $receiptNo = 'RC-' . str_pad((string) $installment->id, 6, '0', STR_PAD_LEFT);

                return [
                    'id' => $installment->id,
                    'payment_id' => $payment?->id,
                    'receipt_date' => $receiptDate,
                    'receipt_no' => $receiptNo,
                    'invoice_no' => $invoiceNo,
                    'student' => $installment->user?->name
                        ?: ($installment->user?->email ?: ($payment?->user?->name ?: '—')),
                    'course' => $payment
                        ? (method_exists($payment, 'programTitle')
                            ? $payment->programTitle()
                            : ($payment->course?->title ?: ('Course #' . ($payment->course_id ?: '—'))))
                        : '—',
                    'package' => $payment?->courseFee?->package_name
                        ?: ($payment?->pageFeePackage?->package_name ?: '—'),
                    'amount' => $currency . ' ' . number_format((float) $displayAmount, 2),
                    'amount_aed' => round($paidAed, 2),
                    'installments' => 'Installment ' . $installmentNo . ' of ' . $totalInstallments,
                    'status' => ucfirst((string) ($installment->status ?: 'paid')),
                    'invoice_status' => $payment?->status ?: '—',
                    'created_at' => $receiptDate,
                    'url' => $payment ? route('admin.payments.edit', $payment->id) : null,
                ];
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function invoiceRows(?Carbon $from, ?Carbon $to): Collection
    {
        return $this->salesRows($from, $to);
    }

    private function formatSaleDate(mixed $value): string
    {
        if (! $value) {
            return '—';
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    private function paidSalesQuery(?Carbon $from, ?Carbon $to)
    {
        // Sales = fully paid installment amounts only (pending/partial unpaid rows excluded).
        $query = Installment::query()
            ->where('paid_amount', '>', 0)
            ->where(function ($q) {
                $q->where('status', 'paid')
                    ->orWhere(function ($inner) {
                        $inner->where('remaining_amount', '<=', 0)
                            ->where('paid_amount', '>', 0);
                    });
            });

        // Use paid_date when present; otherwise fall back to created_at.
        $saleDate = 'COALESCE(paid_date, DATE(created_at))';

        if ($from) {
            $query->whereRaw("{$saleDate} >= ?", [$from->toDateString()]);
        }
        if ($to) {
            $query->whereRaw("{$saleDate} <= ?", [$to->toDateString()]);
        }

        return $query;
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
