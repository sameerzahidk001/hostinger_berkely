<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Page;
use App\Models\PagesSEO;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ContentDashboardReportService
{
    public function reportBundle(?int $actorUserId, ?string $dateFrom, ?string $dateTo): array
    {
        [$from, $to] = $this->parseDateRange($dateFrom, $dateTo);

        return [
            'pages' => $this->pagesStats($actorUserId, $from, $to),
            'courses' => $this->coursesStats($actorUserId, $from, $to),
            'seo' => $this->seoStats($actorUserId, $from, $to),
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }

    public function pagesStats(?int $actorUserId, ?Carbon $from, ?Carbon $to): array
    {
        $hasAudit = Schema::hasColumn('pages', 'created_by');
        $statusEnabled = pages_status_enabled();

        $base = Page::query();
        $active = $statusEnabled ? (clone $base)->where('status', 1) : (clone $base);
        $inactive = $statusEnabled ? (clone $base)->where('status', 0) : Page::query()->whereRaw('1 = 0');

        return [
            'total' => (int) (clone $base)->count(),
            'active' => (int) $active->count(),
            'inactive' => (int) $inactive->count(),
            'created_by_me' => $hasAudit && $actorUserId
                ? (int) Page::where('created_by', $actorUserId)->count()
                : 0,
            'updated_by_me' => $hasAudit && $actorUserId
                ? (int) Page::where('updated_by', $actorUserId)->count()
                : 0,
            'created_today' => $this->countCreatedInRange(Page::class, now()->startOfDay(), now()->endOfDay()),
            'created_yesterday' => $this->countCreatedInRange(
                Page::class,
                now()->subDay()->startOfDay(),
                now()->subDay()->endOfDay()
            ),
            'created_this_week' => $this->countCreatedInRange(Page::class, now()->startOfWeek(), now()->endOfWeek()),
            'created_this_month' => $this->countCreatedInRange(Page::class, now()->startOfMonth(), now()->endOfMonth()),
            'created_last_month' => $this->countCreatedInRange(
                Page::class,
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth()
            ),
            'created_in_range' => $this->countCreatedInRange(Page::class, $from, $to),
            'updated_today' => $this->countUpdatedInRange(Page::class, now()->startOfDay(), now()->endOfDay()),
            'updated_yesterday' => $this->countUpdatedInRange(
                Page::class,
                now()->subDay()->startOfDay(),
                now()->subDay()->endOfDay()
            ),
            'updated_this_week' => $this->countUpdatedInRange(Page::class, now()->startOfWeek(), now()->endOfWeek()),
            'updated_this_month' => $this->countUpdatedInRange(Page::class, now()->startOfMonth(), now()->endOfMonth()),
            'updated_last_month' => $this->countUpdatedInRange(
                Page::class,
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth()
            ),
            'updated_in_range' => $this->countUpdatedInRange(Page::class, $from, $to),
        ];
    }

    public function coursesStats(?int $actorUserId, ?Carbon $from, ?Carbon $to): array
    {
        $hasAudit = Schema::hasColumn('courses', 'created_by');
        $hasSoftDeletes = Schema::hasColumn('courses', 'deleted_at');

        $total = (int) Course::withTrashed()->count();
        $active = (int) Course::count();
        $inactive = $hasSoftDeletes ? (int) Course::onlyTrashed()->count() : 0;

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'created_by_me' => $hasAudit && $actorUserId
                ? (int) Course::withTrashed()->where('created_by', $actorUserId)->count()
                : 0,
            'updated_by_me' => $hasAudit && $actorUserId
                ? (int) Course::withTrashed()->where('updated_by', $actorUserId)->count()
                : 0,
            'created_today' => $this->countCreatedInRange(Course::class, now()->startOfDay(), now()->endOfDay(), true),
            'created_yesterday' => $this->countCreatedInRange(
                Course::class,
                now()->subDay()->startOfDay(),
                now()->subDay()->endOfDay(),
                true
            ),
            'created_this_week' => $this->countCreatedInRange(Course::class, now()->startOfWeek(), now()->endOfWeek(), true),
            'created_this_month' => $this->countCreatedInRange(Course::class, now()->startOfMonth(), now()->endOfMonth(), true),
            'created_last_month' => $this->countCreatedInRange(
                Course::class,
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth(),
                true
            ),
            'created_in_range' => $this->countCreatedInRange(Course::class, $from, $to, true),
            'updated_today' => $this->countUpdatedInRange(Course::class, now()->startOfDay(), now()->endOfDay(), true),
            'updated_yesterday' => $this->countUpdatedInRange(
                Course::class,
                now()->subDay()->startOfDay(),
                now()->subDay()->endOfDay(),
                true
            ),
            'updated_this_week' => $this->countUpdatedInRange(Course::class, now()->startOfWeek(), now()->endOfWeek(), true),
            'updated_this_month' => $this->countUpdatedInRange(Course::class, now()->startOfMonth(), now()->endOfMonth(), true),
            'updated_last_month' => $this->countUpdatedInRange(
                Course::class,
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth(),
                true
            ),
            'updated_in_range' => $this->countUpdatedInRange(Course::class, $from, $to, true),
        ];
    }

    public function seoStats(?int $actorUserId, ?Carbon $from, ?Carbon $to): array
    {
        if (! Schema::hasTable('pages_s_e_o_s')) {
            return $this->emptySeoStats();
        }

        $hasAudit = Schema::hasColumn('pages_s_e_o_s', 'created_by');
        $base = PagesSEO::query();
        $enteredPages = (int) PagesSEO::whereNotNull('page_id')->distinct('page_id')->count('page_id');
        $enteredCourses = (int) PagesSEO::whereNotNull('course_id')->distinct('course_id')->count('course_id');
        $totalPages = (int) Page::count();
        $totalCourses = (int) Course::count();

        return [
            'total' => (int) (clone $base)->count(),
            'active' => (int) (clone $base)->where(function ($q) {
                $q->whereNotNull('page_id')->orWhereNotNull('course_id');
            })->count(),
            'inactive' => 0,
            'seo_entered_pages' => $enteredPages,
            'seo_entered_courses' => $enteredCourses,
            'seo_missing_pages' => max(0, $totalPages - $enteredPages),
            'seo_missing_courses' => max(0, $totalCourses - $enteredCourses),
            'page_seo' => $enteredPages,
            'course_seo' => $enteredCourses,
            'created_by_me' => $hasAudit && $actorUserId
                ? (int) PagesSEO::where('created_by', $actorUserId)->count()
                : 0,
            'updated_by_me' => $hasAudit && $actorUserId
                ? (int) PagesSEO::where('updated_by', $actorUserId)->count()
                : 0,
            'created_today' => $this->countCreatedInRange(PagesSEO::class, now()->startOfDay(), now()->endOfDay()),
            'created_yesterday' => $this->countCreatedInRange(
                PagesSEO::class,
                now()->subDay()->startOfDay(),
                now()->subDay()->endOfDay()
            ),
            'created_this_week' => $this->countCreatedInRange(PagesSEO::class, now()->startOfWeek(), now()->endOfWeek()),
            'created_this_month' => $this->countCreatedInRange(PagesSEO::class, now()->startOfMonth(), now()->endOfMonth()),
            'created_last_month' => $this->countCreatedInRange(
                PagesSEO::class,
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth()
            ),
            'created_in_range' => $this->countCreatedInRange(PagesSEO::class, $from, $to),
            'updated_today' => $this->countUpdatedInRange(PagesSEO::class, now()->startOfDay(), now()->endOfDay()),
            'updated_yesterday' => $this->countUpdatedInRange(
                PagesSEO::class,
                now()->subDay()->startOfDay(),
                now()->subDay()->endOfDay()
            ),
            'updated_this_week' => $this->countUpdatedInRange(PagesSEO::class, now()->startOfWeek(), now()->endOfWeek()),
            'updated_this_month' => $this->countUpdatedInRange(PagesSEO::class, now()->startOfMonth(), now()->endOfMonth()),
            'updated_last_month' => $this->countUpdatedInRange(
                PagesSEO::class,
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth()
            ),
            'updated_in_range' => $this->countUpdatedInRange(PagesSEO::class, $from, $to),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function detailRows(string $module, string $metric, ?int $actorUserId, ?string $dateFrom, ?string $dateTo, ?string $search = null): Collection
    {
        [$from, $to] = $this->parseDateRange($dateFrom, $dateTo);

        return match ($module) {
            'pages' => $this->pageDetailRows($metric, $actorUserId, $from, $to, $search),
            'courses' => $this->courseDetailRows($metric, $actorUserId, $from, $to, $search),
            'seo' => $this->seoDetailRows($metric, $actorUserId, $from, $to, $search),
            default => collect(),
        };
    }

    public function metricLabels(string $module): array
    {
        $common = [
            'total' => 'Total',
            'active' => 'Active',
            'inactive' => 'Inactive',
            'created_by_me' => 'Created by Me',
            'updated_by_me' => 'Updated by Me',
            'created_today' => 'Created Today',
            'created_yesterday' => 'Created Yesterday',
            'created_this_week' => 'Created This Week',
            'created_this_month' => 'Created This Month',
            'created_last_month' => 'Created Last Month',
            'created_in_range' => 'Created In Selected Range',
            'updated_today' => 'Updated Today',
            'updated_yesterday' => 'Updated Yesterday',
            'updated_this_week' => 'Updated This Week',
            'updated_this_month' => 'Updated This Month',
            'updated_last_month' => 'Updated Last Month',
            'updated_in_range' => 'Updated In Selected Range',
        ];

        if ($module === 'seo') {
            return [
                'total' => 'Total SEO Records',
                'seo_entered_pages' => 'SEO Entered Pages',
                'seo_entered_courses' => 'SEO Entered Courses',
                'seo_missing_pages' => 'SEO Missing Pages',
                'seo_missing_courses' => 'SEO Missing Courses',
                'created_by_me' => 'Created by Me',
                'updated_by_me' => 'Updated by Me',
                'created_today' => 'Created Today',
                'created_yesterday' => 'Created Yesterday',
                'created_this_week' => 'Created This Week',
                'created_this_month' => 'Created This Month',
                'created_last_month' => 'Created Last Month',
                'created_in_range' => 'Created In Selected Range',
                'updated_today' => 'Updated Today',
                'updated_yesterday' => 'Updated Yesterday',
                'updated_this_week' => 'Updated This Week',
                'updated_this_month' => 'Updated This Month',
                'updated_last_month' => 'Updated Last Month',
                'updated_in_range' => 'Updated In Selected Range',
            ];
        }

        return $common;
    }

    private function pageDetailRows(string $metric, ?int $actorUserId, ?Carbon $from, ?Carbon $to, ?string $search): Collection
    {
        if (in_array($metric, ['seo_missing_pages'], true)) {
            return $this->missingPageSeoRows($search);
        }

        $query = Page::query()->with(['createdBy', 'updatedBy']);

        $this->applyMetricFilters($query, $metric, $actorUserId, $from, $to, pages_status_enabled());

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('page_name', 'like', '%' . $search . '%')
                    ->orWhere('url', 'like', '%' . $search . '%');
            });
        }

        $analyzer = app(SeoAnalyzerService::class);

        return $query->orderByDesc('updated_at')->limit(500)->get()->map(function (Page $page) use ($analyzer) {
            $status = pages_status_enabled()
                ? (((int) ($page->status ?? 1) === 1) ? 'Active' : 'Disabled')
                : 'Active';
            $seo = PagesSEO::query()->where('page_id', $page->id)->first();
            [$seoStatus, $seoScore] = $this->seoStatusAndScore($seo, $analyzer);

            return [
                'id' => $page->id,
                'title' => $page->page_name ?: ('Page #' . $page->id),
                'status' => $status,
                'seo_status' => $seoStatus,
                'seo_score' => $seoScore,
                'created_by' => audit_user_name($page->createdBy, $page->created_by ?? null),
                'updated_by' => audit_user_name($page->updatedBy, $page->updated_by ?? null),
                'created_at' => optional($page->created_at)?->format('Y-m-d H:i'),
                'updated_at' => optional($page->updated_at)?->format('Y-m-d H:i'),
                'url' => route('pages.edit', $page->id),
            ];
        });
    }

    private function courseDetailRows(string $metric, ?int $actorUserId, ?Carbon $from, ?Carbon $to, ?string $search): Collection
    {
        if (in_array($metric, ['seo_missing_courses'], true)) {
            return $this->missingCourseSeoRows($search);
        }

        $query = Course::withTrashed()->with(['createdBy', 'updatedBy']);

        $this->applyCourseMetricFilters($query, $metric, $actorUserId, $from, $to);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%');
            });
        }

        $analyzer = app(SeoAnalyzerService::class);

        return $query->orderByDesc('updated_at')->limit(500)->get()->map(function (Course $course) use ($analyzer) {
            $seo = PagesSEO::query()->where('course_id', $course->id)->first();
            [$seoStatus, $seoScore] = $this->seoStatusAndScore($seo, $analyzer);

            return [
                'id' => $course->id,
                'title' => $course->title ?: ('Course #' . $course->id),
                'status' => $course->deleted_at ? 'Disabled' : 'Active',
                'seo_status' => $seoStatus,
                'seo_score' => $seoScore,
                'created_by' => audit_user_name($course->createdBy, $course->created_by ?? null),
                'updated_by' => audit_user_name($course->updatedBy, $course->updated_by ?? null),
                'created_at' => optional($course->created_at)?->format('Y-m-d H:i'),
                'updated_at' => optional($course->updated_at)?->format('Y-m-d H:i'),
                'url' => route('course.edit', $course->id),
            ];
        });
    }

    private function seoDetailRows(string $metric, ?int $actorUserId, ?Carbon $from, ?Carbon $to, ?string $search): Collection
    {
        if ($metric === 'seo_missing_pages') {
            return $this->missingPageSeoRows($search);
        }
        if ($metric === 'seo_missing_courses') {
            return $this->missingCourseSeoRows($search);
        }

        if (! Schema::hasTable('pages_s_e_o_s')) {
            return collect();
        }

        $query = PagesSEO::query()->with(['page', 'course', 'createdBy', 'updatedBy']);

        match ($metric) {
            'seo_entered_pages', 'page_seo' => $query->whereNotNull('page_id'),
            'seo_entered_courses', 'course_seo' => $query->whereNotNull('course_id'),
            'created_by_me' => $actorUserId ? $query->where('created_by', $actorUserId) : $query->whereRaw('1 = 0'),
            'updated_by_me' => $actorUserId ? $query->where('updated_by', $actorUserId) : $query->whereRaw('1 = 0'),
            'created_today' => $this->applyCreatedRange($query, now()->startOfDay(), now()->endOfDay()),
            'created_yesterday' => $this->applyCreatedRange($query, now()->subDay()->startOfDay(), now()->subDay()->endOfDay()),
            'created_this_week' => $this->applyCreatedRange($query, now()->startOfWeek(), now()->endOfWeek()),
            'created_this_month' => $this->applyCreatedRange($query, now()->startOfMonth(), now()->endOfMonth()),
            'created_last_month' => $this->applyCreatedRange(
                $query,
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth()
            ),
            'created_in_range' => $this->applyCreatedRange($query, $from, $to),
            'updated_today' => $this->applyUpdatedRange($query, now()->startOfDay(), now()->endOfDay()),
            'updated_yesterday' => $this->applyUpdatedRange($query, now()->subDay()->startOfDay(), now()->subDay()->endOfDay()),
            'updated_this_week' => $this->applyUpdatedRange($query, now()->startOfWeek(), now()->endOfWeek()),
            'updated_this_month' => $this->applyUpdatedRange($query, now()->startOfMonth(), now()->endOfMonth()),
            'updated_last_month' => $this->applyUpdatedRange(
                $query,
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth()
            ),
            'updated_in_range' => $this->applyUpdatedRange($query, $from, $to),
            default => null,
        };

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('focus_keyword', 'like', '%' . $search . '%')
                    ->orWhere('meta_description', 'like', '%' . $search . '%');
            });
        }

        $analyzer = app(SeoAnalyzerService::class);

        return $query->orderByDesc('updated_at')->limit(500)->get()->map(function (PagesSEO $seo) use ($analyzer) {
            $type = $seo->course_id ? 'Course' : 'Page';
            $linked = $seo->course_id
                ? ($seo->course?->title ?: ('Course #' . $seo->course_id))
                : ($seo->page?->page_name ?: ('Page #' . $seo->page_id));
            [$seoStatus, $seoScore] = $this->seoStatusAndScore($seo, $analyzer);

            return [
                'id' => $seo->id,
                'title' => trim(($seo->title ?: 'SEO #' . $seo->id) . ' (' . $type . ': ' . $linked . ')'),
                'status' => $type,
                'seo_status' => $seoStatus,
                'seo_score' => $seoScore,
                'created_by' => audit_user_name($seo->createdBy, $seo->created_by ?? null),
                'updated_by' => audit_user_name($seo->updatedBy, $seo->updated_by ?? null),
                'created_at' => optional($seo->created_at)?->format('Y-m-d H:i'),
                'updated_at' => optional($seo->updated_at)?->format('Y-m-d H:i'),
                'url' => route('courses-pages-seo.edit', $seo->id),
            ];
        });
    }

    private function missingPageSeoRows(?string $search): Collection
    {
        $query = Page::query()
            ->whereDoesntHave('seo')
            ->with(['createdBy', 'updatedBy']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('page_name', 'like', '%' . $search . '%')
                    ->orWhere('url', 'like', '%' . $search . '%');
            });
        }

        return $query->orderByDesc('updated_at')->limit(500)->get()->map(function (Page $page) {
            $status = pages_status_enabled()
                ? (((int) ($page->status ?? 1) === 1) ? 'Active' : 'Disabled')
                : 'Active';

            return [
                'id' => $page->id,
                'title' => $page->page_name ?: ('Page #' . $page->id),
                'status' => $status,
                'seo_status' => 'Missing',
                'seo_score' => '—',
                'created_by' => audit_user_name($page->createdBy, $page->created_by ?? null),
                'updated_by' => audit_user_name($page->updatedBy, $page->updated_by ?? null),
                'created_at' => optional($page->created_at)?->format('Y-m-d H:i'),
                'updated_at' => optional($page->updated_at)?->format('Y-m-d H:i'),
                'url' => route('pages.edit', $page->id),
            ];
        });
    }

    private function missingCourseSeoRows(?string $search): Collection
    {
        $query = Course::query()
            ->whereDoesntHave('seo')
            ->with(['createdBy', 'updatedBy']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%');
            });
        }

        return $query->orderByDesc('updated_at')->limit(500)->get()->map(function (Course $course) {
            return [
                'id' => $course->id,
                'title' => $course->title ?: ('Course #' . $course->id),
                'status' => 'Active',
                'seo_status' => 'Missing',
                'seo_score' => '—',
                'created_by' => audit_user_name($course->createdBy, $course->created_by ?? null),
                'updated_by' => audit_user_name($course->updatedBy, $course->updated_by ?? null),
                'created_at' => optional($course->created_at)?->format('Y-m-d H:i'),
                'updated_at' => optional($course->updated_at)?->format('Y-m-d H:i'),
                'url' => route('course.edit', $course->id),
            ];
        });
    }

    private function seoStatusAndScore(?PagesSEO $seo, SeoAnalyzerService $analyzer): array
    {
        if (! $seo) {
            return ['Missing', '—'];
        }

        try {
            $analysis = $analyzer->analyzeForTables($seo);
            $score = isset($analysis['score']) ? (int) $analysis['score'] : null;
            $label = trim((string) ($analysis['label'] ?? ''));

            return [
                $label !== '' ? $label : 'Entered',
                $score !== null ? ($score . '/100') : '—',
            ];
        } catch (\Throwable $e) {
            return ['Entered', '—'];
        }
    }

    private function applyMetricFilters($query, string $metric, ?int $actorUserId, ?Carbon $from, ?Carbon $to, bool $statusEnabled): void
    {
        match ($metric) {
            'active' => $statusEnabled ? $query->where('status', 1) : null,
            'inactive' => $statusEnabled ? $query->where('status', 0) : $query->whereRaw('1 = 0'),
            'created_by_me' => $actorUserId ? $query->where('created_by', $actorUserId) : $query->whereRaw('1 = 0'),
            'updated_by_me' => $actorUserId ? $query->where('updated_by', $actorUserId) : $query->whereRaw('1 = 0'),
            'created_today' => $this->applyCreatedRange($query, now()->startOfDay(), now()->endOfDay()),
            'created_yesterday' => $this->applyCreatedRange($query, now()->subDay()->startOfDay(), now()->subDay()->endOfDay()),
            'created_this_week' => $this->applyCreatedRange($query, now()->startOfWeek(), now()->endOfWeek()),
            'created_this_month' => $this->applyCreatedRange($query, now()->startOfMonth(), now()->endOfMonth()),
            'created_last_month' => $this->applyCreatedRange(
                $query,
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth()
            ),
            'created_in_range' => $this->applyCreatedRange($query, $from, $to),
            'updated_today' => $this->applyUpdatedRange($query, now()->startOfDay(), now()->endOfDay()),
            'updated_yesterday' => $this->applyUpdatedRange($query, now()->subDay()->startOfDay(), now()->subDay()->endOfDay()),
            'updated_this_week' => $this->applyUpdatedRange($query, now()->startOfWeek(), now()->endOfWeek()),
            'updated_this_month' => $this->applyUpdatedRange($query, now()->startOfMonth(), now()->endOfMonth()),
            'updated_last_month' => $this->applyUpdatedRange(
                $query,
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth()
            ),
            'updated_in_range' => $this->applyUpdatedRange($query, $from, $to),
            default => null,
        };
    }

    private function applyCourseMetricFilters($query, string $metric, ?int $actorUserId, ?Carbon $from, ?Carbon $to): void
    {
        match ($metric) {
            'active' => $query->whereNull('deleted_at'),
            'inactive' => $query->whereNotNull('deleted_at'),
            'created_by_me' => $actorUserId ? $query->where('created_by', $actorUserId) : $query->whereRaw('1 = 0'),
            'updated_by_me' => $actorUserId ? $query->where('updated_by', $actorUserId) : $query->whereRaw('1 = 0'),
            'created_today' => $this->applyCreatedRange($query, now()->startOfDay(), now()->endOfDay()),
            'created_yesterday' => $this->applyCreatedRange($query, now()->subDay()->startOfDay(), now()->subDay()->endOfDay()),
            'created_this_week' => $this->applyCreatedRange($query, now()->startOfWeek(), now()->endOfWeek()),
            'created_this_month' => $this->applyCreatedRange($query, now()->startOfMonth(), now()->endOfMonth()),
            'created_last_month' => $this->applyCreatedRange(
                $query,
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth()
            ),
            'created_in_range' => $this->applyCreatedRange($query, $from, $to),
            'updated_today' => $this->applyUpdatedRange($query, now()->startOfDay(), now()->endOfDay()),
            'updated_yesterday' => $this->applyUpdatedRange($query, now()->subDay()->startOfDay(), now()->subDay()->endOfDay()),
            'updated_this_week' => $this->applyUpdatedRange($query, now()->startOfWeek(), now()->endOfWeek()),
            'updated_this_month' => $this->applyUpdatedRange($query, now()->startOfMonth(), now()->endOfMonth()),
            'updated_last_month' => $this->applyUpdatedRange(
                $query,
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth()
            ),
            'updated_in_range' => $this->applyUpdatedRange($query, $from, $to),
            default => null,
        };
    }

    private function applyCreatedRange($query, ?Carbon $from, ?Carbon $to): void
    {
        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }
    }

    private function applyUpdatedRange($query, ?Carbon $from, ?Carbon $to): void
    {
        if ($from) {
            $query->where('updated_at', '>=', $from);
        }
        if ($to) {
            $query->where('updated_at', '<=', $to);
        }
    }

    private function countCreatedInRange(string $modelClass, ?Carbon $from, ?Carbon $to, bool $withTrashed = false): int
    {
        $query = $this->baseQuery($modelClass, $withTrashed);
        $this->applyCreatedRange($query, $from, $to);

        return (int) $query->count();
    }

    private function countUpdatedInRange(string $modelClass, ?Carbon $from, ?Carbon $to, bool $withTrashed = false): int
    {
        $query = $this->baseQuery($modelClass, $withTrashed);
        $this->applyUpdatedRange($query, $from, $to);

        return (int) $query->count();
    }

    private function baseQuery(string $modelClass, bool $withTrashed = false)
    {
        if ($withTrashed && method_exists($modelClass, 'withTrashed')) {
            return $modelClass::withTrashed();
        }

        return $modelClass::query();
    }

    private function emptySeoStats(): array
    {
        return [
            'total' => 0,
            'active' => 0,
            'inactive' => 0,
            'seo_entered_pages' => 0,
            'seo_entered_courses' => 0,
            'seo_missing_pages' => 0,
            'seo_missing_courses' => 0,
            'page_seo' => 0,
            'course_seo' => 0,
            'created_by_me' => 0,
            'updated_by_me' => 0,
            'created_today' => 0,
            'created_yesterday' => 0,
            'created_this_week' => 0,
            'created_this_month' => 0,
            'created_last_month' => 0,
            'created_in_range' => 0,
            'updated_today' => 0,
            'updated_yesterday' => 0,
            'updated_this_week' => 0,
            'updated_this_month' => 0,
            'updated_last_month' => 0,
            'updated_in_range' => 0,
        ];
    }

    private function parseDateRange(?string $dateFrom, ?string $dateTo): array
    {
        $from = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : null;
        $to = $dateTo ? Carbon::parse($dateTo)->endOfDay() : null;

        return [$from, $to];
    }
}
