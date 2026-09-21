<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ClassSchedule extends Model
{
    public const RECURRENCE_NONE = 'none';
    public const RECURRENCE_DAILY = 'daily';
    public const RECURRENCE_WEEKLY = 'weekly';
    public const RECURRENCE_WEEKDAYS = 'weekdays';

    public const DAY_CODES = [
        'MO' => 'Mon',
        'TU' => 'Tue',
        'WE' => 'Wed',
        'TH' => 'Thu',
        'FR' => 'Fri',
        'SA' => 'Sat',
        'SU' => 'Sun',
    ];

    protected $fillable = [
        'batch_name',
        'batch_id',
        'course_id',
        'instructor_id',
        'head_of_faculty_id',
        'meeting_account_id',
        'scheduled_at',
        'timezone',
        'duration_minutes',
        'recurrence_type',
        'recurrence_days',
        'recurrence_count',
        'recurrence_until',
        'reminders',
        'zoho_link',
        'zoho_calendar_event_uid',
        'meeting_key',
        'title',
        'notes',
        'status',
        'created_by_admin_id',
        'created_by_user_id',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'recurrence_until' => 'date',
        'recurrence_days' => 'array',
        'reminders' => 'array',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ClassBatch::class, 'batch_id');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function headOfFaculty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_of_faculty_id');
    }

    public function meetingAccount(): BelongsTo
    {
        return $this->belongsTo(MeetingAccount::class, 'meeting_account_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_schedule_student', 'class_schedule_id', 'student_id')
            ->withTimestamps();
    }

    public function durationMinutes(): int
    {
        return max(15, (int) ($this->duration_minutes ?: 60));
    }

    public function endsAt(?Carbon $start = null)
    {
        $start = $start ?: $this->scheduled_at;

        return $start
            ? $start->copy()->addMinutes($this->durationMinutes())
            : null;
    }

    public function calendarTitle(): string
    {
        return $this->title ?: $this->batch_name;
    }

    public function timezoneName(): string
    {
        $tz = trim((string) ($this->timezone ?: ''));
        if ($tz === '') {
            $tz = (string) ($this->meetingAccount?->timezone ?: config('app.timezone', 'Asia/Dubai'));
        }

        return $tz !== '' ? $tz : 'Asia/Dubai';
    }

    public function timezoneLabel(): string
    {
        $tz = $this->timezoneName();
        try {
            $abbr = now($tz)->format('T');
        } catch (\Throwable $e) {
            $abbr = '';
        }

        return trim($tz . ($abbr !== '' && $abbr !== $tz ? ' · ' . $abbr : ''));
    }

    public static function timezoneOptions(): array
    {
        return [
            'Asia/Dubai' => 'Asia/Dubai (UAE)',
            'Asia/Karachi' => 'Asia/Karachi (Pakistan)',
            'Asia/Kolkata' => 'Asia/Kolkata (India)',
            'Europe/London' => 'Europe/London (UK)',
            'America/New_York' => 'America/New_York (US East)',
            'UTC' => 'UTC',
        ];
    }

    public static function ensureTimezoneColumn(): bool
    {
        if (! Schema::hasTable('class_schedules')) {
            return false;
        }

        if (Schema::hasColumn('class_schedules', 'timezone')) {
            return true;
        }

        Schema::table('class_schedules', function (Blueprint $table) {
            $table->string('timezone', 64)->nullable()->after('scheduled_at');
        });

        return Schema::hasColumn('class_schedules', 'timezone');
    }

    /**
     * Hostinger deploys often skip artisan migrate; ensure meeting_key for reschedule APIs.
     */
    public static function ensureMeetingKeyColumn(): bool
    {
        if (! Schema::hasTable('class_schedules')) {
            return false;
        }

        if (Schema::hasColumn('class_schedules', 'meeting_key')) {
            return true;
        }

        Schema::table('class_schedules', function (Blueprint $table) {
            $table->string('meeting_key', 64)->nullable()->after('zoho_link');
        });

        return Schema::hasColumn('class_schedules', 'meeting_key');
    }

    public function resolveMeetingKey(): ?string
    {
        $key = trim((string) ($this->meeting_key ?? ''));
        if ($key !== '') {
            return $key;
        }

        $link = trim((string) ($this->zoho_link ?? ''));
        if ($link === '') {
            return null;
        }

        if (preg_match('/[?&]key=([0-9A-Za-z_-]+)/', $link, $m)) {
            return $m[1];
        }
        if (preg_match('#/j/(\d+)#', $link, $m)) {
            return $m[1];
        }
        if (preg_match('#/meeting/(\d+)#', $link, $m)) {
            return $m[1];
        }

        return null;
    }

    public function recurrenceType(): string
    {
        $type = strtolower((string) ($this->recurrence_type ?: self::RECURRENCE_NONE));

        return in_array($type, [
            self::RECURRENCE_NONE,
            self::RECURRENCE_DAILY,
            self::RECURRENCE_WEEKLY,
            self::RECURRENCE_WEEKDAYS,
        ], true) ? $type : self::RECURRENCE_NONE;
    }

    public function isRecurring(): bool
    {
        return $this->recurrenceType() !== self::RECURRENCE_NONE;
    }

    public function recurrenceDayCodes(): array
    {
        $days = collect($this->recurrence_days ?? [])
            ->map(fn ($day) => strtoupper((string) $day))
            ->filter(fn ($day) => array_key_exists($day, self::DAY_CODES))
            ->unique()
            ->values()
            ->all();

        if ($this->recurrenceType() === self::RECURRENCE_WEEKDAYS) {
            return ['MO', 'TU', 'WE', 'TH', 'FR'];
        }

        if ($this->recurrenceType() === self::RECURRENCE_WEEKLY && $days === [] && $this->scheduled_at) {
            $map = [1 => 'MO', 2 => 'TU', 3 => 'WE', 4 => 'TH', 5 => 'FR', 6 => 'SA', 7 => 'SU'];

            return [$map[$this->scheduled_at->dayOfWeekIso] ?? 'MO'];
        }

        return $days;
    }

    /**
     * Default reminders matching Zoho Calendar create form.
     */
    public static function defaultReminders(): array
    {
        return [
            ['action' => 'email', 'minutes' => -1440],
            ['action' => 'email', 'minutes' => -2880],
            ['action' => 'email', 'minutes' => -5760],
            ['action' => 'popup', 'minutes' => -1440],
        ];
    }

    public function reminderList(): array
    {
        $rows = collect($this->reminders ?? [])
            ->map(function ($row) {
                $action = strtolower((string) ($row['action'] ?? 'email'));
                if (! in_array($action, ['email', 'popup', 'notification'], true)) {
                    $action = 'email';
                }
                $minutes = (int) ($row['minutes'] ?? 0);
                if ($minutes === 0) {
                    return null;
                }
                if ($minutes > 0) {
                    $minutes = -$minutes;
                }

                return ['action' => $action, 'minutes' => $minutes];
            })
            ->filter()
            ->values()
            ->all();

        return $rows !== [] ? $rows : self::defaultReminders();
    }

    public function zohoRrule(): ?string
    {
        $type = $this->recurrenceType();
        if ($type === self::RECURRENCE_NONE) {
            return null;
        }

        $parts = [];
        if ($type === self::RECURRENCE_DAILY) {
            $parts[] = 'FREQ=DAILY';
            $parts[] = 'INTERVAL=1';
        } else {
            $parts[] = 'FREQ=WEEKLY';
            $parts[] = 'INTERVAL=1';
            $days = $this->recurrenceDayCodes();
            if ($days !== []) {
                $parts[] = 'BYDAY=' . implode(',', $days);
            }
        }

        if ($this->recurrence_until) {
            $until = Carbon::parse($this->recurrence_until)->endOfDay()->utc()->format('Ymd\THis\Z');
            $parts[] = 'UNTIL=' . $until;
        } elseif ($this->recurrence_count) {
            $count = max(1, min(52, (int) $this->recurrence_count));
            $parts[] = 'COUNT=' . $count;
        }
        // "Never" ends: omit COUNT/UNTIL (Zoho infinite recurrence)

        return implode(';', $parts);
    }

    /**
     * Occurrence start datetimes for calendar display (includes first class).
     *
     * @return array<int, Carbon>
     */
    public function occurrenceStarts(int $max = 52): array
    {
        if (! $this->scheduled_at) {
            return [];
        }

        if (! $this->isRecurring()) {
            return [$this->scheduled_at->copy()];
        }

        $starts = [];
        $cursor = $this->scheduled_at->copy()->startOfDay();
        $hasUntil = (bool) $this->recurrence_until;
        $hasCount = (int) ($this->recurrence_count ?: 0) > 0;
        $endBoundary = $hasUntil
            ? Carbon::parse($this->recurrence_until)->endOfDay()
            : $this->scheduled_at->copy()->addYear();
        $limit = $hasUntil
            ? $max
            : ($hasCount ? max(1, min($max, (int) $this->recurrence_count)) : min($max, 52));

        $allowedDays = $this->recurrenceType() === self::RECURRENCE_DAILY
            ? null
            : $this->recurrenceDayCodes();
        $dayMap = [1 => 'MO', 2 => 'TU', 3 => 'WE', 4 => 'TH', 5 => 'FR', 6 => 'SA', 7 => 'SU'];

        $period = CarbonPeriod::create($cursor, '1 day', $endBoundary);
        foreach ($period as $day) {
            if (count($starts) >= $limit) {
                break;
            }

            if ($allowedDays !== null) {
                $code = $dayMap[$day->dayOfWeekIso] ?? null;
                if (! $code || ! in_array($code, $allowedDays, true)) {
                    continue;
                }
            }

            $start = $day->copy()->setTime(
                (int) $this->scheduled_at->format('H'),
                (int) $this->scheduled_at->format('i'),
                0
            );

            if ($start->lt($this->scheduled_at->copy()->subMinute())) {
                continue;
            }

            $starts[] = $start;
        }

        if ($starts === []) {
            $starts[] = $this->scheduled_at->copy();
        }

        return $starts;
    }

    public function toFullCalendarEvent(string $url): array
    {
        $color = $this->status === 'cancelled'
            ? '#ed5565'
            : ($this->status === 'completed' ? '#1ab394' : '#1c84c6');

        $events = [];
        foreach ($this->occurrenceStarts() as $index => $start) {
            $events[] = [
                'id' => $this->id . ($index > 0 ? '-' . $index : ''),
                'title' => $this->calendarTitle(),
                'start' => $start->toIso8601String(),
                'end' => optional($this->endsAt($start))->toIso8601String(),
                'url' => $url,
                'color' => $color,
            ];
        }

        return $events;
    }

    public function toIcsEvent(string $joinUrl = ''): string
    {
        $tz = config('app.timezone', 'Asia/Dubai');
        $start = $this->scheduled_at?->copy()->timezone($tz);
        $end = $this->endsAt()?->copy()->timezone($tz);
        $summary = $this->escapeIcs($this->calendarTitle());
        $description = $this->escapeIcs(trim(
            ($this->course->title ?? '') . "\n" .
            ($this->instructor?->name ? 'Instructor: ' . $this->instructor->name : '') . "\n" .
            ($this->notes ?: '') . "\n" .
            ($this->zoho_link ? 'Join: ' . $this->zoho_link : '')
        ));
        $uid = 'class-schedule-' . $this->id . '@' . parse_url(config('app.url'), PHP_URL_HOST);

        $lines = [
            'BEGIN:VEVENT',
            'UID:' . $uid,
            'DTSTAMP:' . now($tz)->format('Ymd\THis'),
            'DTSTART;TZID=' . $tz . ':' . ($start ? $start->format('Ymd\THis') : ''),
            'DTEND;TZID=' . $tz . ':' . ($end ? $end->format('Ymd\THis') : ''),
            'SUMMARY:' . $summary,
            'DESCRIPTION:' . $description,
        ];

        if ($rrule = $this->zohoRrule()) {
            $lines[] = 'RRULE:' . $rrule;
        }

        foreach ($this->reminderList() as $reminder) {
            $lines[] = 'BEGIN:VALARM';
            $lines[] = 'TRIGGER:' . $this->icsTrigger((int) $reminder['minutes']);
            $lines[] = 'ACTION:' . (strtolower($reminder['action']) === 'email' ? 'EMAIL' : 'DISPLAY');
            $lines[] = 'DESCRIPTION:Class reminder';
            $lines[] = 'END:VALARM';
        }

        if ($joinUrl || $this->zoho_link) {
            $lines[] = 'URL:' . ($joinUrl ?: $this->zoho_link);
        }
        $lines[] = 'END:VEVENT';

        return implode("\r\n", $lines);
    }

    public static function supportsRecurrenceColumns(): bool
    {
        return Schema::hasTable('class_schedules')
            && Schema::hasColumn('class_schedules', 'recurrence_type')
            && Schema::hasColumn('class_schedules', 'reminders');
    }

    /**
     * Hostinger deploys often skip artisan migrate; ensure recurrence UI columns exist.
     */
    public static function ensureRecurrenceColumns(): bool
    {
        if (! Schema::hasTable('class_schedules')) {
            return false;
        }

        if (self::supportsRecurrenceColumns()) {
            return true;
        }

        Schema::table('class_schedules', function (Blueprint $table) {
            if (! Schema::hasColumn('class_schedules', 'recurrence_type')) {
                $table->string('recurrence_type', 20)->default('none')->after('duration_minutes');
            }
            if (! Schema::hasColumn('class_schedules', 'recurrence_days')) {
                $table->json('recurrence_days')->nullable()->after('recurrence_type');
            }
            if (! Schema::hasColumn('class_schedules', 'recurrence_count')) {
                $table->unsignedSmallInteger('recurrence_count')->nullable()->after('recurrence_days');
            }
            if (! Schema::hasColumn('class_schedules', 'recurrence_until')) {
                $table->date('recurrence_until')->nullable()->after('recurrence_count');
            }
            if (! Schema::hasColumn('class_schedules', 'reminders')) {
                $table->json('reminders')->nullable()->after('recurrence_until');
            }
        });

        return self::supportsRecurrenceColumns();
    }

    protected function icsTrigger(int $minutes): string
    {
        $abs = abs($minutes);
        $prefix = $minutes <= 0 ? '-P' : 'P';
        if ($abs % 1440 === 0) {
            return $prefix . (int) ($abs / 1440) . 'D';
        }
        if ($abs % 60 === 0) {
            return $prefix . 'T' . (int) ($abs / 60) . 'H';
        }

        return $prefix . 'T' . $abs . 'M';
    }

    protected function escapeIcs(string $value): string
    {
        return str_replace(["\\", ";", ",", "\n", "\r"], ["\\\\", "\\;", "\\,", "\\n", ''], $value);
    }
}
