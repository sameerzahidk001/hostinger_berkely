<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class StudentsDashboardService
{
    public function overviewStats(): array
    {
        $students = $this->studentQuery();

        $now = now();
        $todayStart = $now->copy()->startOfDay();
        $yesterdayStart = $now->copy()->subDay()->startOfDay();
        $yesterdayEnd = $now->copy()->subDay()->endOfDay();
        $weekStart = $now->copy()->startOfWeek(Carbon::MONDAY);
        $lastWeekStart = $now->copy()->subWeek()->startOfWeek(Carbon::MONDAY);
        $lastWeekEnd = $now->copy()->subWeek()->endOfWeek(Carbon::SUNDAY);
        $monthStart = $now->copy()->startOfMonth();
        $lastMonthStart = $now->copy()->subMonthNoOverflow()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonthNoOverflow()->endOfMonth();
        $yearStart = $now->copy()->startOfYear();

        $registration = [
            'total' => (clone $students)->count(),
            'today' => (clone $students)->where('created_at', '>=', $todayStart)->count(),
            'yesterday' => (clone $students)->whereBetween('created_at', [$yesterdayStart, $yesterdayEnd])->count(),
            'this_week' => (clone $students)->where('created_at', '>=', $weekStart)->count(),
            'last_week' => (clone $students)->whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])->count(),
            'this_month' => (clone $students)->where('created_at', '>=', $monthStart)->count(),
            'last_month' => (clone $students)->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count(),
            'this_year' => (clone $students)->where('created_at', '>=', $yearStart)->count(),
        ];

        $enrollment = $this->enrollmentStats();

        return [
            'registration' => $registration,
            'enrollment' => $enrollment,
        ];
    }

    private function studentQuery()
    {
        return User::query()->whereHas('roles', function ($q) {
            $q->whereRaw('LOWER(name) = ?', ['student']);
        });
    }

    private function enrollmentStats(): array
    {
        if (! Schema::hasTable('payments')) {
            return [
                'enrolled_total' => 0,
                'enrolled_paid' => 0,
                'enrolled_partial' => 0,
                'invoice_unpaid_students' => 0,
                'partially_paid_students' => 0,
            ];
        }

        $byStudent = [];

        Payment::query()
            ->with('installments')
            ->whereNotNull('user_id')
            ->get(['id', 'user_id', 'price'])
            ->each(function (Payment $payment) use (&$byStudent) {
                $userId = (int) $payment->user_id;
                if ($userId <= 0) {
                    return;
                }

                $status = payment_invoice_status($payment);
                if (! isset($byStudent[$userId])) {
                    $byStudent[$userId] = [
                        'paid' => false,
                        'partial' => false,
                        'unpaid' => false,
                    ];
                }

                if ($status === 'Paid') {
                    $byStudent[$userId]['paid'] = true;
                } elseif ($status === 'Partial') {
                    $byStudent[$userId]['partial'] = true;
                } else {
                    $byStudent[$userId]['unpaid'] = true;
                }
            });

        $enrolledPaid = 0;
        $enrolledPartial = 0;
        $unpaidStudents = 0;
        $partialStudents = 0;

        foreach ($byStudent as $flags) {
            if ($flags['paid'] || $flags['partial']) {
                if ($flags['paid']) {
                    $enrolledPaid++;
                }
                if ($flags['partial']) {
                    $enrolledPartial++;
                    $partialStudents++;
                }
            }
            if ($flags['unpaid'] && ! $flags['paid'] && ! $flags['partial']) {
                $unpaidStudents++;
            } elseif ($flags['unpaid']) {
                // Student has unpaid invoice(s) even if another invoice is paid/partial.
                $unpaidStudents++;
            }
        }

        return [
            'enrolled_total' => $enrolledPaid + $enrolledPartial,
            'enrolled_paid' => $enrolledPaid,
            'enrolled_partial' => $enrolledPartial,
            'invoice_unpaid_students' => $unpaidStudents,
            'partially_paid_students' => $partialStudents,
        ];
    }
}
