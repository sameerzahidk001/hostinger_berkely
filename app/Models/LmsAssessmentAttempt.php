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
            'submitted' => 'Submitted',
            'graded' => 'Graded',
            'expired' => 'Expired',
            default => ucfirst((string) $this->status),
        };
    }

    public function passFailLabel(): string
    {
        if ($this->passed === null) {
            return '—';
        }

        return $this->passed ? 'Pass' : 'Fail';
    }
}
