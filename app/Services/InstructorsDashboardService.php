<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class InstructorsDashboardService
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

        // Custom range has no default dates — count stays 0 until the admin searches in the popup.
        $registration['custom'] = ($customFrom && $customTo)
            ? $this->registrationQuery(
                Carbon::parse($customFrom)->startOfDay(),
                Carbon::parse($customTo)->endOfDay()
            )->count()
            : 0;

        $registration['on_website'] = $this->onWebsiteQuery()->count();

        return ['registration' => $registration];
    }

    /**
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

        // Loaded on demand inside the popup when From/To are chosen.
        $details['reg_custom'] = [
            'title' => 'Registered Instructors - Custom Date Range',
            'people' => [],
        ];

        $onWebPeople = $this->onWebsiteQuery()
            ->orderBy('name')
            ->limit(500)
            ->get($this->personSelectColumns())
            ->map(fn (User $user) => $this->personRow($user))
            ->values()
            ->all();

        $details['reg_on_website'] = [
            'title' => 'Instructors Showing on Website',
            'people' => $onWebPeople,
        ];

        return $details;
    }

    /**
     * @return array{title: string, count: int, people: array<int, array<string, mixed>>}
     */
    public function customRangePeople(?string $customFrom = null, ?string $customTo = null): array
    {
        $title = 'Registered Instructors - Custom Date Range';

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

    private function registrationRanges(): array
    {
        $now = now();

        return [
            'total' => ['title' => 'Total Registered Instructors', 'from' => null, 'to' => null],
            'today' => ['title' => 'Registered Instructors Today', 'from' => $now->copy()->startOfDay(), 'to' => null],
            'yesterday' => [
                'title' => 'Registered Instructors Yesterday',
                'from' => $now->copy()->subDay()->startOfDay(),
                'to' => $now->copy()->subDay()->endOfDay(),
            ],
            'this_week' => ['title' => 'Registered Instructors This Week', 'from' => $now->copy()->startOfWeek(Carbon::MONDAY), 'to' => null],
            'last_week' => [
                'title' => 'Registered Instructors Last Week',
                'from' => $now->copy()->subWeek()->startOfWeek(Carbon::MONDAY),
                'to' => $now->copy()->subWeek()->endOfWeek(Carbon::SUNDAY),
            ],
            'this_month' => ['title' => 'Registered Instructors This Month', 'from' => $now->copy()->startOfMonth(), 'to' => null],
            'last_month' => [
                'title' => 'Registered Instructors Last Month',
                'from' => $now->copy()->subMonthNoOverflow()->startOfMonth(),
                'to' => $now->copy()->subMonthNoOverflow()->endOfMonth(),
            ],
            'this_year' => ['title' => 'Registered Instructors This Year', 'from' => $now->copy()->startOfYear(), 'to' => null],
        ];
    }

    private function registrationQuery(?Carbon $from, ?Carbon $to)
    {
        $query = User::query()->whereHas('roles', function ($q) {
            $q->whereRaw('LOWER(name) = ?', ['instructor']);
        });

        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        return $query;
    }

    private function onWebsiteQuery()
    {
        $query = $this->registrationQuery(null, null);

        if (Schema::hasColumn('users', 'is_on_web')) {
            $query->where('is_on_web', 1);
        }

        if (Schema::hasColumn('users', 'approved')) {
            $query->where('approved', 1);
        }

        return $query;
    }

    /**
     * @return list<string>
     */
    private function personSelectColumns(): array
    {
        $columns = ['id', 'name', 'email', 'created_at'];
        foreach (['mobile_number', 'gender', 'country', 'city', 'nationality', 'is_on_web'] as $column) {
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
