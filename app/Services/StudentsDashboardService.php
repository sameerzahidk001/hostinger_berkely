<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class StudentsDashboardService
{
    /** @var array<string, string>|null */
    private ?array $countryNameByIso = null;

    public function overviewStats(?string $customFrom = null, ?string $customTo = null): array
    {
        $ranges = $this->registrationRanges();
        $registration = [];
        foreach ($ranges as $key => $range) {
            $registration[$key] = $this->registrationQuery($range['from'] ?? null, $range['to'] ?? null)->count();
        }

        $registration['custom'] = ($customFrom && $customTo)
            ? $this->registrationQuery(
                Carbon::parse($customFrom)->startOfDay(),
                Carbon::parse($customTo)->endOfDay()
            )->count()
            : 0;

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
            'locations' => $this->locationStatsByCountry(),
            'enrolled_courses' => $this->enrolledCoursesStats(),
        ];
    }

    /**
     * Enrolled student counts by course/page fee for the doughnut under overview.
     *
     * @return array{labels: list<string>, values: list<int>, percents: list<int>, colors: list<string>, total: int}
     */
    public function enrolledCoursesStats(): array
    {
        $empty = [
            'labels' => ['No data'],
            'values' => [1],
            'percents' => [100],
            'colors' => ['#e0e0e0'],
            'total' => 0,
        ];

        if (! Schema::hasTable('payments')) {
            return $empty;
        }

        $with = ['installments'];
        if (Schema::hasTable('courses')) {
            $with[] = 'course:id,title';
        }
        if (Schema::hasTable('pages')) {
            $with[] = 'page:id,page_name';
        }
        if (Schema::hasTable('page_fee_services')) {
            $with[] = 'pageFeeService:id,name';
        }

        $columns = ['id', 'user_id', 'price'];
        foreach (['course_id', 'page_id', 'page_fee_service_id', 'page_fee_package_id', 'package_id', 'status'] as $column) {
            if (Schema::hasColumn('payments', $column)) {
                $columns[] = $column;
            }
        }

        $byLabel = [];
        Payment::query()
            ->with($with)
            ->whereNotNull('user_id')
            ->get($columns)
            ->each(function (Payment $payment) use (&$byLabel) {
                $status = payment_invoice_status($payment);
                if (! in_array($status, ['Paid', 'Partial'], true)) {
                    return;
                }

                $userId = (int) $payment->user_id;
                if ($userId <= 0) {
                    return;
                }

                $label = trim((string) $payment->programTitle());
                if ($label === '' || $label === 'Course #') {
                    $label = 'Unknown course';
                }

                if (! isset($byLabel[$label])) {
                    $byLabel[$label] = [];
                }
                $byLabel[$label][$userId] = true;
            });

        $totals = [];
        foreach ($byLabel as $label => $users) {
            $count = count($users);
            if ($count > 0) {
                $totals[$label] = $count;
            }
        }

        arsort($totals);

        // Keep the chart readable: top 12 courses, fold the rest into Other.
        if (count($totals) > 12) {
            $top = array_slice($totals, 0, 12, true);
            $other = array_sum(array_slice($totals, 12, null, true));
            $totals = $top;
            if ($other > 0) {
                $totals['Other'] = ($totals['Other'] ?? 0) + $other;
            }
        }

        return $this->toDonutSlices($totals);
    }

    /**
     * Student counts by country for the Locations doughnut under overview.
     *
     * @return array{labels: list<string>, values: list<int>, percents: list<int>, colors: list<string>, total: int}
     */
    public function locationStatsByCountry(): array
    {
        $colors = ['#4285f4', '#34a853', '#f4b400', '#ea4335', '#9c27b0', '#00acc1', '#ff6d00', '#5c6bc0', '#00897b', '#c0ca33'];

        if (! Schema::hasColumn('users', 'country')) {
            return [
                'labels' => ['No data'],
                'values' => [1],
                'percents' => [100],
                'colors' => ['#e0e0e0'],
                'total' => 0,
            ];
        }

        $rows = $this->registrationQuery(null, null)
            ->select('country')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('country')
            ->orderByDesc('total')
            ->get();

        $map = $this->countryNameMap();
        $totals = [];
        foreach ($rows as $row) {
            $raw = trim((string) ($row->country ?? ''));
            $label = $raw === ''
                ? 'Unknown'
                : ($map[strtoupper($raw)] ?? $raw);
            $totals[$label] = ($totals[$label] ?? 0) + (int) $row->total;
        }

        arsort($totals);

        return $this->toDonutSlices($totals, $colors);
    }

    /**
     * @param  array<string, int>  $totals
     * @param  list<string>|null  $colors
     * @return array{labels: list<string>, values: list<int>, percents: list<int>, colors: list<string>, total: int}
     */
    private function toDonutSlices(array $totals, ?array $colors = null): array
    {
        $colors = $colors ?? ['#4285f4', '#34a853', '#f4b400', '#ea4335', '#9c27b0', '#00acc1', '#ff6d00', '#5c6bc0', '#00897b', '#c0ca33'];
        $total = array_sum($totals);

        if ($total === 0) {
            return [
                'labels' => ['No data'],
                'values' => [1],
                'percents' => [100],
                'colors' => ['#e0e0e0'],
                'total' => 0,
            ];
        }

        $labels = [];
        $values = [];
        $percents = [];
        $sliceColors = [];
        $i = 0;
        foreach ($totals as $label => $value) {
            if ($value <= 0) {
                continue;
            }
            $labels[] = $label;
            $values[] = $value;
            $percents[] = (int) round(($value / $total) * 100);
            $sliceColors[] = $colors[$i % count($colors)];
            $i++;
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'percents' => $percents,
            'colors' => $sliceColors,
            'total' => $total,
        ];
    }

    /**
     * Person rows for Students Overview metric popups.
     *
     * @return array<string, array{title: string, people: array<int, array<string, mixed>>}>
     */
    public function overviewDetails(?string $customFrom = null, ?string $customTo = null): array
    {
        $ranges = $this->registrationRanges();
        $details = [];

        foreach ($ranges as $key => $range) {
            $people = $this->registrationQuery($range['from'] ?? null, $range['to'] ?? null)
                ->orderByDesc('created_at')
                ->limit(500)
                ->get($this->personSelectColumns())
                ->map(fn (User $user) => $this->personRow($user))
                ->values()
                ->all();

            $details['reg_' . $key] = [
                'title' => $range['title'],
                'people' => $people,
            ];
        }

        $details['reg_custom'] = [
            'title' => 'Registered Students - Custom Date Range',
            'people' => [],
        ];

        $enrollment = $this->enrollmentBuckets();
        $map = [
            'enrolled_total' => 'Enrolled Total Students',
            'enrolled_paid' => 'Invoice Fully Paid Students',
            'partially_paid_students' => 'Partially Paid Students',
            'invoice_unpaid_students' => 'Invoice Issued And Unpaid Students',
        ];

        foreach ($map as $key => $title) {
            $ids = $enrollment[$key] ?? collect();
            $people = User::query()
                ->whereIn('id', $ids->take(500)->all())
                ->orderBy('name')
                ->get($this->personSelectColumns())
                ->map(fn (User $user) => $this->personRow($user))
                ->values()
                ->all();

            $details['enr_' . $key] = [
                'title' => $title,
                'people' => $people,
            ];
        }

        return $details;
    }

    /**
     * @return array{title: string, count: int, people: array<int, array<string, mixed>>}
     */
    public function customRangePeople(?string $customFrom = null, ?string $customTo = null): array
    {
        $title = 'Registered Students - Custom Date Range';

        if (! $customFrom || ! $customTo) {
            return [
                'title' => $title,
                'count' => 0,
                'people' => [],
            ];
        }

        $from = Carbon::parse($customFrom)->startOfDay();
        $to = Carbon::parse($customTo)->endOfDay();
        $query = $this->registrationQuery($from, $to);
        $count = (clone $query)->count();
        $people = $query->orderByDesc('created_at')
            ->limit(500)
            ->get($this->personSelectColumns())
            ->map(fn (User $user) => $this->personRow($user))
            ->values()
            ->all();

        return [
            'title' => $title,
            'count' => $count,
            'people' => $people,
        ];
    }

    /**
     * @return array{title: string, count: int, people: array<int, array<string, mixed>>, date_from: ?string, date_to: ?string}
     */
    public function peopleForMetric(string $metric, ?string $customFrom = null, ?string $customTo = null): array
    {
        $metric = trim($metric);
        if ($metric === 'reg_custom' || $metric === 'custom') {
            $payload = $this->customRangePeople($customFrom, $customTo);

            return $payload + [
                'date_from' => $customFrom,
                'date_to' => $customTo,
            ];
        }

        $key = str_starts_with($metric, 'reg_') ? substr($metric, 4) : $metric;
        $ranges = $this->registrationRanges();
        if (! isset($ranges[$key])) {
            $key = 'total';
        }
        $range = $ranges[$key];
        $people = $this->registrationQuery($range['from'] ?? null, $range['to'] ?? null)
            ->orderByDesc('created_at')
            ->limit(5000)
            ->get($this->personSelectColumns())
            ->map(fn (User $user) => $this->personRow($user))
            ->values()
            ->all();

        return [
            'title' => $range['title'],
            'count' => count($people),
            'people' => $people,
            'date_from' => isset($range['from']) ? $range['from']->toDateString() : null,
            'date_to' => isset($range['to']) ? $range['to']->toDateString() : null,
        ];
    }

    private function registrationRanges(): array
    {
        $now = now();

        return [
            'total' => [
                'title' => 'Total Registered Students',
                'from' => null,
                'to' => null,
            ],
            'today' => [
                'title' => 'Registered Students Today',
                'from' => $now->copy()->startOfDay(),
                'to' => null,
            ],
            'yesterday' => [
                'title' => 'Registered Students Yesterday',
                'from' => $now->copy()->subDay()->startOfDay(),
                'to' => $now->copy()->subDay()->endOfDay(),
            ],
            'this_week' => [
                'title' => 'Registered Students This Week',
                'from' => $now->copy()->startOfWeek(Carbon::MONDAY),
                'to' => null,
            ],
            'last_week' => [
                'title' => 'Registered Students Last Week',
                'from' => $now->copy()->subWeek()->startOfWeek(Carbon::MONDAY),
                'to' => $now->copy()->subWeek()->endOfWeek(Carbon::SUNDAY),
            ],
            'this_month' => [
                'title' => 'Registered Students This Month',
                'from' => $now->copy()->startOfMonth(),
                'to' => null,
            ],
            'last_month' => [
                'title' => 'Registered Students Last Month',
                'from' => $now->copy()->subMonthNoOverflow()->startOfMonth(),
                'to' => $now->copy()->subMonthNoOverflow()->endOfMonth(),
            ],
            'this_year' => [
                'title' => 'Registered Students This Year',
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

    /**
     * @return list<string>
     */
    private function personSelectColumns(): array
    {
        $columns = ['id', 'name', 'email', 'created_at'];
        foreach (['mobile_number', 'gender', 'country', 'city', 'nationality'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                $columns[] = $column;
            }
        }

        return $columns;
    }

    private function personRow(User $user): array
    {
        $country = trim((string) ($user->country ?? ''));
        if ($country !== '') {
            $map = $this->countryNameMap();
            $country = $map[strtoupper($country)] ?? $country;
        }

        $phone = trim((string) ($user->mobile_number ?? ''));

        return [
            'id' => $user->id,
            'name' => $user->name ?: '—',
            'email' => $user->email ?: '—',
            'phone' => $phone !== '' ? $phone : '—',
            'gender' => $user->gender ?: '—',
            'country' => $country !== '' ? $country : '—',
            'city' => $user->city ?: '—',
            'nationality' => $user->nationality ?: '—',
            'registered_at' => optional($user->created_at)->format('Y-m-d H:i') ?: '—',
            'url' => route('users.edit', $user->id),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function countryNameMap(): array
    {
        if ($this->countryNameByIso !== null) {
            return $this->countryNameByIso;
        }

        $this->countryNameByIso = [];
        if (! class_exists(\App\Models\Country::class) || ! Schema::hasTable('countries')) {
            return $this->countryNameByIso;
        }

        $this->countryNameByIso = \App\Models\Country::query()
            ->get(['iso_code', 'name'])
            ->filter(fn ($row) => filled($row->iso_code))
            ->mapWithKeys(fn ($row) => [strtoupper((string) $row->iso_code) => (string) $row->name])
            ->all();

        return $this->countryNameByIso;
    }
}
