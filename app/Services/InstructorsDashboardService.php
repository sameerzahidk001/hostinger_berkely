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
        $ranges = $this->registrationRanges($customFrom, $customTo);
        $registration = [];
        foreach ($ranges as $key => $range) {
            $registration[$key] = $this->registrationQuery($range['from'] ?? null, $range['to'] ?? null)->count();
        }

        return ['registration' => $registration];
    }

    /**
     * @return array<string, array{title: string, people: array<int, array<string, mixed>>}>
     */
    public function overviewDetails(?string $customFrom = null, ?string $customTo = null): array
    {
        $ranges = $this->registrationRanges($customFrom, $customTo);
        $details = [];

        foreach ($ranges as $key => $range) {
            // Custom list is loaded on demand inside the popup (From/To at top).
            if ($key === 'custom') {
                $details['reg_' . $key] = [
                    'title' => $range['title'],
                    'people' => [],
                ];
                continue;
            }

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

        return $details;
    }

    /**
     * @return array{title: string, count: int, people: array<int, array<string, mixed>>}
     */
    public function customRangePeople(?string $customFrom = null, ?string $customTo = null): array
    {
        $from = $customFrom ? Carbon::parse($customFrom)->startOfDay() : now()->copy()->startOfMonth();
        $to = $customTo ? Carbon::parse($customTo)->endOfDay() : now()->copy()->endOfDay();
        $query = $this->registrationQuery($from, $to);
        $count = (clone $query)->count();
        $people = $query->orderByDesc('created_at')
            ->limit(500)
            ->get($this->personSelectColumns())
            ->map(fn (User $user) => $this->personRow($user))
            ->values()
            ->all();

        return [
            'title' => 'Registered Custom Date Range',
            'count' => $count,
            'people' => $people,
        ];
    }

    private function registrationRanges(?string $customFrom = null, ?string $customTo = null): array
    {
        $now = now();
        $customStart = $customFrom ? Carbon::parse($customFrom)->startOfDay() : $now->copy()->startOfMonth();
        $customEnd = $customTo ? Carbon::parse($customTo)->endOfDay() : $now->copy()->endOfDay();

        return [
            'total' => ['title' => 'Total Registered', 'from' => null, 'to' => null],
            'today' => ['title' => 'Registered Today', 'from' => $now->copy()->startOfDay(), 'to' => null],
            'yesterday' => [
                'title' => 'Registered Yesterday',
                'from' => $now->copy()->subDay()->startOfDay(),
                'to' => $now->copy()->subDay()->endOfDay(),
            ],
            'this_week' => ['title' => 'Registered This Week', 'from' => $now->copy()->startOfWeek(Carbon::MONDAY), 'to' => null],
            'last_week' => [
                'title' => 'Registered Last Week',
                'from' => $now->copy()->subWeek()->startOfWeek(Carbon::MONDAY),
                'to' => $now->copy()->subWeek()->endOfWeek(Carbon::SUNDAY),
            ],
            'this_month' => ['title' => 'Registered This Month', 'from' => $now->copy()->startOfMonth(), 'to' => null],
            'last_month' => [
                'title' => 'Registered Last Month',
                'from' => $now->copy()->subMonthNoOverflow()->startOfMonth(),
                'to' => $now->copy()->subMonthNoOverflow()->endOfMonth(),
            ],
            'this_year' => ['title' => 'Registered This Year', 'from' => $now->copy()->startOfYear(), 'to' => null],
            'custom' => [
                'title' => 'Registered Custom Date Range',
                'from' => $customStart,
                'to' => $customEnd,
            ],
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

    /**
     * @return list<string>
     */
    private function personSelectColumns(): array
    {
        $columns = ['id', 'name', 'email', 'created_at'];
        foreach (['gender', 'country', 'city', 'nationality'] as $column) {
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

        return [
            'id' => $user->id,
            'name' => $user->name ?: '—',
            'email' => $user->email ?: '—',
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
