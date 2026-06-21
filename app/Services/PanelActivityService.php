<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Page;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class PanelActivityService
{
    public function summary(
        ?int $userId,
        ?string $dateFrom,
        ?string $dateTo,
        bool $includePayments = false
    ): array {
        [$from, $to] = $this->parseDateRange($dateFrom, $dateTo);

        return [
            'my_courses' => $userId ? $this->countCoursesCreated($userId, null, null) : 0,
            'my_pages' => $userId ? $this->countPagesCreated($userId, null, null) : 0,
            'total_courses_site' => (int) Course::count(),
            'active_courses_site' => (int) Course::count(),
            'disabled_courses_site' => Schema::hasColumn('courses', 'deleted_at')
                ? (int) Course::onlyTrashed()->count()
                : 0,
            'total_pages_site' => (int) Page::count(),
            'active_pages_site' => Schema::hasColumn('pages', 'status')
                ? (int) Page::where('status', 1)->count()
                : (int) Page::count(),
            'disabled_pages_site' => Schema::hasColumn('pages', 'status')
                ? (int) Page::where('status', 0)->count()
                : 0,
            'total_payments_site' => $includePayments && Schema::hasTable('payments')
                ? (int) Payment::count()
                : 0,
            'courses_created' => $this->countCoursesCreated($userId, $from, $to),
            'pages_created' => $this->countPagesCreated($userId, $from, $to),
            'payments_recorded' => ($includePayments && $userId === null) ? $this->countPayments($from, $to) : 0,
            'invoices_recorded' => ($includePayments && $userId === null) ? $this->countPayments($from, $to) : 0,
        ] + ($includePayments ? $this->invoiceSummary() : [
            'invoice_total' => 0,
            'invoice_paid' => 0,
            'invoice_partial' => 0,
            'invoice_unpaid' => 0,
        ]);
    }

    public function invoiceSummary(): array
    {
        if (! Schema::hasTable('payments')) {
            return [
                'invoice_total' => 0,
                'invoice_paid' => 0,
                'invoice_partial' => 0,
                'invoice_unpaid' => 0,
            ];
        }

        $paid = 0;
        $partial = 0;
        $unpaid = 0;

        Payment::query()
            ->with('installments')
            ->get()
            ->each(function (Payment $payment) use (&$paid, &$partial, &$unpaid) {
                $totalPaid = (float) $payment->installments->sum('paid_amount');
                $price = (float) $payment->price;

                if ($totalPaid >= $price && $price > 0) {
                    $paid++;
                } elseif ($totalPaid > 0) {
                    $partial++;
                } else {
                    $unpaid++;
                }
            });

        return [
            'invoice_total' => $paid + $partial + $unpaid,
            'invoice_paid' => $paid,
            'invoice_partial' => $partial,
            'invoice_unpaid' => $unpaid,
        ];
    }

    public function paginatedFeed(
        ?int $userId,
        ?string $dateFrom,
        ?string $dateTo,
        bool $includePayments,
        int $perPage = 15,
        int $page = 1,
        string $path = '',
        array $query = [],
        ?array $restrictToUserIds = null,
        bool $paymentsOnly = false
    ): LengthAwarePaginator {
        $activities = $this->buildFeed(
            $userId,
            $dateFrom,
            $dateTo,
            $includePayments,
            $restrictToUserIds,
            $paymentsOnly
        );
        $total = $activities->count();
        $items = $activities->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => $path, 'query' => $query]
        );
    }

    public function paginatedFeedWithLogs(
        ?int $userId,
        ?string $dateFrom,
        ?string $dateTo,
        bool $includePayments,
        string $logAudience,
        int $perPage = 15,
        int $page = 1,
        string $path = '',
        array $query = [],
        ?array $restrictToUserIds = null,
        bool $paymentsOnly = false
    ): LengthAwarePaginator {
        $activities = $this->buildFeed(
            $userId,
            $dateFrom,
            $dateTo,
            $includePayments,
            $restrictToUserIds,
            $paymentsOnly
        );

        $logService = app(UserActivityLogService::class);
        $logs = $logService->buildFeed($logAudience, $userId, $dateFrom, $dateTo);

        if ($restrictToUserIds !== null) {
            $logs = $logs->filter(function (array $row) use ($restrictToUserIds) {
                if (empty($row['actor_id'])) {
                    return false;
                }

                return in_array((int) $row['actor_id'], $restrictToUserIds, true);
            });
        }

        if ($logService->tableExists()) {
            $activities = $activities->filter(function (array $row) {
                return ! in_array($row['action'], ['Page Updated', 'Course Updated'], true);
            });
        }

        $merged = $activities
            ->merge($logs)
            ->sortByDesc(fn (array $row) => $row['occurred_at']->timestamp)
            ->values();

        $total = $merged->count();
        $items = $merged->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => $path, 'query' => $query]
        );
    }

    public function buildFeed(
        ?int $userId,
        ?string $dateFrom,
        ?string $dateTo,
        bool $includePayments = false,
        ?array $restrictToUserIds = null,
        bool $paymentsOnly = false
    ): Collection {
        [$from, $to] = $this->parseDateRange($dateFrom, $dateTo);

        if ($paymentsOnly) {
            return $this->invoiceActivities($from, $to)
                ->merge($this->installmentPaymentActivities($from, $to))
                ->sortByDesc(fn (array $row) => $row['occurred_at']->timestamp)
                ->values();
        }

        $feed = $this->courseActivities($userId, $from, $to)
            ->merge($this->pageActivities($userId, $from, $to));

        if ($includePayments && $userId === null && $restrictToUserIds === null) {
            $feed = $feed
                ->merge($this->invoiceActivities($from, $to))
                ->merge($this->installmentPaymentActivities($from, $to));
        }

        if ($restrictToUserIds !== null) {
            $feed = $feed->filter(function (array $row) use ($restrictToUserIds) {
                if (empty($row['actor_id'])) {
                    return false;
                }

                return in_array((int) $row['actor_id'], $restrictToUserIds, true);
            });
        }

        return $feed
            ->sortByDesc(fn (array $row) => $row['occurred_at']->timestamp)
            ->values();
    }

    public function userIdsForRole(?string $role): ?array
    {
        if (! $role) {
            return null;
        }

        $normalized = normalize_role_key($role);

        if ($normalized === 'content_writer') {
            $ids = content_writer_role_ids();

            return User::query()
                ->whereHas('roles', fn ($q) => $q->whereIn('role_id', $ids))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return User::query()
            ->whereHas('roles', function ($q) use ($normalized) {
                $q->whereIn('name', match ($normalized) {
                    'accountant' => ['accountant', 'Accountant'],
                    'instructor' => ['instructor', 'Instructor'],
                    default => [$role],
                });
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function filterUsers(): Collection
    {
        return User::query()
            ->with('roles')
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', [
                    'content_writer', 'Content Writer', 'librarian', 'content-writer',
                    'accountant', 'Accountant',
                    'instructor', 'Instructor',
                ]);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    private function courseActivities(?int $userId, ?Carbon $from, ?Carbon $to): Collection
    {
        if (! Schema::hasColumn('courses', 'created_by')) {
            return collect();
        }

        $query = Course::query()->withTrashed();

        if ($userId) {
            $query->where(function ($q) use ($userId) {
                $q->where('created_by', $userId)->orWhere('updated_by', $userId);
            });
        }

        return $query->get()->flatMap(function (Course $course) use ($userId, $from, $to) {
            $rows = collect();

            if ((! $userId || (int) $course->created_by === $userId)
                && $this->inRange($course->created_at, $from, $to)) {
                $rows->push($this->activityRow(
                    'Course Created',
                    $course->title ?: 'Course #' . $course->id,
                    route('course.edit', $course->id),
                    $course->created_by,
                    $course->created_at
                ));
            }

            if ($this->wasUpdated($course->created_at, $course->updated_at)
                && (! $userId || (int) $course->updated_by === $userId)
                && $this->inRange($course->updated_at, $from, $to)) {
                $rows->push($this->activityRow(
                    'Course Updated',
                    $course->title ?: 'Course #' . $course->id,
                    route('course.edit', $course->id),
                    $course->updated_by ?: $course->created_by,
                    $course->updated_at
                ));
            }

            return $rows;
        });
    }

    private function pageActivities(?int $userId, ?Carbon $from, ?Carbon $to): Collection
    {
        if (! Schema::hasColumn('pages', 'created_by')) {
            return collect();
        }

        $query = Page::query();

        if ($userId) {
            $query->where(function ($q) use ($userId) {
                $q->where('created_by', $userId)->orWhere('updated_by', $userId);
            });
        }

        return $query->get()->flatMap(function (Page $page) use ($userId, $from, $to) {
            $rows = collect();
            $label = $page->page_name ?: $page->url;

            if ((! $userId || (int) $page->created_by === $userId)
                && $this->inRange($page->created_at, $from, $to)) {
                $rows->push($this->activityRow(
                    'Page Created',
                    $label,
                    route('pages.edit', $page->id),
                    $page->created_by,
                    $page->created_at
                ));
            }

            if ($this->wasUpdated($page->created_at, $page->updated_at)
                && (! $userId || (int) $page->updated_by === $userId)
                && $this->inRange($page->updated_at, $from, $to)) {
                $rows->push($this->activityRow(
                    'Page Updated',
                    $label,
                    route('pages.edit', $page->id),
                    $page->updated_by ?: $page->created_by,
                    $page->updated_at
                ));
            }

            return $rows;
        });
    }

    private function invoiceActivities(?Carbon $from, ?Carbon $to): Collection
    {
        if (! Schema::hasTable('payments')) {
            return collect();
        }

        return Payment::query()
            ->with(['user', 'course'])
            ->orderByDesc('created_at')
            ->get()
            ->filter(fn (Payment $payment) => $this->inRange($payment->created_at, $from, $to))
            ->map(function (Payment $payment) {
                $student = $payment->user?->name ?? $payment->user?->email ?? 'Student';
                $course = $payment->course?->title ?? 'Course #' . $payment->course_id;

                return $this->activityRow(
                    'Invoice Recorded',
                    $course . ' — ' . $student,
                    route('admin.payments.index'),
                    null,
                    $payment->created_at
                );
            });
    }

    private function installmentPaymentActivities(?Carbon $from, ?Carbon $to): Collection
    {
        if (! Schema::hasTable('installments')) {
            return collect();
        }

        return \App\Models\Installment::query()
            ->with(['user', 'payment.course'])
            ->where('status', 'paid')
            ->where('paid_amount', '>', 0)
            ->orderByDesc('paid_date')
            ->orderByDesc('updated_at')
            ->get()
            ->filter(function ($installment) use ($from, $to) {
                $at = $installment->paid_date ?? $installment->updated_at;

                return $this->inRange($at, $from, $to);
            })
            ->map(function ($installment) {
                $student = $installment->user?->name ?? $installment->user?->email ?? 'Student';
                $course = $installment->payment?->course?->title ?? 'Course';
                $invoiceNo = 'INV-' . str_pad((string) $installment->payment_id, 6, '0', STR_PAD_LEFT);

                return $this->activityRow(
                    'Payment Completed',
                    $course . ' — ' . $student . ' (' . $invoiceNo . ')',
                    route('admin.payments.index'),
                    null,
                    $installment->paid_date ?? $installment->updated_at
                );
            });
    }

    private function countCoursesCreated(?int $userId, ?Carbon $from, ?Carbon $to): int
    {
        if (! Schema::hasColumn('courses', 'created_by')) {
            return 0;
        }

        $query = Course::query()->withTrashed();

        if ($userId) {
            $query->where('created_by', $userId);
        }

        if ($from) {
            $query->where('created_at', '>=', $from);
        }

        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        return (int) $query->count();
    }

    private function countPagesCreated(?int $userId, ?Carbon $from, ?Carbon $to): int
    {
        if (! Schema::hasColumn('pages', 'created_by')) {
            return 0;
        }

        $query = Page::query();

        if ($userId) {
            $query->where('created_by', $userId);
        }

        if ($from) {
            $query->where('created_at', '>=', $from);
        }

        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        return (int) $query->count();
    }

    private function countPayments(?Carbon $from, ?Carbon $to): int
    {
        if (! Schema::hasTable('payments')) {
            return 0;
        }

        $query = Payment::query();

        if ($from) {
            $query->where('created_at', '>=', $from);
        }

        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        return (int) $query->count();
    }

    private function activityRow(
        string $action,
        string $item,
        string $url,
        ?int $actorId,
        $occurredAt
    ): array {
        return [
            'action' => $action,
            'item' => $item,
            'url' => $url,
            'session_id' => null,
            'actor_id' => $actorId ? (int) $actorId : null,
            'user_name' => audit_user_name(null, $actorId),
            'occurred_at' => Carbon::parse($occurredAt),
        ];
    }

    private function parseDateRange(?string $dateFrom, ?string $dateTo): array
    {
        $from = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : null;
        $to = $dateTo ? Carbon::parse($dateTo)->endOfDay() : null;

        return [$from, $to];
    }

    private function inRange($date, ?Carbon $from, ?Carbon $to): bool
    {
        if (! $date) {
            return false;
        }

        $at = Carbon::parse($date);

        if ($from && $at->lt($from)) {
            return false;
        }

        if ($to && $at->gt($to)) {
            return false;
        }

        return true;
    }

    private function wasUpdated($createdAt, $updatedAt): bool
    {
        if (! $createdAt || ! $updatedAt) {
            return false;
        }

        return Carbon::parse($updatedAt)->diffInSeconds(Carbon::parse($createdAt)) > 5;
    }
}
