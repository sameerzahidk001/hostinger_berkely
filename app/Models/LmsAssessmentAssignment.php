<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class LmsAssessmentAssignment extends Model
{
    protected $table = 'lms_assessment_assignments';

    public const KIND_EXAM = 'exam';
    public const KIND_SELF = 'self';

    protected $fillable = [
        'assessment_id', 'class_batch_id', 'student_id', 'marker_user_id',
        'available_from', 'available_until', 'timezone', 'sitting_kind',
        'assigned_by_admin_id', 'assigned_by_user_id',
    ];

    protected $casts = [
        'available_from' => 'datetime',
        'available_until' => 'datetime',
    ];

    /** Public appointment / sitting code, e.g. BEXT12 */
    public function displayCode(): string
    {
        return 'BEXT' . (int) $this->id;
    }

    public function sittingKind(): string
    {
        if (! Schema::hasColumn($this->getTable(), 'sitting_kind')) {
            return self::KIND_EXAM;
        }

        return $this->sitting_kind === self::KIND_SELF ? self::KIND_SELF : self::KIND_EXAM;
    }

    public function isSelfSitting(): bool
    {
        return $this->sittingKind() === self::KIND_SELF;
    }

    public function sittingKindLabel(): string
    {
        return $this->isSelfSitting() ? 'Self test' : 'Test';
    }

    public static function timezoneOptions(): array
    {
        if (class_exists(ClassSchedule::class) && method_exists(ClassSchedule::class, 'timezoneOptions')) {
            return ClassSchedule::timezoneOptions();
        }

        return [
            'Asia/Dubai' => 'Asia/Dubai (UAE)',
            'Asia/Karachi' => 'Asia/Karachi (Pakistan)',
            'Asia/Kolkata' => 'Asia/Kolkata (India)',
            'Europe/London' => 'Europe/London (UK)',
            'America/New_York' => 'America/New_York (US East)',
            'UTC' => 'UTC',
        ];
    }

    public function timezoneName(): string
    {
        $tz = trim((string) ($this->timezone ?? ''));
        if ($tz === '') {
            $tz = (string) config('app.timezone', 'Asia/Dubai');
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

    /** Format a moment in this assignment's timezone (legacy rows without timezone stay as stored). */
    public function formatInTimezone($dt, string $fmt = 'd/m/y H:i'): string
    {
        if (! $dt) {
            return '—';
        }
        try {
            $carbon = $dt instanceof Carbon ? $dt->copy() : Carbon::parse($dt);
        } catch (\Throwable $e) {
            return '—';
        }
        if (filled($this->timezone)) {
            try {
                $carbon->setTimezone($this->timezoneName());
            } catch (\Throwable $e) {
                // keep stored clock
            }
        }

        $out = $carbon->format($fmt);
        if ($this->formatShowsClock($fmt)) {
            $out = $this->withTimezone($out);
        }

        return $out;
    }

    public function withTimezone(string $datetimeText): string
    {
        $text = trim($datetimeText);
        if ($text === '' || $text === '—' || stripos($text, 'anytime') !== false) {
            return $datetimeText;
        }
        $label = $this->timezoneLabel();
        if ($label === '' || str_contains($text, $label)) {
            return $datetimeText;
        }

        return $text . ' (' . $label . ')';
    }

    private function formatShowsClock(string $fmt): bool
    {
        if (in_array($fmt, ['Y-m-d H:i:s', 'Y-m-d H:i:s.u', 'Y-m-d H:i'], true)) {
            return false;
        }

        return (bool) preg_match('/[HghisAa]/', $fmt);
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(LmsAssessment::class, 'assessment_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ClassBatch::class, 'class_batch_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function marker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marker_user_id');
    }

    public function assignedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    /** Who created this assignment (Admin or panel user). */
    public function createdByLabel(): string
    {
        if ($this->assigned_by_user_id) {
            if ($this->relationLoaded('assignedByUser') && $this->assignedByUser) {
                return $this->assignedByUser->name ?: ('User #' . $this->assigned_by_user_id);
            }
            $user = User::query()->find($this->assigned_by_user_id);

            return $user?->name ?: ('User #' . $this->assigned_by_user_id);
        }
        if ($this->assigned_by_admin_id) {
            if (class_exists(\App\Models\Admin::class)) {
                $admin = \App\Models\Admin::query()->find($this->assigned_by_admin_id);
                if ($admin) {
                    return $admin->name ?? $admin->email ?? 'Admin';
                }
            }

            return 'Admin';
        }

        return 'Admin';
    }

    /**
     * Exam start as wall-clock in this sitting's timezone (matches Timing column).
     * Open/closed uses this so status always agrees with what the student sees.
     */
    public function windowStart(): ?Carbon
    {
        if (! $this->available_from) {
            return null;
        }
        $tz = $this->timezoneName();
        try {
            $wall = $this->formatInTimezone($this->available_from, 'Y-m-d H:i:s');

            return Carbon::createFromFormat('Y-m-d H:i:s', $wall, $tz);
        } catch (\Throwable $e) {
            try {
                return $this->available_from->copy()->timezone($tz);
            } catch (\Throwable $e2) {
                return $this->available_from->copy();
            }
        }
    }

    /**
     * Appointment window end = appointment start + exam duration (same timezone as start).
     * Student may start anytime within that window. Falls back to stored available_until.
     */
    public function appointmentEnd(): ?Carbon
    {
        $start = $this->windowStart();
        if ($start) {
            $assessment = $this->relationLoaded('assessment') ? $this->assessment : $this->assessment;
            $mins = $assessment ? (int) ($assessment->durationMinutes() ?? 0) : 0;
            if ($mins > 0) {
                return $start->copy()->addMinutes($mins);
            }
        }
        if (! $this->available_until) {
            return null;
        }
        $tz = $this->timezoneName();
        try {
            $wall = $this->formatInTimezone($this->available_until, 'Y-m-d H:i:s');
            $until = Carbon::createFromFormat('Y-m-d H:i:s', $wall, $tz);
        } catch (\Throwable $e) {
            $until = $this->available_until->copy();
        }
        // Zero-length from/until must not expire the sitting the instant it opens.
        if ($start && $until && $until->lte($start)) {
            return null;
        }

        return $until;
    }

    /** Display: "15/10/24 22:25 → 16/10/24 01:25 (Asia/Dubai · GST)". */
    public function appointmentWindowLabel(string $fmt = 'd/m/y H:i'): string
    {
        if ($this->isSelfSitting()) {
            return 'Self test — start anytime';
        }
        $from = $this->windowStart();
        $until = $this->appointmentEnd();
        $text = '';
        if ($from && $until) {
            $text = $from->format($fmt) . ' → ' . $until->format($fmt);
        } elseif ($from) {
            $text = $from->format($fmt);
        } elseif ($until) {
            $text = 'Until ' . $until->format($fmt);
        } else {
            return 'Open';
        }

        return $this->withTimezone($text);
    }

    public function isNotYetOpen(): bool
    {
        if ($this->isSelfSitting()) {
            return false;
        }
        $start = $this->windowStart();
        if (! $start) {
            return false;
        }

        return now()->lt($start);
    }

    public function isExpired(): bool
    {
        if ($this->isSelfSitting()) {
            return false;
        }
        $until = $this->appointmentEnd();
        if (! $until) {
            return false;
        }
        $now = now();

        // Still upcoming — not missed yet.
        if ($this->isNotYetOpen()) {
            return false;
        }

        $start = $this->windowStart();
        if ($start && $until->lte($start)) {
            return false;
        }

        return $now->gt($until);
    }

    public function isAvailableNow(): bool
    {
        return ! $this->isNotYetOpen() && ! $this->isExpired();
    }

    public function scopeLabel(): string
    {
        if ($this->batch) {
            return 'Batch: ' . $this->batch->displayLabel();
        }
        if ($this->student) {
            return 'Student: ' . ($this->student->name ?? '#' . $this->student_id);
        }

        return '—';
    }
}
