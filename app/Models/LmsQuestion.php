<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LmsQuestion extends Model
{
    use SoftDeletes;

    public const TYPE_MCQ = 'mcq';
    public const TYPE_ESSAY = 'essay';
    public const TYPE_SIMULATION = 'simulation';

    protected $table = 'lms_questions';

    protected $fillable = [
        'course_id', 'type', 'topic', 'stem', 'explanation', 'options_json',
        'max_marks', 'status', 'created_by_admin_id', 'created_by_user_id',
    ];

    protected $casts = [
        'options_json' => 'array',
        'max_marks' => 'decimal:2',
    ];

    public static function types(): array
    {
        return [
            self::TYPE_MCQ => 'MCQ',
            self::TYPE_ESSAY => 'Essay',
            self::TYPE_SIMULATION => 'Simulation / case',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class)->withTrashed();
    }

    public function assessments(): BelongsToMany
    {
        return $this->belongsToMany(LmsAssessment::class, 'lms_assessment_questions', 'question_id', 'assessment_id')
            ->withPivot(['sort_order', 'marks_override'])
            ->withTimestamps();
    }

    public function isMcq(): bool
    {
        return $this->type === self::TYPE_MCQ;
    }

    public function needsManualMark(): bool
    {
        return in_array($this->type, [self::TYPE_ESSAY, self::TYPE_SIMULATION], true);
    }

    public function options(): array
    {
        return is_array($this->options_json) ? $this->options_json : [];
    }

    public function correctOptionKeys(): array
    {
        $keys = [];
        foreach ($this->options() as $i => $opt) {
            if (! empty($opt['is_correct'])) {
                $keys[] = (string) ($opt['key'] ?? $i);
            }
        }

        return $keys;
    }

    public function typeLabel(): string
    {
        return self::types()[$this->type] ?? ucfirst((string) $this->type);
    }
}
