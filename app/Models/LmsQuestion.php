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
    public const TYPE_CASE_STUDY = 'case_study';
    public const TYPE_READ = 'read';
    public const TYPE_TRANSLATION = 'translation';

    protected $table = 'lms_questions';

    protected $fillable = [
        'course_id', 'target_role', 'type', 'topic', 'stem', 'explanation', 'options_json',
        'max_marks', 'time_limit_minutes', 'status', 'created_by_admin_id', 'created_by_user_id',
    ];

    public static function targetRoles(): array
    {
        return LmsAssessment::targetRoles();
    }

    /** Question for (audience). Independent of Course. */
    public function targetRoleLabel(): string
    {
        $role = (string) ($this->target_role ?? '');
        if ($role === '') {
            return '—';
        }

        return self::targetRoles()[$role] ?? ucwords(str_replace('_', ' ', $role));
    }

    public function questionSetLabel(): string
    {
        return $this->targetRoleLabel();
    }

    /** Public display code, e.g. QB12 */
    public function displayCode(): string
    {
        return 'QB' . (int) $this->id;
    }

    public function createdByLabel(): string
    {
        if ($this->created_by_user_id) {
            $user = User::query()->find($this->created_by_user_id);

            return $user?->name ?: ('User #' . $this->created_by_user_id);
        }
        if ($this->created_by_admin_id) {
            if (class_exists(\App\Models\Admin::class)) {
                $admin = \App\Models\Admin::query()->find($this->created_by_admin_id);
                if ($admin) {
                    return $admin->name ?? $admin->email ?? 'Admin';
                }
            }

            return 'Admin';
        }

        return 'Admin';
    }

    protected $casts = [
        'options_json' => 'array',
        'max_marks' => 'decimal:2',
        'time_limit_minutes' => 'integer',
    ];

    public static function types(): array
    {
        return [
            self::TYPE_MCQ => 'MCQ',
            self::TYPE_ESSAY => 'Essay',
            self::TYPE_READ => 'Read',
            self::TYPE_TRANSLATION => 'Translation',
            self::TYPE_SIMULATION => 'Simulation',
            self::TYPE_CASE_STUDY => 'Case Study',
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
        return in_array($this->type, [
            self::TYPE_ESSAY,
            self::TYPE_READ,
            self::TYPE_TRANSLATION,
            self::TYPE_SIMULATION,
            self::TYPE_CASE_STUDY,
        ], true);
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


    /** Non-MCQ parts a/b/c stored in options_json when not MCQ. */
    public function subQuestions(): array
    {
        if ($this->isMcq()) {
            return [];
        }
        $raw = $this->options();
        $out = [];
        foreach ($raw as $i => $row) {
            if (! is_array($row)) {
                continue;
            }
            // Skip MCQ-shaped rows accidentally left behind.
            if (array_key_exists('is_correct', $row) && ! array_key_exists('marks', $row)) {
                continue;
            }
            $text = trim((string) ($row['text'] ?? $row['prompt'] ?? ''));
            $key = (string) ($row['key'] ?? (count($out) + 1));
            $marks = (float) ($row['marks'] ?? 0);
            if ($text === '' && $marks <= 0) {
                continue;
            }
            $out[] = [
                'key' => $key,
                'text' => $text,
                'marks' => $marks > 0 ? $marks : 0.0,
            ];
        }

        return $out;
    }

    public function hasSubQuestions(): bool
    {
        return count($this->subQuestions()) > 0;
    }

    public function typeLabel(): string
    {
        return self::types()[$this->type] ?? ucfirst((string) $this->type);
    }
}
