<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LmsAssessment extends Model
{
    use SoftDeletes;

    public const KIND_PRACTICE = 'practice';
    public const KIND_MOCK = 'mock';
    public const KIND_GENERAL = 'general';
    public const KIND_SKILL = 'skill';

    public const AUDIENCE_STUDENT = 'student';
    public const AUDIENCE_STAFF = 'staff';

    protected $table = 'lms_assessments';

    protected $fillable = [
        'title', 'description', 'kind', 'audience', 'course_id', 'duration_minutes',
        'pass_percent', 'max_attempts', 'shuffle_questions', 'results_mode', 'status',
        'published_at', 'created_by_admin_id', 'created_by_user_id',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'pass_percent' => 'decimal:2',
        'max_attempts' => 'integer',
        'shuffle_questions' => 'boolean',
        'published_at' => 'datetime',
    ];

    public static function kinds(): array
    {
        return [
            self::KIND_PRACTICE => 'Practice',
            self::KIND_MOCK => 'Mock Exam',
            self::KIND_GENERAL => 'General Test',
            self::KIND_SKILL => 'Skill Test',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(LmsQuestion::class, 'lms_assessment_questions', 'assessment_id', 'question_id')
            ->withPivot(['sort_order', 'marks_override'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(LmsAssessmentAssignment::class, 'assessment_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(LmsAssessmentAttempt::class, 'assessment_id');
    }

    public function kindLabel(): string
    {
        return self::kinds()[$this->kind] ?? ucfirst((string) $this->kind);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isMock(): bool
    {
        return $this->kind === self::KIND_MOCK;
    }

    public function isSkill(): bool
    {
        return $this->kind === self::KIND_SKILL || $this->audience === self::AUDIENCE_STAFF;
    }

    public function totalMarks(): float
    {
        $sum = 0.0;
        foreach ($this->questions as $q) {
            $sum += (float) ($q->pivot->marks_override ?? $q->max_marks);
        }

        return $sum;
    }
}
