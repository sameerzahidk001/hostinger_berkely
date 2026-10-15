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

    protected $table = 'lms_questions';

    protected $fillable = [
        'course_id', 'target_role', 'type', 'topic', 'stem', 'explanation', 'options_json',
        'max_marks', 'status', 'created_by_admin_id', 'created_by_user_id',
    ];

    public static function targetRoles(): array
    {
        return LmsAssessment::targetRoles();
    }

    public function targetRoleLabel(): string
    {
        if ($this->course_id) {
            return $this->course?->title ?: ('Course #' . $this->course_id);
        }
        $role = (string) ($this->target_role ?? '');

        return self::targetRoles()[$role] ?? ($role !== '' ? ucwords(str_replace('_', ' ', $role)) : '—');
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
    ];

    public static function types(): array
    {
        return [
            self::TYPE_MCQ => 'MCQ',
            self::TYPE_ESSAY => 'Essay',
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
        return in_array($this->type, [self::TYPE_ESSAY, self::TYPE_SIMULATION, self::TYPE_CASE_STUDY], true);
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
