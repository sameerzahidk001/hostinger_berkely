<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LmsAttemptAnswer extends Model
{
    protected $table = 'lms_attempt_answers';

    protected $fillable = [
        'attempt_id', 'question_id', 'answer_json', 'auto_score', 'manual_score',
        'max_marks', 'feedback', 'is_correct', 'marked_by_admin_id', 'marked_by_user_id', 'marked_at',
    ];

    protected $casts = [
        'answer_json' => 'array',
        'auto_score' => 'decimal:2',
        'manual_score' => 'decimal:2',
        'max_marks' => 'decimal:2',
        'is_correct' => 'boolean',
        'marked_at' => 'datetime',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(LmsAssessmentAttempt::class, 'attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(LmsQuestion::class, 'question_id');
    }

    public function finalScore(): float
    {
        if ($this->manual_score !== null) {
            return (float) $this->manual_score;
        }
        if ($this->auto_score !== null) {
            return (float) $this->auto_score;
        }

        return 0.0;
    }

    public function textAnswer(): string
    {
        $data = $this->answer_json;
        if (! is_array($data)) {
            return '';
        }

        return (string) ($data['text'] ?? '');
    }

    public function selectedKey(): ?string
    {
        $data = $this->answer_json;
        if (! is_array($data)) {
            return null;
        }

        return isset($data['selected']) ? (string) $data['selected'] : null;
    }

    /** @return array<string, string> */
    public function partAnswers(): array
    {
        $data = $this->answer_json;
        if (! is_array($data)) {
            return [];
        }
        $parts = $data['parts'] ?? null;
        if (! is_array($parts)) {
            return [];
        }
        $out = [];
        foreach ($parts as $k => $v) {
            $out[(string) $k] = trim((string) $v);
        }

        return $out;
    }

    public function partAnswer(string $key): string
    {
        return $this->partAnswers()[$key] ?? '';
    }

    /** @return array<string, float> */
    public function partScores(): array
    {
        $data = $this->answer_json;
        $scores = is_array($data) ? ($data['part_scores'] ?? []) : [];
        if (! is_array($scores)) {
            return [];
        }
        $out = [];
        foreach ($scores as $k => $v) {
            if ($v === null || $v === '') {
                continue;
            }
            $out[(string) $k] = (float) $v;
        }

        return $out;
    }

    public function partScore(string $key): ?float
    {
        $scores = $this->partScores();
        if (! array_key_exists($key, $scores)) {
            return null;
        }

        return $scores[$key];
    }

    /** @return array<string, string> */
    public function partFeedbacks(): array
    {
        $data = $this->answer_json;
        $rows = is_array($data) ? ($data['part_feedback'] ?? []) : [];
        if (! is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $k => $v) {
            $out[(string) $k] = (string) $v;
        }

        return $out;
    }

    public function partFeedback(string $key): string
    {
        return $this->partFeedbacks()[$key] ?? '';
    }
}
