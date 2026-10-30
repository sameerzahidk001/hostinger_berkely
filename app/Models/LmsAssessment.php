<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
        'title', 'description', 'kind', 'audience', 'target_role', 'course_id', 'duration_minutes',
        'pass_percent', 'max_attempts', 'shuffle_questions', 'results_mode', 'allow_pause', 'status',
        'published_at', 'created_by_admin_id', 'created_by_user_id',
        'reference_files_json', 'preseen_files_json',
    ];

    /** Who a non-course (skill/staff) test is intended for. */
    public static function targetRoles(): array
    {
        return [
            'student' => 'Students',
            'instructor' => 'Instructors',
            'accountant' => 'Accountants',
            'content_writer' => 'Content Writers',
            'all_staff' => 'All staff (Instructor / Accountant / CW)',
        ];
    }

    public function targetRoleLabel(): string
    {
        if ($this->course_id) {
            return $this->course?->title ?: ('Course #' . $this->course_id);
        }
        $role = (string) ($this->target_role ?? '');

        return self::targetRoles()[$role] ?? ($role !== '' ? ucwords(str_replace('_', ' ', $role)) : '—');
    }

    protected $casts = [
        'duration_minutes' => 'integer',
        'pass_percent' => 'decimal:2',
        'max_attempts' => 'integer',
        'shuffle_questions' => 'boolean',
        'allow_pause' => 'boolean',
        'published_at' => 'datetime',
        'reference_files_json' => 'array',
        'preseen_files_json' => 'array',
    ];

    public static function kinds(): array
    {
        return [
            self::KIND_PRACTICE => 'Practice Test',
            self::KIND_MOCK => 'Mock Test',
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

    /** Assigned sitting rows: one per student assign, batch size per batch assign, plus leftover attempts. */
    public function assignedPeopleCount(): int
    {
        $assignments = $this->relationLoaded('assignments')
            ? $this->assignments
            : $this->assignments()->get(['id', 'assessment_id', 'student_id', 'class_batch_id']);
        $count = 0;
        $assignmentIds = [];
        $batchCounts = [];
        foreach ($assignments as $assignment) {
            $assignmentIds[] = (int) $assignment->id;
            if ($assignment->student_id) {
                $count++;
                continue;
            }
            if ($assignment->class_batch_id && Schema::hasTable('class_batch_student')) {
                $bid = (int) $assignment->class_batch_id;
                if (! isset($batchCounts[$bid])) {
                    $pivot = DB::table('class_batch_student')->where('class_batch_id', $bid);
                    $batchCounts[$bid] = (int) $pivot->count();
                    if ($batchCounts[$bid] === 0 && Schema::hasTable('class_schedules') && Schema::hasTable('class_schedule_student')) {
                        $scheduleIds = DB::table('class_schedules')->where('batch_id', $bid)->pluck('id');
                        $batchCounts[$bid] = $scheduleIds->isEmpty()
                            ? 0
                            : (int) DB::table('class_schedule_student')->whereIn('class_schedule_id', $scheduleIds)->distinct()->count('student_id');
                    }
                }
                $count += max(1, $batchCounts[$bid]);
            }
        }
        if (Schema::hasTable('lms_assessment_attempts')) {
            $orphanQuery = LmsAssessmentAttempt::query()->where('assessment_id', $this->id);
            if ($assignmentIds !== [] && Schema::hasColumn('lms_assessment_attempts', 'assignment_id')) {
                $orphanQuery->where(function ($q) use ($assignmentIds) {
                    $q->whereNull('assignment_id')
                        ->orWhere('assignment_id', 0)
                        ->orWhereNotIn('assignment_id', $assignmentIds);
                });
            } elseif ($assignmentIds !== []) {
                $orphanQuery->whereRaw('0 = 1');
            }
            $count += (int) $orphanQuery->count();
        }

        return $count;
    }

    public function studyFolders(): BelongsToMany
    {
        return $this->belongsToMany(
            StudyMaterialFolder::class,
            'study_material_folder_assessments',
            'assessment_id',
            'folder_id'
        )->withTimestamps();
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(LmsAssessmentAttempt::class, 'assessment_id');
    }

    /** Public display code, e.g. BST12 */
    public function displayCode(): string
    {
        return 'BST' . (int) $this->id;
    }

    public function createdByLabel(): string
    {
        if ($this->created_by_user_id) {
            $user = User::query()->find($this->created_by_user_id);

            return $user?->name ?: ('User #' . $this->created_by_user_id);
        }
        if ($this->created_by_admin_id && class_exists(Admin::class)) {
            $admin = Admin::query()->find($this->created_by_admin_id);
            if ($admin) {
                return $admin->name ?? $admin->email ?? 'Admin';
            }
        }

        return 'Admin';
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

    public function isPractice(): bool
    {
        return $this->kind === self::KIND_PRACTICE;
    }

    public function isInstantResults(): bool
    {
        return in_array((string) $this->results_mode, ['instant_mcq', 'instant'], true);
    }

    public function allowsPause(): bool
    {
        if (! Schema::hasColumn('lms_assessments', 'allow_pause')) {
            return false;
        }

        return (bool) $this->allow_pause;
    }

    /** Practice tests hide overall marks/%; only MCQ question marks stay visible. */
    public function hidesOverallMarks(): bool
    {
        return $this->isPractice();
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

    /**
     * Timed length: sum of per-question timers when any are set, else test-level duration.
     */
    public function durationMinutes(): ?int
    {
        $sum = 0;
        if (Schema::hasColumn('lms_questions', 'time_limit_minutes')) {
            $questions = $this->relationLoaded('questions')
                ? $this->questions
                : $this->questions()->get(['lms_questions.id', 'lms_questions.time_limit_minutes']);
            $sum = (int) $questions->sum(fn ($q) => (int) ($q->time_limit_minutes ?? 0));
        }
        if ($sum > 0) {
            return $sum;
        }
        $fallback = (int) ($this->duration_minutes ?? 0);

        return $fallback > 0 ? $fallback : null;
    }

    /** @return list<array{name:string,path:string,mime?:string}> */
    public function resourceFiles(string $kind): array
    {
        $col = $kind === 'preseen' ? 'preseen_files_json' : 'reference_files_json';
        if (! Schema::hasColumn('lms_assessments', $col)) {
            return [];
        }
        $raw = $this->{$col};
        if (! is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }
            $path = (string) ($row['url'] ?? $row['path'] ?? '');
            if ($path === '') {
                continue;
            }
            $out[] = [
                'name' => (string) ($row['name'] ?? basename($path)),
                'path' => $path,
                'url' => $path,
                'mime' => (string) ($row['mime'] ?? ''),
            ];
        }

        return array_slice($out, 0, 2);
    }

    public function hasResourceKind(string $kind): bool
    {
        return $this->resourceFiles($kind) !== [];
    }
}
