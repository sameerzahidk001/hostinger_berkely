<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class StudentsDashboardService
{
    public function overviewStats(): array
    {
        $ranges = $this->registrationRanges();
        $registration = [];
        foreach ($ranges as $key => $range) {
            $registration[$key] = $this->registrationQuery($range['from'] ?? null, $range['to'] ?? null)->count();
        }

        $enrollment = $this->enrollmentBuckets();

        return [
            'registration' => $registration,
            'enrollment' => [
                'enrolled_total' => $enrollment['enrolled_total']->count(),
                'enrolled_paid' => $enrollment['enrolled_paid']->count(),
                'enrolled_partial' => $enrollment['enrolled_partial']->count(),
                'invoice_unpaid_students' => $enrollment['invoice_unpaid_students']->count(),
                'partially_paid_students' => $enrollment['partially_paid_students']->count(),
            ],
        ];
    }

    /**
     * Person rows for Students Overview metric popups.
     *
     * @return array<string, array{title: string, people: array<int, array<string, mixed>>}>
     */
    public function overviewDetails(): array
    {
        $ranges = $this->registrationRanges();
        $details = [];

        foreach ($ranges as $key => $range) {
            $people = $this->registrationQuery($range['from'] ?? null, $range['to'] ?? null)
                ->orderByDesc('created_at')
                ->limit(500)
                ->get(['id', 'name', 'email', 'created_at'])
                ->map(fn (User $user) => $this->personRow($user, 'Registered: ' . optional($user->created_at)->format('Y-m-d H:i')))
                ->values()
                ->all();

            $details['reg_' . $key] = [
                'title' => $range['title'],
                'people' => $people,
            ];
        }

        $enrollment = $this->enrollmentBuckets();
        $map = [
            'enrolled_total' => 'Enrolled Total Students',
            'enrolled_paid' => 'Invoice Fully Paid',
            'partially_paid_students' => 'Partially Paid Students',
            'invoice_unpaid_students' => 'Invoice Issued And Unpaid',
        ];

        foreach ($map as $key => $title) {
            $ids = $enrollment[$key] ?? collect();
            $people = User::query()
                ->whereIn('id', $ids->take(500)->all())
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'created_at'])
                ->map(fn (User $user) => $this->personRow($user, $title))
                ->values()
                ->all();

            $details['enr_' . $key] = [
                'title' => $title,
                'people' => $people,
            ];
        }

        return $details;
    }

    private function registrationRanges(): array
    {
        $now = now();

        return [
            'total' => [
                'title' => 'Total Registered',
                'from' => null,
                'to' => null,
            ],
            'today' => [
                'title' => 'Registered Today',
                'from' => $now->copy()->startOfDay(),
                'to' => null,
            ],
            'yesterday' => [
                'title' => 'Registered Yesterday',
                'from' => $now->copy()->subDay()->startOfDay(),
                'to' => $now->copy()->subDay()->endOfDay(),
            ],
            'this_week' => [
                'title' => 'Registered This Week',
                'from' => $now->copy()->startOfWeek(Carbon::MONDAY),
                'to' => null,
            ],
            'last_week' => [
                'title' => 'Registered Last Week',
                'from' => $now->copy()->subWeek()->startOfWeek(Carbon::MONDAY),
                'to' => $now->copy()->subWeek()->endOfWeek(Carbon::SUNDAY),
            ],
            'this_month' => [
                'title' => 'Registered This Month',
                'from' => $now->copy()->startOfMonth(),
                'to' => null,
            ],
            'last_month' => [
                'title' => 'Registered Last Month',
                'from' => $now->copy()->subMonthNoOverflow()->startOfMonth(),
                'to' => $now->copy()->subMonthNoOverflow()->endOfMonth(),
            ],
            'this_year' => [
                'title' => 'Registered This Year',
                'from' => $now->copy()->startOfYear(),
                'to' => null,
            ],
        ];
    }

    private function registrationQuery(?Carbon $from, ?Carbon $to)
    {
        $query = $this->studentQuery();

        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        return $query;
    }

    private function studentQuery()
    {
        return User::query()->whereHas('roles', function ($q) {
            $q->whereRaw('LOWER(name) = ?', ['student']);
        });
    }

    /**
     * @return array<string, Collection<int, int>>
     */
    private function enrollmentBuckets(): array
    {
        $empty = [
            'enrolled_total' => collect(),
            'enrolled_paid' => collect(),
            'enrolled_partial' => collect(),
            'invoice_unpaid_students' => collect(),
            'partially_paid_students' => collect(),
        ];

        if (! Schema::hasTable('payments')) {
            return $empty;
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

        $enrolledPaid = collect();
        $enrolledPartial = collect();
        $unpaidStudents = collect();
        $partialStudents = collect();

        foreach ($byStudent as $userId => $flags) {
            if ($flags['paid'] || $flags['partial']) {
                if ($flags['paid']) {
                    $enrolledPaid->push($userId);
                }
                if ($flags['partial']) {
                    $enrolledPartial->push($userId);
                    $partialStudents->push($userId);
                }
            }
            if ($flags['unpaid']) {
                $unpaidStudents->push($userId);
            }
        }

        return [
            'enrolled_total' => $enrolledPaid->merge($enrolledPartial)->unique()->values(),
            'enrolled_paid' => $enrolledPaid->unique()->values(),
            'enrolled_partial' => $enrolledPartial->unique()->values(),
            'invoice_unpaid_students' => $unpaidStudents->unique()->values(),
            'partially_paid_students' => $partialStudents->unique()->values(),
        ];
    }

    private function personRow(User $user, string $note): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name ?: '—',
            'email' => $user->email ?: '—',
            'note' => $note,
            'url' => route('users.edit', $user->id),
        ];
    }
}
