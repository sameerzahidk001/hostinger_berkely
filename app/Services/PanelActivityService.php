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
            'total_pages_site' => (int) Page::count(),
            'total_payments_site' => $includePayments && Schema::hasTable('payments')
                ? (int) Payment::count()
                : 0,
            'courses_created' => $this->countCoursesCreated($userId, $from, $to),
            'pages_created' => $this->countPagesCreated($userId, $from, $to),
            'payments_recorded' => $includePayments ? $this->countPayments($from, $to) : 0,
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
            return $this->paymentActivities($from, $to)
                ->sortByDesc(fn (array $row) => $row['occurred_at']->timestamp)
                ->values();
        }

        $feed = $this->courseActivities($userId, $from, $to)
            ->merge($this->pageActivities($userId, $from, $to));

        if ($includePayments && empty($restrictToUserIds)) {
            $feed = $feed->merge($this->paymentActivities($from, $to));
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

    private function paymentActivities(?Carbon $from, ?Carbon $to): Collection
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
                    'Payment Recorded',
                    $course . ' — ' . $student,
                    route('admin.payments.index'),
                    null,
                    $payment->created_at
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
