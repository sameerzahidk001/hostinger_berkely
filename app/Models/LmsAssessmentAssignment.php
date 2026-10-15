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
        'available_from', 'available_until',
        'assigned_by_admin_id', 'assigned_by_user_id',
    ];

    protected $casts = [
        'available_from' => 'datetime',
        'available_until' => 'datetime',
    ];

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

    /** Display: "15/10/24 22:25 → 16/10/24 01:25" */
    public function appointmentWindowLabel(string $fmt = 'd/m/y H:i'): string
    {
        $from = $this->available_from;
        $until = $this->appointmentEnd();
        if ($from && $until) {
            return $from->format($fmt) . ' → ' . $until->format($fmt);
        }
        if ($from) {
            return $from->format($fmt);
        }
        if ($until) {
            return 'Until ' . $until->format($fmt);
        }

        return 'Open';
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
