<?php

namespace App\Models;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LmsAssessmentAttempt extends Model
{
    protected $table = 'lms_assessment_attempts';

    protected $fillable = [
        'assessment_id', 'assignment_id', 'user_id', 'status', 'started_at', 'submitted_at', 'expires_at',
        'paused_at', 'pause_seconds', 'pause_log_json',
        'score_raw', 'score_max', 'passed', 'grader_feedback', 'results_published',
        'results_published_at', 'graded_by_admin_id', 'graded_by_user_id', 'graded_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'expires_at' => 'datetime',
        'paused_at' => 'datetime',
        'pause_seconds' => 'integer',
        'pause_log_json' => 'array',
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
        if ($this->isPaused()) {
            return false;
        }

        return $this->expires_at && now()->gt($this->expires_at) && $this->isInProgress();
    }

    public function isPaused(): bool
    {
        if (! $this->isInProgress()) {
            return false;
        }
        if (session()->has($this->pauseSessionKey())) {
            return true;
        }
        if (! Schema::hasColumn('lms_assessment_attempts', 'paused_at')) {
            return false;
        }

        return $this->paused_at !== null;
    }

    public function pauseSessionKey(): string
    {
        return 'lms_attempt_pause_' . (int) $this->id;
    }

    public function pauseLog(): array
    {
        if (! Schema::hasColumn('lms_assessment_attempts', 'pause_log_json')) {
            return [];
        }
        $raw = $this->pause_log_json;
        if (! is_array($raw)) {
            return [];
        }

        return array_values(array_filter($raw, fn ($row) => is_array($row)));
    }

    public function pauseSecondsTotal(): int
    {
        $base = 0;
        if (Schema::hasColumn('lms_assessment_attempts', 'pause_seconds')) {
            $base = (int) ($this->pause_seconds ?? 0);
        }
        $sess = session($this->pauseSessionKey());
        $started = null;
        if (is_array($sess) && ! empty($sess['started_at'])) {
            try {
                $started = \Carbon\Carbon::parse($sess['started_at']);
            } catch (\Throwable $e) {
                $started = null;
            }
        }
        if (! $started && $this->paused_at) {
            $started = $this->paused_at;
        }
        if ($started) {
            $base += max(0, $started->diffInSeconds(now()));
        }

        return $base;
    }

    public function pauseDurationLabel(): string
    {
        $s = $this->pauseSecondsTotal();
        if ($s <= 0) {
            return '—';
        }
        $h = intdiv($s, 3600);
        $m = intdiv($s % 3600, 60);
        $sec = $s % 60;
        if ($h > 0) {
            return $h . 'h ' . $m . 'm ' . $sec . 's';
        }
        if ($m > 0) {
            return $m . 'm ' . $sec . 's';
        }

        return $sec . 's';
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
        $assessment = $this->assessment;
        if ($assessment && $assessment->isPractice() && in_array($this->status, ['submitted', 'graded', 'expired'], true)) {
            return 'Completed';
        }

        return match ($this->status) {
            'in_progress' => 'In progress',
            'submitted' => 'Marking Required',
            'graded' => 'Completed and Marked',
            // Started attempts that auto-submit at time-up are still a submission, not a missed sitting.
            'expired' => 'Marking Required',
            default => ucfirst((string) $this->status),
        };
    }

    /** CSS class key for status colour in tables. */
    public function statusColorClass(): string
    {
        $assessment = $this->assessment;
        if ($assessment && $assessment->isPractice() && in_array($this->status, ['submitted', 'graded', 'expired'], true)) {
            return 'lms-status-marked';
        }

        return match ($this->status) {
            'submitted' => 'lms-status-completed',
            'graded' => 'lms-status-marked',
            'in_progress' => 'lms-status-in_progress',
            'expired' => 'lms-status-completed',
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
        $pauseMin = (int) floor($this->pauseSecondsTotal() / 60);
        $spent = max(0, $spent - $pauseMin);
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
