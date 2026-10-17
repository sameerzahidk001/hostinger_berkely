<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LmsAssessmentAttempt extends Model
{
    protected $table = 'lms_assessment_attempts';

    protected $fillable = [
        'assessment_id', 'user_id', 'status', 'started_at', 'submitted_at', 'expires_at',
        'score_raw', 'score_max', 'passed', 'grader_feedback', 'results_published',
        'results_published_at', 'graded_by_admin_id', 'graded_by_user_id', 'graded_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'expires_at' => 'datetime',
        'score_raw' => 'decimal:2',
        'score_max' => 'decimal:2',
        'passed' => 'boolean',
        'results_published' => 'boolean',
        'results_published_at' => 'datetime',
        'graded_at' => 'datetime',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(LmsAssessment::class, 'assessment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(LmsAttemptAnswer::class, 'attempt_id');
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isTimedOut(): bool
    {
        return $this->expires_at && now()->gt($this->expires_at) && $this->isInProgress();
    }

    public function percent(): ?float
    {
        if ($this->score_max === null || (float) $this->score_max <= 0 || $this->score_raw === null) {
            return null;
        }

        return round(((float) $this->score_raw / (float) $this->score_max) * 100, 1);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'in_progress' => 'In progress',
            'submitted' => 'Marking Required',
            'graded' => 'Completed and Marked',
            'expired' => 'Missed',
            default => ucfirst((string) $this->status),
        };
    }

    /** CSS class key for status colour in tables. */
    public function statusColorClass(): string
    {
        return match ($this->status) {
            'submitted' => 'lms-status-completed',
            'graded' => 'lms-status-marked',
            'in_progress' => 'lms-status-in_progress',
            'expired' => 'lms-status-missed',
            default => 'lms-status-' . $this->status,
        };
    }

    /** Minutes spent from start to submit (null if incomplete). Capped at exam duration. */
    public function spendMinutes(): ?int
    {
        if (! $this->started_at || ! $this->submitted_at) {
            return null;
        }

        $spent = (int) max(0, $this->started_at->diffInMinutes($this->submitted_at));
        $assessment = $this->assessment;
        $cap = $assessment ? (int) ($assessment->durationMinutes() ?? 0) : 0;
        if ($cap > 0) {
            return min($spent, $cap);
        }

        return $spent;
    }

    public function assessorLabel(?LmsAssessmentAssignment $assignment = null): string
    {
        if ($this->graded_by_user_id) {
            $user = User::query()->find($this->graded_by_user_id);

            return $user?->name ?: ('User #' . $this->graded_by_user_id);
        }
        if ($this->graded_by_admin_id) {
            if (class_exists(\App\Models\Admin::class)) {
                $admin = \App\Models\Admin::query()->find($this->graded_by_admin_id);
                if ($admin) {
                    return $admin->name ?? $admin->email ?? 'Admin';
                }
            }

            return 'Admin';
        }
        if ($assignment && $assignment->marker) {
            return $assignment->marker->name ?? '—';
        }

        return '—';
    }

    /** User id for linking assessor profile, when the assessor is a portal user. */
    public function assessorUserId(?LmsAssessmentAssignment $assignment = null): ?int
    {
        if ($this->graded_by_user_id) {
            return (int) $this->graded_by_user_id;
        }
        if ($assignment && $assignment->marker_user_id) {
            return (int) $assignment->marker_user_id;
        }

        return null;
    }

    public function passFailLabel(): string
    {
        if ($this->passed === null) {
            return '—';
        }

        return $this->passed ? 'Pass' : 'Fail';
    }
}
