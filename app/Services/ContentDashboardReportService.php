<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseAgenda;
use App\Models\CourseFee;
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
            'training' => $this->trainingStats($actorUserId, $from, $to),
            'fees' => $this->feePackageStats($actorUserId, $from, $to),
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
            'created_by_me' => $hasAudit
                ? (int) ($actorUserId
                    ? Page::where('created_by', $actorUserId)->count()
                    : Page::count())
                : 0,
            'updated_by_me' => $hasAudit
                ? (int) ($actorUserId
                    ? Page::where('updated_by', $actorUserId)->count()
                    : Page::count())
                : 0,
            'created_today' => $this->countCreatedInRange(Page::class, now()->startOfDay(), now()->endOfDay(), false, $actorUserId),
            'created_yesterday' => $this->countCreatedInRange(
                Page::class,
                now()->subDay()->startOfDay(),
                now()->subDay()->endOfDay(),
                false,
                $actorUserId
            ),
            'created_this_week' => $this->countCreatedInRange(Page::class, $this->thisWeekBounds()[0], $this->thisWeekBounds()[1], false, $actorUserId),
            'created_last_week' => $this->countCreatedInRange(Page::class, $this->lastWeekBounds()[0], $this->lastWeekBounds()[1], false, $actorUserId),
            'created_this_month' => $this->countCreatedInRange(Page::class, now()->startOfMonth(), now()->endOfMonth(), false, $actorUserId),
            'created_this_year' => $this->countCreatedInRange(Page::class, now()->startOfYear(), now()->endOfYear(), false, $actorUserId),
            'created_last_month' => $this->countCreatedInRange(
                Page::class,
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth(),
                false,
                $actorUserId
            ),
            'created_in_range' => $this->countCreatedInRange(Page::class, $from, $to, false, $actorUserId),
            'updated_today' => $this->countUpdatedInRange(Page::class, now()->startOfDay(), now()->endOfDay(), false, $actorUserId),
            'updated_yesterday' => $this->countUpdatedInRange(
                Page::class,
                now()->subDay()->startOfDay(),
                now()->subDay()->endOfDay(),
                false,
                $actorUserId
            ),
            'updated_this_week' => $this->countUpdatedInRange(Page::class, $this->thisWeekBounds()[0], $this->thisWeekBounds()[1], false, $actorUserId),
            'updated_last_week' => $this->countUpdatedInRange(Page::class, $this->lastWeekBounds()[0], $this->lastWeekBounds()[1], false, $actorUserId),
            'updated_this_month' => $this->countUpdatedInRange(Page::class, now()->startOfMonth(), now()->endOfMonth(), false, $actorUserId),
            'updated_last_month' => $this->countUpdatedInRange(
                Page::class,
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth(),
                false,
                $actorUserId
            ),
            'updated_in_range' => $this->countUpdatedInRange(Page::class, $from, $to, false, $actorUserId),
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
            'created_by_me' => $hasAudit
                ? (int) ($actorUserId
                    ? Course::withTrashed()->where('created_by', $actorUserId)->count()
                    : Course::withTrashed()->count())
                : 0,
            'updated_by_me' => $hasAudit
                ? (int) ($actorUserId
                    ? Course::withTrashed()->where('updated_by', $actorUserId)->count()
                    : Course::withTrashed()->count())
                : 0,
            'created_today' => $this->countCreatedInRange(Course::class, now()->startOfDay(), now()->endOfDay(), true, $actorUserId),
            'created_yesterday' => $this->countCreatedInRange(
                Course::class,
                now()->subDay()->startOfDay(),
                now()->subDay()->endOfDay(),
                true,
                $actorUserId
            ),
            'created_this_week' => $this->countCreatedInRange(Course::class, $this->thisWeekBounds()[0], $this->thisWeekBounds()[1], true, $actorUserId),
            'created_last_week' => $this->countCreatedInRange(Course::class, $this->lastWeekBounds()[0], $this->lastWeekBounds()[1], true, $actorUserId),
            'created_this_month' => $this->countCreatedInRange(Course::class, now()->startOfMonth(), now()->endOfMonth(), true, $actorUserId),
            'created_this_year' => $this->countCreatedInRange(Course::class, now()->startOfYear(), now()->endOfYear(), true, $actorUserId),
            'created_last_month' => $this->countCreatedInRange(
                Course::class,
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth(),
                true,
                $actorUserId
            ),
            'created_in_range' => $this->countCreatedInRange(Course::class, $from, $to, true, $actorUserId),
            'updated_today' => $this->countUpdatedInRange(Course::class, now()->startOfDay(), now()->endOfDay(), true, $actorUserId),
            'updated_yesterday' => $this->countUpdatedInRange(
                Course::class,
                now()->subDay()->startOfDay(),
                now()->subDay()->endOfDay(),
                true,
                $actorUserId
            ),
            'updated_this_week' => $this->countUpdatedInRange(Course::class, $this->thisWeekBounds()[0], $this->thisWeekBounds()[1], true, $actorUserId),
            'updated_last_week' => $this->countUpdatedInRange(Course::class, $this->lastWeekBounds()[0], $this->lastWeekBounds()[1], true, $actorUserId),
            'updated_this_month' => $this->countUpdatedInRange(Course::class, now()->startOfMonth(), now()->endOfMonth(), true, $actorUserId),
            'updated_last_month' => $this->countUpdatedInRange(
                Course::class,
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth(),
                true,
                $actorUserId
            ),
            'updated_in_range' => $this->countUpdatedInRange(Course::class, $from, $to, true, $actorUserId),
        ];
    }

    public function seoStats(?int $actorUserId, ?Carbon $from, ?Carbon $to): array
    {
        if (! Schema::hasTable('pages_s_e_o_s')) {
            return $this->emptySeoStats();
        }

        $hasAudit = Schema::hasColumn('pages_s_e_o_s', 'created_by');
        $base = PagesSEO::query();
        // SEO entered/missing metrics only count Active pages & courses.
        $enteredPages = (int) PagesSEO::query()
            ->whereNotNull('page_id')
            ->whereIn('page_id', $this->activePagesQuery()->select('id'))
            ->distinct('page_id')
            ->count('page_id');
        $enteredCourses = (int) PagesSEO::query()
            ->whereNotNull('course_id')
            ->whereIn('course_id', $this->activeCoursesQuery()->select('id'))
            ->distinct('course_id')
            ->count('course_id');
        $totalActivePages = (int) $this->activePagesQuery()->count();
        $totalActiveCourses = (int) $this->activeCoursesQuery()->count();

        return [
            'total' => (int) (clone $base)->count(),
            'active' => (int) (clone $base)->where(function ($q) {
                $q->whereNotNull('page_id')->orWhereNotNull('course_id');
            })->count(),
            'inactive' => 0,
            'seo_entered_pages' => $enteredPages,
            'seo_entered_courses' => $enteredCourses,
            'seo_missing_pages' => max(0, $totalActivePages - $enteredPages),
            'seo_missing_courses' => max(0, $totalActiveCourses - $enteredCourses),
            'page_seo' => $enteredPages,
            'course_seo' => $enteredCourses,
            'created_by_me' => $hasAudit
                ? (int) ($actorUserId
                    ? PagesSEO::where('created_by', $actorUserId)->count()
                    : PagesSEO::count())
                : 0,
            'updated_by_me' => $hasAudit
                ? (int) ($actorUserId
                    ? PagesSEO::where('updated_by', $actorUserId)->count()
                    : PagesSEO::count())
                : 0,
            'created_today' => $this->countCreatedInRange(PagesSEO::class, now()->startOfDay(), now()->endOfDay(), false, $actorUserId),
            'created_yesterday' => $this->countCreatedInRange(
                PagesSEO::class,
                now()->subDay()->startOfDay(),
                now()->subDay()->endOfDay(),
                false,
                $actorUserId
            ),
            'created_this_week' => $this->countCreatedInRange(PagesSEO::class, $this->thisWeekBounds()[0], $this->thisWeekBounds()[1], false, $actorUserId),
            'created_last_week' => $this->countCreatedInRange(PagesSEO::class, $this->lastWeekBounds()[0], $this->lastWeekBounds()[1], false, $actorUserId),
            'created_this_month' => $this->countCreatedInRange(PagesSEO::class, now()->startOfMonth(), now()->endOfMonth(), false, $actorUserId),
            'created_this_year' => $this->countCreatedInRange(PagesSEO::class, now()->startOfYear(), now()->endOfYear(), false, $actorUserId),
            'created_last_month' => $this->countCreatedInRange(
                PagesSEO::class,
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth(),
                false,
                $actorUserId
            ),
            'created_in_range' => $this->countCreatedInRange(PagesSEO::class, $from, $to, false, $actorUserId),
            'updated_today' => $this->countUpdatedInRange(PagesSEO::class, now()->startOfDay(), now()->endOfDay(), false, $actorUserId),
            'updated_yesterday' => $this->countUpdatedInRange(
                PagesSEO::class,
                now()->subDay()->startOfDay(),
                now()->subDay()->endOfDay(),
                false,
                $actorUserId
            ),
            'updated_this_week' => $this->countUpdatedInRange(PagesSEO::class, $this->thisWeekBounds()[0], $this->thisWeekBounds()[1], false, $actorUserId),
            'updated_last_week' => $this->countUpdatedInRange(PagesSEO::class, $this->lastWeekBounds()[0], $this->lastWeekBounds()[1], false, $actorUserId),
            'updated_this_month' => $this->countUpdatedInRange(PagesSEO::class, now()->startOfMonth(), now()->endOfMonth(), false, $actorUserId),
            'updated_last_month' => $this->countUpdatedInRange(
                PagesSEO::class,
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth(),
                false,
                $actorUserId
            ),
            'updated_in_range' => $this->countUpdatedInRange(PagesSEO::class, $from, $to, false, $actorUserId),
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
            'training' => $this->trainingDetailRows($metric, $actorUserId, $from, $to, $search),
            'fees' => $this->feePackageDetailRows($metric, $actorUserId, $from, $to, $search),
            default => collect(),
        };
    }

    /**
     * Column definitions for detail modal / Excel / PDF.
     * Each item: ['key' => string, 'label' => string]
     *
     * @return array<int, array{key: string, label: string}>
     */
    public function detailColumns(string $module, string $metric = 'total'): array
    {
        if ($module === 'training') {
            if (in_array($metric, ['courses_with_training', 'courses_without_training'], true)) {
                return [
                    ['key' => 'course', 'label' => 'Course'],
                    ['key' => 'coverage', 'label' => 'Coverage'],
                    ['key' => 'created_by', 'label' => 'Created By'],
                    ['key' => 'created_at', 'label' => 'Created'],
                    ['key' => 'updated_by', 'label' => 'Modified By'],
                    ['key' => 'updated_at', 'label' => 'Last Modified'],
                ];
            }

            return [
                ['key' => 'course', 'label' => 'Course'],
                ['key' => 'subject', 'label' => 'Subject'],
                ['key' => 'delivery_type', 'label' => 'Delivery Option'],
                ['key' => 'location', 'label' => 'Location'],
                ['key' => 'from', 'label' => 'From'],
                ['key' => 'to', 'label' => 'To'],
                ['key' => 'created_at', 'label' => 'Created'],
                ['key' => 'created_by', 'label' => 'Created By'],
                ['key' => 'updated_at', 'label' => 'Last Modified'],
                ['key' => 'updated_by', 'label' => 'Modified By'],
            ];
        }

        return [
            ['key' => 'title', 'label' => 'Title'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'type', 'label' => 'Type'],
            ['key' => 'seo_status', 'label' => 'SEO Status'],
            ['key' => 'seo_score', 'label' => 'SEO Score'],
            ['key' => 'created_by', 'label' => 'Created By'],
            ['key' => 'created_at', 'label' => 'Created Date'],
            ['key' => 'updated_by', 'label' => 'Updated By'],
            ['key' => 'updated_at', 'label' => 'Updated Date'],
        ];
    }

    public function metricLabels(string $module, string $actorName = 'All Users'): array
    {
        $who = trim($actorName) !== '' ? trim($actorName) : 'All Users';

        if ($module === 'training') {
            // No Active/Inactive status cards — past dates is a date filter, not a status flag.
            return [
                'total' => 'Total Training Calendar entries',
                'courses_with_training' => 'Courses with Training Calendar (Active)',
                'courses_without_training' => 'Courses without Training Calendar (Active)',
                'past_dates' => 'Training Calendars with Past Dates',
                'created_by_me' => 'Total Training Calendar entries created by ' . $who,
                'updated_by_me' => 'Total Training Calendar entries updated by ' . $who,
                'created_today' => 'Created Today by ' . $who,
                'created_this_year' => 'Total Created This Year by ' . $who,
                'created_yesterday' => 'Created Yesterday by ' . $who,
                'created_this_week' => 'Created This Week by ' . $who,
                'created_last_week' => 'Created Last Week by ' . $who,
                'created_this_month' => 'Created This Month by ' . $who,
                'created_last_month' => 'Created Last Month by ' . $who,
                'updated_today' => 'Updated Today by ' . $who,
                'updated_yesterday' => 'Updated Yesterday by ' . $who,
                'updated_this_week' => 'Updated This Week by ' . $who,
                'updated_last_week' => 'Updated Last Week by ' . $who,
                'updated_this_month' => 'Updated This Month by ' . $who,
                'updated_last_month' => 'Updated Last Month by ' . $who,
                'created_in_range' => 'Created Custom Date Range',
                'updated_in_range' => 'Updated Custom Date Range',
            ];
        }

        if ($module === 'fees') {
            return $this->standardMetricLabels('Fee Packages', 'Fee Packages', $who);
        }

        if ($module === 'seo') {
            return [
                'total' => 'Total SEO Records',
                'seo_entered_pages' => 'SEO Entered Pages',
                'seo_entered_courses' => 'SEO Entered Courses',
                'seo_missing_pages' => 'SEO Missing Pages',
                'seo_missing_courses' => 'SEO Missing Courses',
                'created_by_me' => 'Total SEO created by ' . $who,
                'updated_by_me' => 'Total SEO updated by ' . $who,
                'created_today' => 'Created Today by ' . $who,
                'created_this_year' => 'Total Created This Year by ' . $who,
                'created_yesterday' => 'Created Yesterday by ' . $who,
                'created_this_week' => 'Created This Week by ' . $who,
                'created_last_week' => 'Created Last Week by ' . $who,
                'created_this_month' => 'Created This Month by ' . $who,
                'created_last_month' => 'Created Last Month by ' . $who,
                'updated_today' => 'Updated Today by ' . $who,
                'updated_yesterday' => 'Updated Yesterday by ' . $who,
                'updated_this_week' => 'Updated This Week by ' . $who,
                'updated_last_week' => 'Updated Last Week by ' . $who,
                'updated_this_month' => 'Updated This Month by ' . $who,
                'updated_last_month' => 'Updated Last Month by ' . $who,
                'created_in_range' => 'Created Custom Date Range',
                'updated_in_range' => 'Updated Custom Date Range',
            ];
        }

        $noun = $module === 'courses' ? 'Courses' : 'Pages';

        return $this->standardMetricLabels($noun, $noun, $who);
    }

    private function standardMetricLabels(string $totalNoun, string $statusNoun, string $who): array
    {
        return [
            'total' => 'Total ' . $totalNoun,
            'active' => 'Active ' . $statusNoun,
            'inactive' => 'Inactive ' . $statusNoun,
            'created_by_me' => 'Total ' . $totalNoun . ' created by ' . $who,
            'updated_by_me' => 'Total ' . $totalNoun . ' updated by ' . $who,
            'created_today' => 'Created Today by ' . $who,
            'created_this_year' => 'Total Created This Year by ' . $who,
            'created_yesterday' => 'Created Yesterday by ' . $who,
            'created_this_week' => 'Created This Week by ' . $who,
            'created_last_week' => 'Created Last Week by ' . $who,
            'created_this_month' => 'Created This Month by ' . $who,
            'created_last_month' => 'Created Last Month by ' . $who,
            'updated_today' => 'Updated Today by ' . $who,
            'updated_yesterday' => 'Updated Yesterday by ' . $who,
            'updated_this_week' => 'Updated This Week by ' . $who,
            'updated_last_week' => 'Updated Last Week by ' . $who,
            'updated_this_month' => 'Updated This Month by ' . $who,
            'updated_last_month' => 'Updated Last Month by ' . $who,
            'created_in_range' => 'Created Custom Date Range',
            'updated_in_range' => 'Updated Custom Date Range',
        ];
    }

    public function trainingStats(?int $actorUserId, ?Carbon $from, ?Carbon $to): array
    {
        if (! Schema::hasTable('course_agendas')) {
            return array_merge($this->emptyStandardStats(), [
                'courses_with_training' => 0,
                'courses_without_training' => 0,
                'past_dates' => 0,
            ]);
        }

        $hasAudit = Schema::hasColumn('course_agendas', 'created_by');
        $today = now()->startOfDay();
        $total = (int) CourseAgenda::count();
        $pastDates = (int) CourseAgenda::query()->whereDate('to', '<', $today)->count();
        $coursesWithTraining = (int) $this->activeCoursesQuery()->whereHas('agendas')->count();
        $coursesWithoutTraining = (int) $this->activeCoursesQuery()->whereDoesntHave('agendas')->count();

        return [
            'total' => $total,
            'past_dates' => $pastDates,
            'courses_with_training' => $coursesWithTraining,
            'courses_without_training' => $coursesWithoutTraining,
            'created_by_me' => $hasAudit
                ? (int) ($actorUserId
                    ? CourseAgenda::where('created_by', $actorUserId)->count()
                    : CourseAgenda::count())
                : ($actorUserId ? 0 : $total),
            'updated_by_me' => $hasAudit
                ? (int) ($actorUserId
                    ? CourseAgenda::where('updated_by', $actorUserId)->count()
                    : CourseAgenda::count())
                : ($actorUserId ? 0 : $total),
            'created_today' => $this->countCreatedInRange(CourseAgenda::class, now()->startOfDay(), now()->endOfDay(), false, $actorUserId),
            'created_yesterday' => $this->countCreatedInRange(CourseAgenda::class, now()->subDay()->startOfDay(), now()->subDay()->endOfDay(), false, $actorUserId),
            'created_this_week' => $this->countCreatedInRange(CourseAgenda::class, $this->thisWeekBounds()[0], $this->thisWeekBounds()[1], false, $actorUserId),
            'created_last_week' => $this->countCreatedInRange(CourseAgenda::class, $this->lastWeekBounds()[0], $this->lastWeekBounds()[1], false, $actorUserId),
            'created_this_month' => $this->countCreatedInRange(CourseAgenda::class, now()->startOfMonth(), now()->endOfMonth(), false, $actorUserId),
            'created_this_year' => $this->countCreatedInRange(CourseAgenda::class, now()->startOfYear(), now()->endOfYear(), false, $actorUserId),
            'created_last_month' => $this->countCreatedInRange(
                CourseAgenda::class,
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth(),
                false,
                $actorUserId
            ),
            'created_in_range' => $this->countCreatedInRange(CourseAgenda::class, $from, $to, false, $actorUserId),
            'updated_today' => $this->countUpdatedInRange(CourseAgenda::class, now()->startOfDay(), now()->endOfDay(), false, $actorUserId),
            'updated_yesterday' => $this->countUpdatedInRange(CourseAgenda::class, now()->subDay()->startOfDay(), now()->subDay()->endOfDay(), false, $actorUserId),
            'updated_this_week' => $this->countUpdatedInRange(CourseAgenda::class, $this->thisWeekBounds()[0], $this->thisWeekBounds()[1], false, $actorUserId),
            'updated_last_week' => $this->countUpdatedInRange(CourseAgenda::class, $this->lastWeekBounds()[0], $this->lastWeekBounds()[1], false, $actorUserId),
            'updated_this_month' => $this->countUpdatedInRange(CourseAgenda::class, now()->startOfMonth(), now()->endOfMonth(), false, $actorUserId),
            'updated_last_month' => $this->countUpdatedInRange(
                CourseAgenda::class,
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth(),
                false,
                $actorUserId
            ),
            'updated_in_range' => $this->countUpdatedInRange(CourseAgenda::class, $from, $to, false, $actorUserId),
        ];
    }

    public function feePackageStats(?int $actorUserId, ?Carbon $from, ?Carbon $to): array
    {
        if (! Schema::hasTable('course_fees')) {
            return $this->emptyStandardStats();
        }

        $hasAudit = Schema::hasColumn('course_fees', 'created_by');
        $hasShow = Schema::hasColumn('course_fees', 'showonwebsite');
        $total = (int) CourseFee::count();
        $active = $hasShow ? (int) CourseFee::where('showonwebsite', 1)->count() : $total;
        $inactive = $hasShow ? (int) CourseFee::where(function ($q) {
            $q->where('showonwebsite', 0)->orWhereNull('showonwebsite');
        })->count() : 0;

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'created_by_me' => $hasAudit
                ? (int) ($actorUserId
                    ? CourseFee::where('created_by', $actorUserId)->count()
                    : CourseFee::count())
                : ($actorUserId ? 0 : $total),
            'updated_by_me' => $hasAudit
                ? (int) ($actorUserId
                    ? CourseFee::where('updated_by', $actorUserId)->count()
                    : CourseFee::count())
                : ($actorUserId ? 0 : $total),
            'created_today' => $this->countCreatedInRange(CourseFee::class, now()->startOfDay(), now()->endOfDay(), false, $hasAudit ? $actorUserId : null),
            'created_yesterday' => $this->countCreatedInRange(CourseFee::class, now()->subDay()->startOfDay(), now()->subDay()->endOfDay(), false, $hasAudit ? $actorUserId : null),
            'created_this_week' => $this->countCreatedInRange(CourseFee::class, $this->thisWeekBounds()[0], $this->thisWeekBounds()[1], false, $hasAudit ? $actorUserId : null),
            'created_last_week' => $this->countCreatedInRange(CourseFee::class, $this->lastWeekBounds()[0], $this->lastWeekBounds()[1], false, $hasAudit ? $actorUserId : null),
            'created_this_month' => $this->countCreatedInRange(CourseFee::class, now()->startOfMonth(), now()->endOfMonth(), false, $hasAudit ? $actorUserId : null),
            'created_this_year' => $this->countCreatedInRange(CourseFee::class, now()->startOfYear(), now()->endOfYear(), false, $hasAudit ? $actorUserId : null),
            'created_last_month' => $this->countCreatedInRange(
                CourseFee::class,
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth(),
                false,
                $hasAudit ? $actorUserId : null
            ),
            'created_in_range' => $this->countCreatedInRange(CourseFee::class, $from, $to, false, $hasAudit ? $actorUserId : null),
            'updated_today' => $this->countUpdatedInRange(CourseFee::class, now()->startOfDay(), now()->endOfDay(), false, $hasAudit ? $actorUserId : null),
            'updated_yesterday' => $this->countUpdatedInRange(CourseFee::class, now()->subDay()->startOfDay(), now()->subDay()->endOfDay(), false, $hasAudit ? $actorUserId : null),
            'updated_this_week' => $this->countUpdatedInRange(CourseFee::class, $this->thisWeekBounds()[0], $this->thisWeekBounds()[1], false, $hasAudit ? $actorUserId : null),
            'updated_last_week' => $this->countUpdatedInRange(CourseFee::class, $this->lastWeekBounds()[0], $this->lastWeekBounds()[1], false, $hasAudit ? $actorUserId : null),
            'updated_this_month' => $this->countUpdatedInRange(CourseFee::class, now()->startOfMonth(), now()->endOfMonth(), false, $hasAudit ? $actorUserId : null),
            'updated_last_month' => $this->countUpdatedInRange(
                CourseFee::class,
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth(),
                false,
                $hasAudit ? $actorUserId : null
            ),
            'updated_in_range' => $this->countUpdatedInRange(CourseFee::class, $from, $to, false, $hasAudit ? $actorUserId : null),
        ];
    }

    private function trainingDetailRows(string $metric, ?int $actorUserId, ?Carbon $from, ?Carbon $to, ?string $search): Collection
    {
        if (in_array($metric, ['courses_with_training', 'courses_without_training'], true)) {
            return $this->trainingCourseCoverageRows($metric, $search);
        }

        if (! Schema::hasTable('course_agendas')) {
            return collect();
        }

        $hasAudit = Schema::hasColumn('course_agendas', 'created_by');
        $query = CourseAgenda::query()->with(['course', 'country', 'createdBy', 'updatedBy']);
        $today = now()->startOfDay();

        match ($metric) {
            'past_dates', 'inactive' => $query->whereDate('to', '<', $today),
            'active' => $query->whereDate('to', '>=', $today),
            'created_by_me' => $hasAudit
                ? ($actorUserId ? $query->where('created_by', $actorUserId) : null)
                : ($actorUserId ? $query->whereRaw('1 = 0') : null),
            'updated_by_me' => $hasAudit
                ? ($actorUserId ? $query->where('updated_by', $actorUserId) : null)
                : ($actorUserId ? $query->whereRaw('1 = 0') : null),
            'created_today' => $this->applyCreatedByActorRange($query, $hasAudit ? $actorUserId : null, now()->startOfDay(), now()->endOfDay()),
            'created_yesterday' => $this->applyCreatedByActorRange($query, $hasAudit ? $actorUserId : null, now()->subDay()->startOfDay(), now()->subDay()->endOfDay()),
            'created_this_week' => $this->applyCreatedByActorRange($query, $hasAudit ? $actorUserId : null, $this->thisWeekBounds()[0], $this->thisWeekBounds()[1]),
            'created_last_week' => $this->applyCreatedByActorRange($query, $hasAudit ? $actorUserId : null, $this->lastWeekBounds()[0], $this->lastWeekBounds()[1]),
            'created_this_month' => $this->applyCreatedByActorRange($query, $hasAudit ? $actorUserId : null, now()->startOfMonth(), now()->endOfMonth()),
            'created_this_year' => $this->applyCreatedByActorRange($query, $hasAudit ? $actorUserId : null, now()->startOfYear(), now()->endOfYear()),
            'created_last_month' => $this->applyCreatedByActorRange(
                $query,
                $hasAudit ? $actorUserId : null,
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth()
            ),
            'created_in_range' => $this->applyCreatedByActorRange($query, $hasAudit ? $actorUserId : null, $from, $to),
            'updated_today' => $this->applyUpdatedByActorRange($query, $hasAudit ? $actorUserId : null, now()->startOfDay(), now()->endOfDay()),
            'updated_yesterday' => $this->applyUpdatedByActorRange($query, $hasAudit ? $actorUserId : null, now()->subDay()->startOfDay(), now()->subDay()->endOfDay()),
            'updated_this_week' => $this->applyUpdatedByActorRange($query, $hasAudit ? $actorUserId : null, $this->thisWeekBounds()[0], $this->thisWeekBounds()[1]),
            'updated_last_week' => $this->applyUpdatedByActorRange($query, $hasAudit ? $actorUserId : null, $this->lastWeekBounds()[0], $this->lastWeekBounds()[1]),
            'updated_this_month' => $this->applyUpdatedByActorRange($query, $hasAudit ? $actorUserId : null, now()->startOfMonth(), now()->endOfMonth()),
            'updated_last_month' => $this->applyUpdatedByActorRange(
                $query,
                $hasAudit ? $actorUserId : null,
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth()
            ),
            'updated_in_range' => $this->applyUpdatedByActorRange($query, $hasAudit ? $actorUserId : null, $from, $to),
            default => null,
        };

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', '%' . $search . '%')
                    ->orWhere('city', 'like', '%' . $search . '%')
                    ->orWhere('delivery_type', 'like', '%' . $search . '%')
                    ->orWhereHas('course', fn ($cq) => $cq->where('title', 'like', '%' . $search . '%'));
            });
        }

        return $query->orderByDesc('updated_at')->limit(10000)->get()->map(function (CourseAgenda $row) {
            $courseTitle = $row->course?->title ?: ('Course #' . $row->course_id);
            $country = $row->country?->name ?: 'International';
            $city = trim((string) ($row->city ?? ''));
            $location = $city !== '' ? ($country . ' / ' . $city) : $country;

            return [
                'id' => $row->id,
                'title' => $courseTitle,
                'course' => $courseTitle,
                'subject' => $row->subject ?: '—',
                'delivery_type' => $row->delivery_type_label ?: '—',
                'location' => $location,
                'from' => $row->from ? Carbon::parse($row->from)->format('M d, Y') : '—',
                'to' => $row->to ? Carbon::parse($row->to)->format('M d, Y') : '—',
                'created_by' => audit_user_name($row->createdBy, $row->created_by ?? null),
                'updated_by' => audit_user_name($row->updatedBy, $row->updated_by ?? null),
                'created_at' => optional($row->created_at)?->format('M d, Y H:i'),
                'updated_at' => optional($row->updated_at)?->format('M d, Y H:i'),
                'url' => route('admin.course-agendas.edit', ['training_calendar' => $row->id]),
            ];
        });
    }

    private function feePackageDetailRows(string $metric, ?int $actorUserId, ?Carbon $from, ?Carbon $to, ?string $search): Collection
    {
        if (! Schema::hasTable('course_fees')) {
            return collect();
        }

        $hasAudit = Schema::hasColumn('course_fees', 'created_by');
        $hasShow = Schema::hasColumn('course_fees', 'showonwebsite');
        $query = CourseFee::query()->with(['course']);

        match ($metric) {
            'active' => $hasShow ? $query->where('showonwebsite', 1) : null,
            'inactive' => $hasShow
                ? $query->where(function ($q) {
                    $q->where('showonwebsite', 0)->orWhereNull('showonwebsite');
                })
                : $query->whereRaw('1 = 0'),
            'created_by_me' => $hasAudit
                ? ($actorUserId ? $query->where('created_by', $actorUserId) : null)
                : ($actorUserId ? $query->whereRaw('1 = 0') : null),
            'updated_by_me' => $hasAudit
                ? ($actorUserId ? $query->where('updated_by', $actorUserId) : null)
                : ($actorUserId ? $query->whereRaw('1 = 0') : null),
            'created_today' => $this->applyCreatedByActorRange($query, $hasAudit ? $actorUserId : null, now()->startOfDay(), now()->endOfDay()),
            'created_yesterday' => $this->applyCreatedByActorRange($query, $hasAudit ? $actorUserId : null, now()->subDay()->startOfDay(), now()->subDay()->endOfDay()),
            'created_this_week' => $this->applyCreatedByActorRange($query, $hasAudit ? $actorUserId : null, $this->thisWeekBounds()[0], $this->thisWeekBounds()[1]),
            'created_last_week' => $this->applyCreatedByActorRange($query, $hasAudit ? $actorUserId : null, $this->lastWeekBounds()[0], $this->lastWeekBounds()[1]),
            'created_this_month' => $this->applyCreatedByActorRange($query, $hasAudit ? $actorUserId : null, now()->startOfMonth(), now()->endOfMonth()),
            'created_this_year' => $this->applyCreatedByActorRange($query, $hasAudit ? $actorUserId : null, now()->startOfYear(), now()->endOfYear()),
            'created_last_month' => $this->applyCreatedByActorRange(
                $query,
                $hasAudit ? $actorUserId : null,
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth()
            ),
            'created_in_range' => $this->applyCreatedByActorRange($query, $hasAudit ? $actorUserId : null, $from, $to),
            'updated_today' => $this->applyUpdatedByActorRange($query, $hasAudit ? $actorUserId : null, now()->startOfDay(), now()->endOfDay()),
            'updated_yesterday' => $this->applyUpdatedByActorRange($query, $hasAudit ? $actorUserId : null, now()->subDay()->startOfDay(), now()->subDay()->endOfDay()),
            'updated_this_week' => $this->applyUpdatedByActorRange($query, $hasAudit ? $actorUserId : null, $this->thisWeekBounds()[0], $this->thisWeekBounds()[1]),
            'updated_last_week' => $this->applyUpdatedByActorRange($query, $hasAudit ? $actorUserId : null, $this->lastWeekBounds()[0], $this->lastWeekBounds()[1]),
            'updated_this_month' => $this->applyUpdatedByActorRange($query, $hasAudit ? $actorUserId : null, now()->startOfMonth(), now()->endOfMonth()),
            'updated_last_month' => $this->applyUpdatedByActorRange(
                $query,
                $hasAudit ? $actorUserId : null,
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth()
            ),
            'updated_in_range' => $this->applyUpdatedByActorRange($query, $hasAudit ? $actorUserId : null, $from, $to),
            default => null,
        };

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('package_name', 'like', '%' . $search . '%')
                    ->orWhere('short_description', 'like', '%' . $search . '%')
                    ->orWhereHas('course', fn ($cq) => $cq->where('title', 'like', '%' . $search . '%'));
            });
        }

        return $query->orderByDesc('updated_at')->limit(10000)->get()->map(function (CourseFee $fee) use ($hasShow, $hasAudit) {
            $courseTitle = $fee->course?->title ?: ('Course #' . $fee->courses_id);
            $title = trim(($fee->package_name ?: 'Fee Package') . ' — ' . $courseTitle);
            $isActive = $hasShow ? (int) ($fee->showonwebsite ?? 0) === 1 : true;

            return [
                'id' => $fee->id,
                'title' => $title,
                'status' => $isActive ? 'Active' : 'Inactive',
                'type' => 'Fee Package',
                'seo_status' => '—',
                'seo_score' => '—',
                'created_by' => $hasAudit
                    ? audit_user_name(null, $fee->created_by ?? null)
                    : 'Admin',
                'updated_by' => $hasAudit
                    ? audit_user_name(null, $fee->updated_by ?? null)
                    : 'Admin',
                'created_at' => optional($fee->created_at)?->format('Y-m-d H:i'),
                'updated_at' => optional($fee->updated_at)?->format('Y-m-d H:i'),
                'url' => route('course.edit-fee', ['id' => $fee->id]),
            ];
        });
    }

    private function emptyStandardStats(): array
    {
        return [
            'total' => 0,
            'active' => 0,
            'inactive' => 0,
            'created_by_me' => 0,
            'updated_by_me' => 0,
            'created_today' => 0,
            'created_yesterday' => 0,
            'created_this_week' => 0,
            'created_last_week' => 0,
            'created_this_month' => 0,
            'created_this_year' => 0,
            'created_last_month' => 0,
            'created_in_range' => 0,
            'updated_today' => 0,
            'updated_yesterday' => 0,
            'updated_this_week' => 0,
            'updated_last_week' => 0,
            'updated_this_month' => 0,
            'updated_last_month' => 0,
            'updated_in_range' => 0,
        ];
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

        return $query->orderByDesc('updated_at')->limit(10000)->get()->map(function (Page $page) use ($analyzer) {
            $status = pages_status_enabled()
                ? (((int) ($page->status ?? 1) === 1) ? 'Active' : 'Inactive')
                : 'Active';
            $seo = PagesSEO::query()->where('page_id', $page->id)->first();
            [$seoStatus, $seoScore] = $this->seoStatusAndScore($seo, $analyzer);

            return [
                'id' => $page->id,
                'title' => $page->page_name ?: ('Page #' . $page->id),
                'status' => $status,
                'type' => 'Page',
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

        return $query->orderByDesc('updated_at')->limit(10000)->get()->map(function (Course $course) use ($analyzer) {
            $seo = PagesSEO::query()->where('course_id', $course->id)->first();
            [$seoStatus, $seoScore] = $this->seoStatusAndScore($seo, $analyzer);

            return [
                'id' => $course->id,
                'title' => $course->title ?: ('Course #' . $course->id),
                'status' => $course->deleted_at ? 'Inactive' : 'Active',
                'type' => 'Course',
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
            // Match dashboard cards: distinct Active page/course SEO rows only.
            'seo_entered_pages', 'page_seo' => $query->whereNotNull('page_id')
                ->whereIn('page_id', $this->activePagesQuery()->select('id'))
                ->whereIn('id', function ($q) {
                    $q->selectRaw('MAX(id)')->from('pages_s_e_o_s')->whereNotNull('page_id')->groupBy('page_id');
                }),
            'seo_entered_courses', 'course_seo' => $query->whereNotNull('course_id')
                ->whereIn('course_id', $this->activeCoursesQuery()->select('id'))
                ->whereIn('id', function ($q) {
                    $q->selectRaw('MAX(id)')->from('pages_s_e_o_s')->whereNotNull('course_id')->groupBy('course_id');
                }),
            'created_by_me' => $actorUserId ? $query->where('created_by', $actorUserId) : null,
            'updated_by_me' => $actorUserId ? $query->where('updated_by', $actorUserId) : null,
            'created_today' => $this->applyCreatedByActorRange($query, $actorUserId, now()->startOfDay(), now()->endOfDay()),
            'created_yesterday' => $this->applyCreatedByActorRange($query, $actorUserId, now()->subDay()->startOfDay(), now()->subDay()->endOfDay()),
            'created_this_week' => $this->applyCreatedByActorRange($query, $actorUserId, $this->thisWeekBounds()[0], $this->thisWeekBounds()[1]),
            'created_last_week' => $this->applyCreatedByActorRange($query, $actorUserId, $this->lastWeekBounds()[0], $this->lastWeekBounds()[1]),
            'created_this_month' => $this->applyCreatedByActorRange($query, $actorUserId, now()->startOfMonth(), now()->endOfMonth()),
            'created_this_year' => $this->applyCreatedByActorRange($query, $actorUserId, now()->startOfYear(), now()->endOfYear()),
            'created_last_month' => $this->applyCreatedByActorRange(
                $query,
                $actorUserId,
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth()
            ),
            'created_in_range' => $this->applyCreatedByActorRange($query, $actorUserId, $from, $to),
            'updated_today' => $this->applyUpdatedByActorRange($query, $actorUserId, now()->startOfDay(), now()->endOfDay()),
            'updated_yesterday' => $this->applyUpdatedByActorRange($query, $actorUserId, now()->subDay()->startOfDay(), now()->subDay()->endOfDay()),
            'updated_this_week' => $this->applyUpdatedByActorRange($query, $actorUserId, $this->thisWeekBounds()[0], $this->thisWeekBounds()[1]),
            'updated_last_week' => $this->applyUpdatedByActorRange($query, $actorUserId, $this->lastWeekBounds()[0], $this->lastWeekBounds()[1]),
            'updated_this_month' => $this->applyUpdatedByActorRange($query, $actorUserId, now()->startOfMonth(), now()->endOfMonth()),
            'updated_last_month' => $this->applyUpdatedByActorRange(
                $query,
                $actorUserId,
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth()
            ),
            'updated_in_range' => $this->applyUpdatedByActorRange($query, $actorUserId, $from, $to),
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

        return $query->orderByDesc('updated_at')->limit(10000)->get()->map(function (PagesSEO $seo) use ($analyzer) {
            $type = $seo->course_id ? 'Course' : 'Page';
            $linked = $seo->course_id
                ? ($seo->course?->title ?: ('Course #' . $seo->course_id))
                : ($seo->page?->page_name ?: ('Page #' . $seo->page_id));
            if ($seo->course_id) {
                $status = $seo->course && $seo->course->deleted_at ? 'Inactive' : 'Active';
            } else {
                $status = pages_status_enabled()
                    ? (((int) ($seo->page?->status ?? 1) === 1) ? 'Active' : 'Inactive')
                    : 'Active';
            }
            [$seoStatus, $seoScore] = $this->seoStatusAndScore($seo, $analyzer);

            return [
                'id' => $seo->id,
                'title' => trim((string) ($seo->title ?: $linked ?: ('SEO #' . $seo->id))),
                'status' => $status,
                'type' => $type,
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
        $query = $this->activePagesQuery()
            ->whereDoesntHave('seo')
            ->with(['createdBy', 'updatedBy']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('page_name', 'like', '%' . $search . '%')
                    ->orWhere('url', 'like', '%' . $search . '%');
            });
        }

        return $query->orderByDesc('updated_at')->limit(10000)->get()->map(function (Page $page) {
            return [
                'id' => $page->id,
                'title' => $page->page_name ?: ('Page #' . $page->id),
                'status' => 'Active',
                'type' => 'Page',
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
        $query = $this->activeCoursesQuery()
            ->whereDoesntHave('seo')
            ->with(['createdBy', 'updatedBy']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%');
            });
        }

        return $query->orderByDesc('updated_at')->limit(10000)->get()->map(function (Course $course) {
            return [
                'id' => $course->id,
                'title' => $course->title ?: ('Course #' . $course->id),
                'status' => 'Active',
                'type' => 'Course',
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

    private function trainingCourseCoverageRows(string $metric, ?string $search): Collection
    {
        if (! Schema::hasTable('course_agendas')) {
            return collect();
        }

        $query = $this->activeCoursesQuery()->with(['createdBy', 'updatedBy']);

        match ($metric) {
            'courses_with_training' => $query->whereHas('agendas'),
            'courses_without_training' => $query->whereDoesntHave('agendas'),
            default => $query->whereRaw('1 = 0'),
        };

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%');
            });
        }

        $coverageLabel = match ($metric) {
            'courses_with_training' => 'Has Calendar',
            'courses_without_training' => 'Missing',
            default => '—',
        };

        return $query->orderByDesc('updated_at')->limit(10000)->get()->map(function (Course $course) use ($coverageLabel) {
            $title = $course->title ?: ('Course #' . $course->id);

            return [
                'id' => $course->id,
                'title' => $title,
                'course' => $title,
                'coverage' => $coverageLabel,
                'created_by' => audit_user_name($course->createdBy, $course->created_by ?? null),
                'updated_by' => audit_user_name($course->updatedBy, $course->updated_by ?? null),
                'created_at' => optional($course->created_at)?->format('M d, Y H:i'),
                'updated_at' => optional($course->updated_at)?->format('M d, Y H:i'),
                'url' => route('course.edit', $course->id),
            ];
        });
    }

    private function activePagesQuery()
    {
        $query = Page::query();
        if (pages_status_enabled()) {
            $query->where('status', 1);
        }

        return $query;
    }

    private function activeCoursesQuery()
    {
        // Soft-deletes exclude disabled/inactive courses by default.
        return Course::query();
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
            'created_by_me' => $actorUserId ? $query->where('created_by', $actorUserId) : null,
            'updated_by_me' => $actorUserId ? $query->where('updated_by', $actorUserId) : null,
            'created_today' => $this->applyCreatedByActorRange($query, $actorUserId, now()->startOfDay(), now()->endOfDay()),
            'created_yesterday' => $this->applyCreatedByActorRange($query, $actorUserId, now()->subDay()->startOfDay(), now()->subDay()->endOfDay()),
            'created_this_week' => $this->applyCreatedByActorRange($query, $actorUserId, $this->thisWeekBounds()[0], $this->thisWeekBounds()[1]),
            'created_last_week' => $this->applyCreatedByActorRange($query, $actorUserId, $this->lastWeekBounds()[0], $this->lastWeekBounds()[1]),
            'created_this_month' => $this->applyCreatedByActorRange($query, $actorUserId, now()->startOfMonth(), now()->endOfMonth()),
            'created_this_year' => $this->applyCreatedByActorRange($query, $actorUserId, now()->startOfYear(), now()->endOfYear()),
            'created_last_month' => $this->applyCreatedByActorRange(
                $query,
                $actorUserId,
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth()
            ),
            'created_in_range' => $this->applyCreatedByActorRange($query, $actorUserId, $from, $to),
            'updated_today' => $this->applyUpdatedByActorRange($query, $actorUserId, now()->startOfDay(), now()->endOfDay()),
            'updated_yesterday' => $this->applyUpdatedByActorRange($query, $actorUserId, now()->subDay()->startOfDay(), now()->subDay()->endOfDay()),
            'updated_this_week' => $this->applyUpdatedByActorRange($query, $actorUserId, $this->thisWeekBounds()[0], $this->thisWeekBounds()[1]),
            'updated_last_week' => $this->applyUpdatedByActorRange($query, $actorUserId, $this->lastWeekBounds()[0], $this->lastWeekBounds()[1]),
            'updated_this_month' => $this->applyUpdatedByActorRange($query, $actorUserId, now()->startOfMonth(), now()->endOfMonth()),
            'updated_last_month' => $this->applyUpdatedByActorRange(
                $query,
                $actorUserId,
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth()
            ),
            'updated_in_range' => $this->applyUpdatedByActorRange($query, $actorUserId, $from, $to),
            default => null,
        };
    }

    private function applyCourseMetricFilters($query, string $metric, ?int $actorUserId, ?Carbon $from, ?Carbon $to): void
    {
        match ($metric) {
            'active' => $query->whereNull('deleted_at'),
            'inactive' => $query->whereNotNull('deleted_at'),
            'created_by_me' => $actorUserId ? $query->where('created_by', $actorUserId) : null,
            'updated_by_me' => $actorUserId ? $query->where('updated_by', $actorUserId) : null,
            'created_today' => $this->applyCreatedByActorRange($query, $actorUserId, now()->startOfDay(), now()->endOfDay()),
            'created_yesterday' => $this->applyCreatedByActorRange($query, $actorUserId, now()->subDay()->startOfDay(), now()->subDay()->endOfDay()),
            'created_this_week' => $this->applyCreatedByActorRange($query, $actorUserId, $this->thisWeekBounds()[0], $this->thisWeekBounds()[1]),
            'created_last_week' => $this->applyCreatedByActorRange($query, $actorUserId, $this->lastWeekBounds()[0], $this->lastWeekBounds()[1]),
            'created_this_month' => $this->applyCreatedByActorRange($query, $actorUserId, now()->startOfMonth(), now()->endOfMonth()),
            'created_this_year' => $this->applyCreatedByActorRange($query, $actorUserId, now()->startOfYear(), now()->endOfYear()),
            'created_last_month' => $this->applyCreatedByActorRange(
                $query,
                $actorUserId,
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth()
            ),
            'created_in_range' => $this->applyCreatedByActorRange($query, $actorUserId, $from, $to),
            'updated_today' => $this->applyUpdatedByActorRange($query, $actorUserId, now()->startOfDay(), now()->endOfDay()),
            'updated_yesterday' => $this->applyUpdatedByActorRange($query, $actorUserId, now()->subDay()->startOfDay(), now()->subDay()->endOfDay()),
            'updated_this_week' => $this->applyUpdatedByActorRange($query, $actorUserId, $this->thisWeekBounds()[0], $this->thisWeekBounds()[1]),
            'updated_last_week' => $this->applyUpdatedByActorRange($query, $actorUserId, $this->lastWeekBounds()[0], $this->lastWeekBounds()[1]),
            'updated_this_month' => $this->applyUpdatedByActorRange($query, $actorUserId, now()->startOfMonth(), now()->endOfMonth()),
            'updated_last_month' => $this->applyUpdatedByActorRange(
                $query,
                $actorUserId,
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth()
            ),
            'updated_in_range' => $this->applyUpdatedByActorRange($query, $actorUserId, $from, $to),
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

    private function applyCreatedByActorRange($query, ?int $actorUserId, ?Carbon $from, ?Carbon $to): void
    {
        if ($actorUserId) {
            $query->where('created_by', $actorUserId);
        }
        $this->applyCreatedRange($query, $from, $to);
    }

    private function applyUpdatedByActorRange($query, ?int $actorUserId, ?Carbon $from, ?Carbon $to): void
    {
        if ($actorUserId) {
            $query->where('updated_by', $actorUserId);
        }
        $this->applyUpdatedRange($query, $from, $to);
    }

    private function countCreatedInRange(string $modelClass, ?Carbon $from, ?Carbon $to, bool $withTrashed = false, ?int $actorUserId = null): int
    {
        $query = $this->baseQuery($modelClass, $withTrashed);
        $this->applyCreatedByActorRange($query, $actorUserId, $from, $to);

        return (int) $query->count();
    }

    private function countUpdatedInRange(string $modelClass, ?Carbon $from, ?Carbon $to, bool $withTrashed = false, ?int $actorUserId = null): int
    {
        $query = $this->baseQuery($modelClass, $withTrashed);
        $this->applyUpdatedByActorRange($query, $actorUserId, $from, $to);

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
            'created_last_week' => 0,
            'created_this_month' => 0,
            'created_this_year' => 0,
            'created_last_month' => 0,
            'created_in_range' => 0,
            'updated_today' => 0,
            'updated_yesterday' => 0,
            'updated_this_week' => 0,
            'updated_last_week' => 0,
            'updated_this_month' => 0,
            'updated_last_month' => 0,
            'updated_in_range' => 0,
        ];
    }


    /** @return array{0: Carbon, 1: Carbon} Monday-Sunday of current week */
    private function thisWeekBounds(): array
    {
        return [
            now()->startOfWeek(Carbon::MONDAY)->startOfDay(),
            now()->endOfWeek(Carbon::SUNDAY)->endOfDay(),
        ];
    }

    /** @return array{0: Carbon, 1: Carbon} Monday-Sunday of previous week */
    private function lastWeekBounds(): array
    {
        $start = now()->startOfWeek(Carbon::MONDAY)->subWeek()->startOfDay();

        return [
            $start,
            $start->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay(),
        ];
    }

    private function parseDateRange(?string $dateFrom, ?string $dateTo): array
    {
        $from = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : null;
        $to = $dateTo ? Carbon::parse($dateTo)->endOfDay() : null;

        return [$from, $to];
    }
}
