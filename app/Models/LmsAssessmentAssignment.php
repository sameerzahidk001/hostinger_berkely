<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LmsAssessmentAssignment extends Model
{
    protected $table = 'lms_assessment_assignments';

    protected $fillable = [
        'assessment_id', 'class_batch_id', 'student_id', 'marker_user_id',
        'available_from', 'available_until', 'timezone',
        'assigned_by_admin_id', 'assigned_by_user_id',
    ];

    protected $casts = [
        'available_from' => 'datetime',
        'available_until' => 'datetime',
    ];

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

        return $carbon->format($fmt);
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
     * Appointment window end = appointment start + exam duration.
     * Student may start anytime within that window. Falls back to stored available_until.
     */
    public function appointmentEnd(): ?Carbon
    {
        if ($this->available_from) {
            $mins = (int) ($this->relationLoaded('assessment')
                ? ($this->assessment?->duration_minutes ?? 0)
                : ($this->assessment()->value('duration_minutes') ?? 0));
            if ($mins > 0) {
                return $this->available_from->copy()->addMinutes($mins);
            }
        }

        return $this->available_until;
    }

    /** Display: "15/10/24 22:25 → 16/10/24 01:25 (Asia/Dubai · GST)" */
    public function appointmentWindowLabel(string $fmt = 'd/m/y H:i'): string
    {
        $from = $this->available_from;
        $until = $this->appointmentEnd();
        $tzSuffix = ' (' . $this->timezoneLabel() . ')';
        if ($from && $until) {
            return $this->formatInTimezone($from, $fmt) . ' → ' . $this->formatInTimezone($until, $fmt) . $tzSuffix;
        }
        if ($from) {
            return $this->formatInTimezone($from, $fmt) . $tzSuffix;
        }
        if ($until) {
            return 'Until ' . $this->formatInTimezone($until, $fmt) . $tzSuffix;
        }

        return 'Open' . $tzSuffix;
    }

    public function isNotYetOpen(): bool
    {
        if (! $this->available_from) {
            return false;
        }

        return now()->lt($this->available_from);
    }

    public function isExpired(): bool
    {
        $until = $this->appointmentEnd();
        if (! $until) {
            return false;
        }

        // Same from/until (zero-length window) expires as soon as that timestamp is reached.
        if ($this->available_from && $this->available_from->equalTo($until)) {
            return now()->gte($until);
        }

        // Still upcoming — not missed yet.
        if ($this->isNotYetOpen()) {
            return false;
        }

        return now()->gt($until);
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
