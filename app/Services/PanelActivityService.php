<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseAgenda;
use App\Models\Page;
use App\Models\PagesSEO;
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
            'active_pages_site' => pages_status_enabled()
                ? (int) Page::where('status', 1)->count()
                : (int) Page::count(),
            'disabled_pages_site' => pages_status_enabled()
                ? (int) Page::where('status', 0)->count()
                : 0,
            'pages_status_enabled' => pages_status_enabled(),
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

        // Keep legacy panel activities even when logs exist.
        // On some servers role/user filtering can hide log rows (admin_id vs user_id),
        // which previously made "Page Updated" / "Course Updated" disappear entirely.

        $merged = $this->mergeActivityRows(
            $this->supportRows($activities),
            $this->supportRows($logs)
        );

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
        bool $paymentsOnly = false,
        ?int $limit = null
    ): Collection {
        [$from, $to] = $this->parseDateRange($dateFrom, $dateTo);
        $sourceLimit = ($limit !== null && $limit > 0 && ! $from && ! $to)
            ? max(500, (int) ceil($limit / 2))
            : null;

        if ($paymentsOnly) {
            $feed = $this->concatActivityRows(
                $this->invoiceActivities($from, $to, $sourceLimit),
                $this->installmentPaymentActivities($from, $to, $sourceLimit)
            )->sortByDesc(fn (array $row) => $row['occurred_at']->timestamp)
                ->values();

            return $limit ? $feed->take($limit)->values() : $feed;
        }

        $feed = $this->concatActivityRows(
            $this->courseActivities($userId, $from, $to, $sourceLimit),
            $this->pageActivities($userId, $from, $to, $sourceLimit),
            $this->seoActivities($userId, $from, $to, $sourceLimit),
            $this->trainingCalendarActivities($userId, $from, $to, $sourceLimit)
        );

        if ($includePayments && $userId === null && $restrictToUserIds === null) {
            $feed = $this->concatActivityRows(
                $feed,
                $this->invoiceActivities($from, $to, $sourceLimit),
                $this->installmentPaymentActivities($from, $to, $sourceLimit)
            );
        }

        if ($restrictToUserIds !== null) {
            $feed = $feed->filter(function (array $row) use ($restrictToUserIds) {
                if (empty($row['actor_id'])) {
                    return false;
                }

                return in_array((int) $row['actor_id'], $restrictToUserIds, true);
            });
        }

        $feed = $feed
            ->sortByDesc(fn (array $row) => $row['occurred_at']->timestamp)
            ->values();

        return $limit ? $feed->take($limit)->values() : $feed;
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

    public function filterUsers(?string $roleScope = null): Collection
    {
        $normalized = $roleScope ? normalize_role_key($roleScope) : null;

        $roleNames = match ($normalized) {
            'content_writer' => [
                'content_writer', 'Content Writer', 'librarian', 'content-writer',
            ],
            'instructor' => ['instructor', 'Instructor'],
            'accountant' => ['accountant', 'Accountant'],
            default => [
                'content_writer', 'Content Writer', 'librarian', 'content-writer',
                'accountant', 'Accountant',
                'instructor', 'Instructor',
            ],
        };

        return User::query()
            ->with('roles')
            ->whereHas('roles', function ($q) use ($roleNames) {
                $q->whereIn('name', $roleNames);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    private function courseActivities(?int $userId, ?Carbon $from, ?Carbon $to, ?int $limit = null): Collection
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

        if ($limit) {
            $query->orderByDesc('updated_at')->limit($limit);
        }

        return $this->supportRows($query->get()->flatMap(function (Course $course) use ($userId, $from, $to) {
            $rows = collect();

            $visitUrl = empty($course->deleted_at) ? course_details_url($course) : null;
            $editUrl = route('course.edit', $course->id);

            if ((! $userId || (int) $course->created_by === $userId)
                && $this->inRange($course->created_at, $from, $to)) {
                $rows->push($this->activityRow(
                    'Course Created',
                    $course->title ?: 'Course #' . $course->id,
                    $editUrl,
                    $course->created_by,
                    $course->created_at,
                    $visitUrl
                ));
            }

            if ($this->wasUpdated($course->created_at, $course->updated_at)
                && (! $userId || (int) $course->updated_by === $userId)
                && $this->inRange($course->updated_at, $from, $to)) {
                $rows->push($this->activityRow(
                    'Course Updated',
                    $course->title ?: 'Course #' . $course->id,
                    $editUrl,
                    $course->updated_by ?: $course->created_by,
                    $course->updated_at,
                    $visitUrl
                ));
            }

            return $rows;
        }));
    }

    private function pageActivities(?int $userId, ?Carbon $from, ?Carbon $to, ?int $limit = null): Collection
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

        if ($limit) {
            $query->orderByDesc('updated_at')->limit($limit);
        }

        return $this->supportRows($query->get()->flatMap(function (Page $page) use ($userId, $from, $to) {
            $rows = collect();
            $label = $page->page_name ?: $page->url;

            $visitUrl = page_public_url($page);
            $editUrl = route('pages.edit', $page->id);

            if ((! $userId || (int) $page->created_by === $userId)
                && $this->inRange($page->created_at, $from, $to)) {
                $rows->push($this->activityRow(
                    'Page Created',
                    $label,
                    $editUrl,
                    $page->created_by,
                    $page->created_at,
                    $visitUrl
                ));
            }

            if ($this->wasUpdated($page->created_at, $page->updated_at)
                && (! $userId || (int) $page->updated_by === $userId)
                && $this->inRange($page->updated_at, $from, $to)) {
                $rows->push($this->activityRow(
                    'Page Updated',
                    $label,
                    $editUrl,
                    $page->updated_by ?: $page->created_by,
                    $page->updated_at,
                    $visitUrl
                ));
            }

            return $rows;
        }));
    }

    private function trainingCalendarActivities(?int $userId, ?Carbon $from, ?Carbon $to, ?int $limit = null): Collection
    {
        if (! Schema::hasTable('course_agendas') || ! Schema::hasColumn('course_agendas', 'created_by')) {
            return collect();
        }

        $query = CourseAgenda::query()->with('course');

        if ($userId) {
            $query->where(function ($q) use ($userId) {
                $q->where('created_by', $userId)->orWhere('updated_by', $userId);
            });
        }

        if ($limit) {
            $query->orderByDesc('updated_at')->limit($limit);
        }

        return $this->supportRows($query->get()->flatMap(function (CourseAgenda $agenda) use ($userId, $from, $to) {
            $rows = collect();
            $course = $agenda->course;
            $courseTitle = $course?->title ?: ('Course #' . $agenda->course_id);
            $subject = trim((string) ($agenda->subject ?? ''));
            $label = $subject !== '' ? ($courseTitle . ' — ' . $subject) : $courseTitle;
            $editUrl = route('admin.course-agendas.edit', ['training_calendar' => $agenda->id]);
            $visitUrl = $course ? course_details_url($course) : null;

            if ((! $userId || (int) $agenda->created_by === $userId)
                && $this->inRange($agenda->created_at, $from, $to)) {
                $rows->push($this->activityRow(
                    'Training Calendar Created',
                    $label,
                    $editUrl,
                    $agenda->created_by,
                    $agenda->created_at,
                    $visitUrl
                ));
            }

            if ($this->wasUpdated($agenda->created_at, $agenda->updated_at)
                && (! $userId || (int) $agenda->updated_by === $userId)
                && $this->inRange($agenda->updated_at, $from, $to)) {
                $rows->push($this->activityRow(
                    'Training Calendar Updated',
                    $label,
                    $editUrl,
                    $agenda->updated_by ?: $agenda->created_by,
                    $agenda->updated_at,
                    $visitUrl
                ));
            }

            return $rows;
        }));
    }

    private function seoActivities(?int $userId, ?Carbon $from, ?Carbon $to, ?int $limit = null): Collection
    {
        if (! Schema::hasTable('pages_s_e_o_s') || ! Schema::hasColumn('pages_s_e_o_s', 'created_by')) {
            return collect();
        }

        $query = PagesSEO::query()->with(['page', 'course']);

        if ($userId) {
            $query->where(function ($q) use ($userId) {
                $q->where('created_by', $userId)->orWhere('updated_by', $userId);
            });
        }

        if ($limit) {
            $query->orderByDesc('updated_at')->limit($limit);
        }

        return $this->supportRows($query->get()->flatMap(function (PagesSEO $seo) use ($userId, $from, $to) {
            $rows = collect();
            $type = $seo->course_id ? 'Course' : 'Page';
            $name = $seo->course_id
                ? ($seo->course?->title ?: 'Course #' . $seo->course_id)
                : ($seo->page?->page_name ?: 'Page #' . $seo->page_id);
            $item = $type . ': ' . $name;
            $editUrl = $seo->id ? route('courses-pages-seo.edit', $seo->id) : '#';
            $visitUrl = $seo->course_id
                ? course_details_url($seo->course)
                : page_public_url($seo->page);

            if ((! $userId || (int) $seo->created_by === $userId)
                && $this->inRange($seo->created_at, $from, $to)) {
                $rows->push($this->activityRow(
                    'SEO Created',
                    $item,
                    $editUrl,
                    $seo->created_by,
                    $seo->created_at,
                    $visitUrl
                ));
            }

            if ($this->wasUpdated($seo->created_at, $seo->updated_at)
                && (! $userId || (int) $seo->updated_by === $userId)
                && $this->inRange($seo->updated_at, $from, $to)) {
                $rows->push($this->activityRow(
                    'SEO Updated',
                    $item,
                    $editUrl,
                    $seo->updated_by ?: $seo->created_by,
                    $seo->updated_at,
                    $visitUrl
                ));
            }

            return $rows;
        }));
    }

    private function invoiceActivities(?Carbon $from, ?Carbon $to, ?int $limit = null): Collection
    {
        if (! Schema::hasTable('payments')) {
            return collect();
        }

        $query = Payment::query()
            ->with(['user', 'course'])
            ->orderByDesc('created_at');

        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }
        if ($limit) {
            $query->limit($limit);
        }

        return $this->supportRows($query
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
            }));
    }

    private function installmentPaymentActivities(?Carbon $from, ?Carbon $to, ?int $limit = null): Collection
    {
        if (! Schema::hasTable('installments')) {
            return collect();
        }

        $query = \App\Models\Installment::query()
            ->with(['user', 'payment.course'])
            ->where('status', 'paid')
            ->where('paid_amount', '>', 0)
            ->orderByDesc('paid_date')
            ->orderByDesc('updated_at');

        if ($limit) {
            $query->limit($limit);
        }

        return $this->supportRows($query
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
                    'Receipt Recorded',
                    $course . ' — ' . $student . ' (' . $invoiceNo . ')',
                    route('admin.payments.index'),
                    null,
                    $installment->paid_date ?? $installment->updated_at
                );
            }));
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

    private function mergeActivityRows(Collection $activities, Collection $logs): Collection
    {
        // Prefer log rows with section detail ("Title — Banner") over bare DB update rows.
        return $this->concatActivityRows($logs, $activities)
            ->sortByDesc(fn (array $row) => $row['occurred_at']->timestamp)
            ->groupBy(fn (array $row) => $this->activityDedupeKey($row))
            ->map(function (Collection $group) {
                return $group
                    ->sortByDesc(fn (array $row) => strlen((string) ($row['item'] ?? '')))
                    ->first();
            })
            ->sortByDesc(fn (array $row) => $row['occurred_at']->timestamp)
            ->values();
    }

    private function activityDedupeKey(array $row): string
    {
        $action = strtolower(trim((string) ($row['action'] ?? '')));
        $item = trim((string) ($row['item'] ?? ''));
        // "Course Updated" + "Title — Banner" collapses with bare "Title" for same actor/minute.
        if (preg_match('/\b(updated|created)\b/i', $action)) {
            $item = trim((string) preg_replace('/\s+[—–-]\s+.+$/u', '', $item));
        }

        return implode('|', [
            $action,
            strtolower($item),
            (string) ($row['actor_id'] ?? ''),
            $row['occurred_at']?->format('Y-m-d H:i') ?? '',
        ]);
    }

    /**
     * Forever-safe activity row bags: plain Support\Collection of arrays.
     * Never use Eloquent\Collection::merge() — it calls getKey() on each item.
     */
    private function supportRows(mixed $items): Collection
    {
        if ($items instanceof Collection) {
            return collect($items->values()->all());
        }

        if (is_array($items)) {
            return collect(array_values($items));
        }

        return collect();
    }

    private function concatActivityRows(mixed ...$bags): Collection
    {
        $feed = collect();

        foreach ($bags as $bag) {
            $feed = $feed->concat($this->supportRows($bag));
        }

        return $feed->values();
    }

    private function activityRow(
        string $action,
        string $item,
        string $url,
        ?int $actorId,
        $occurredAt,
        ?string $visitUrl = null
    ): array {
        $editUrl = ($url !== '' && $url !== '#') ? $url : null;

        return [
            'action' => $action,
            'item' => $item,
            'url' => $editUrl ?? $url,
            'edit_url' => $editUrl,
            'visit_url' => $visitUrl ?: null,
            'session_id' => null,
            'actor_id' => $actorId ? (int) $actorId : null,
            'user_name' => audit_user_name(null, $actorId),
            'contact_number' => $this->actorContactNumber($actorId),
            'occurred_at' => Carbon::parse($occurredAt),
        ];
    }

    private function actorContactNumber(?int $actorId): string
    {
        if (! $actorId) {
            return '—';
        }

        $mobile = '';
        if (Schema::hasColumn('users', 'mobile_number')) {
            $mobile = trim((string) (User::query()->whereKey($actorId)->value('mobile_number') ?? ''));
        }

        return $mobile !== '' ? $mobile : '—';
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
