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

    /** @var array<string, array<int, int>> */
    protected $assignedRosterByKindCache = [];

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

    public function primaryExamAssignment(): ?LmsAssessmentAssignment
    {
        $asgs = $this->relationLoaded('assignments')
            ? $this->assignments
            : $this->assignments()->get();
        $timed = $asgs->first(function ($a) {
            return ! $a->isSelfSitting() && $a->windowStart();
        });
        if ($timed) {
            return $timed;
        }

        return $asgs->first(fn ($a) => ! $a->isSelfSitting()) ?: $asgs->first();
    }

    public function appointmentSummaryLabel(): string
    {
        $asg = $this->primaryExamAssignment();

        return $asg ? $asg->appointmentStartLabel() : '—';
    }

    public function isCimaScsNovemberTitle(): bool
    {
        $title = mb_strtolower(trim((string) ($this->title ?? '')));
        if ($title === '' || str_contains($title, 'masterclass') || str_contains($title, 'mcs') || str_contains($title, 'management case')) {
            return false;
        }

        return str_contains($title, 'scs') && str_contains($title, 'mock');
    }

    /** Only SCS Mock 1 and Mock 2 were assigned to people. Mock 3–7 stay unassigned. */
    public function isCimaScsAssignedMockTitle(): bool
    {
        if (! $this->isCimaScsNovemberTitle()) {
            return false;
        }
        $title = mb_strtolower(trim((string) ($this->title ?? '')));

        return (bool) preg_match('/\bmock\s*[12]\b/', $title);
    }

    public function markerUsers()
    {
        if ($this->isCimaScsNovemberTitle() && ! $this->isCimaScsAssignedMockTitle()) {
            return collect();
        }
        $asgs = $this->relationLoaded('assignments')
            ? $this->assignments
            : $this->assignments()->with('marker')->get();

        $markers = $asgs->map(function ($a) {
            if ($a->relationLoaded('marker') && $a->marker) {
                return $a->marker;
            }
            if (! empty($a->marker_user_id)) {
                return User::query()->find((int) $a->marker_user_id);
            }

            return null;
        })->filter()->unique('id')->values();
        if ($markers->isNotEmpty() || ! $this->isCimaScsAssignedMockTitle()) {
            return $markers;
        }
        try {
            $batch = ClassBatch::query()->where('code', 'BAT-0004')->first()
                ?: ClassBatch::query()->where('code', 'like', '%0004%')->first();
            if (! $batch) {
                return $markers;
            }
            $batch->loadMissing(['instructors', 'headOfFaculty']);
            $trainer = method_exists($batch, 'trainerInstructors')
                ? $batch->trainerInstructors()->first()
                : $batch->instructors->first();
            if ($trainer && trim((string) ($trainer->name ?? '')) !== '') {
                return collect([$trainer]);
            }
        } catch (\Throwable $e) {
            return $markers;
        }

        return $markers;
    }

    /** Unique people assigned to this test: every sitting/batch, including classmates who never started. */
    public function assignedPeopleCount(?string $sittingKind = null): int
    {
        return $this->assignedRosterUserIds($sittingKind)->count();
    }

    /** Unique people from that same roster who started or submitted. */
    public function takenPeopleCount(?string $sittingKind = null): int
    {
        $ids = $this->assignedRosterUserIds($sittingKind);
        if ($ids->isEmpty() || ! Schema::hasTable('lms_assessment_attempts')) {
            return 0;
        }
        $aids = $this->sameTitleAssessmentIds();
        $asgIds = $this->assignmentIdsForSittingKind($sittingKind);
        $q = LmsAssessmentAttempt::query()
            ->whereIn('assessment_id', $aids)
            ->whereIn('user_id', $ids->all())
            ->where(function ($q) {
                $q->whereNotNull('started_at')->orWhereNotNull('submitted_at');
            });
        if ($sittingKind === LmsAssessmentAssignment::KIND_SELF) {
            if ($asgIds === []) {
                return 0;
            }
            $q->whereIn('assignment_id', $asgIds);
        } elseif ($sittingKind === LmsAssessmentAssignment::KIND_EXAM) {
            $selfAsgIds = $this->assignmentIdsForSittingKind(LmsAssessmentAssignment::KIND_SELF);
            if ($selfAsgIds !== []) {
                $q->where(function ($qq) use ($selfAsgIds) {
                    $qq->whereNull('assignment_id')->orWhereNotIn('assignment_id', $selfAsgIds);
                });
            }
        }

        return (int) $q->distinct()->count('user_id');
    }

    /** @return array<int, int> */
    protected function assignmentIdsForSittingKind(?string $sittingKind): array
    {
        if (! $sittingKind) {
            return [];
        }
        $aids = $this->sameTitleAssessmentIds();
        try {
            $rows = LmsAssessmentAssignment::query()->whereIn('assessment_id', $aids)->get();
        } catch (\Throwable $e) {
            return [];
        }
        $wantSelf = $sittingKind === LmsAssessmentAssignment::KIND_SELF;

        return $rows->filter(function ($asg) use ($wantSelf) {
            $isSelf = method_exists($asg, 'isSelfSitting') && $asg->isSelfSitting();

            return $wantSelf ? $isSelf : ! $isSelf;
        })->pluck('id')->map(fn ($id) => (int) $id)->filter()->values()->all();
    }

    /** All test records that share this title (ALL TESTS collapses them to one row). */
    protected function sameTitleAssessmentIds(): array
    {
        $id = (int) ($this->id ?? 0);
        $title = mb_strtolower(trim((string) ($this->title ?? '')));
        if ($id <= 0) {
            return [];
        }
        if ($title === '') {
            return [$id];
        }
        try {
            $ids = static::query()->get(['id', 'title'])
                ->filter(fn ($row) => mb_strtolower(trim((string) ($row->title ?? ''))) === $title)
                ->pluck('id')
                ->map(fn ($rowId) => (int) $rowId)
                ->filter()
                ->unique()
                ->values()
                ->all();
        } catch (\Throwable $e) {
            $ids = [$id];
        }

        return $ids === [] ? [$id] : $ids;
    }

    protected function assignedRosterUserIds(?string $sittingKind = null)
    {
        $cacheKey = $sittingKind ?: 'all';
        if (isset($this->assignedRosterByKindCache[$cacheKey]) && is_array($this->assignedRosterByKindCache[$cacheKey])) {
            return collect($this->assignedRosterByKindCache[$cacheKey]);
        }

        $cols = ['id', 'assessment_id', 'student_id', 'class_batch_id', 'created_at'];
        if (Schema::hasColumn('lms_assessment_assignments', 'roster_user_ids')) {
            $cols[] = 'roster_user_ids';
        }
        if (Schema::hasColumn('lms_assessment_assignments', 'excluded_user_ids')) {
            $cols[] = 'excluded_user_ids';
        }
        if (Schema::hasColumn('lms_assessment_assignments', 'sitting_kind')) {
            $cols[] = 'sitting_kind';
        }
        $aids = $this->sameTitleAssessmentIds();
        try {
            $assignments = LmsAssessmentAssignment::query()->whereIn('assessment_id', $aids)->get($cols);
        } catch (\Throwable $e) {
            $assignments = $this->relationLoaded('assignments')
                ? $this->assignments
                : $this->assignments()->get($cols);
        }
        if ($sittingKind === LmsAssessmentAssignment::KIND_SELF) {
            $assignments = $assignments->filter(fn ($a) => method_exists($a, 'isSelfSitting') && $a->isSelfSitting())->values();
        } elseif ($sittingKind === LmsAssessmentAssignment::KIND_EXAM) {
            $assignments = $assignments->filter(fn ($a) => ! (method_exists($a, 'isSelfSitting') && $a->isSelfSitting()))->values();
        }
        $hay = mb_strtolower(trim((string) ($this->title ?? '') . ' ' . (string) ($this->course->title ?? '')));
        $isCimaMcs = str_contains($hay, 'cima mcs') || str_contains($hay, 'management case') || (bool) preg_match('/\bmcs\b/', $hay);

        $ids = collect();
        $excluded = collect();
        $isCimaScs = $this->isCimaScsNovemberTitle();
        if ($isCimaScs && ! $this->isCimaScsAssignedMockTitle()) {
            $this->assignedRosterByKindCache[$cacheKey] = [];

            return collect();
        }
        foreach ($assignments as $assignment) {
            $local = collect();
            if (! empty($assignment->student_id)) {
                $local->push((int) $assignment->student_id);
            }
            if (Schema::hasColumn('lms_assessment_assignments', 'roster_user_ids')) {
                $local = $local->merge(collect($assignment->roster_user_ids ?? []));
            }
            $self = method_exists($assignment, 'isSelfSitting') && $assignment->isSelfSitting();
            $batchId = (int) ($assignment->class_batch_id ?? 0);
            if ($self) {
                // Self Test: named / roster people only. Do not copy the exam batch onto it.
            } elseif ($isCimaScs && $this->isCimaScsAssignedMockTitle()) {
                try {
                    $local = $local->merge(ClassBatch::studentUserIdsForSitting($assignment));
                } catch (\Throwable $e) {
                    // keep named/roster
                }
                if ($batchId <= 0) {
                    try {
                        $batch = ClassBatch::query()->where('code', 'BAT-0004')->first()
                            ?: ClassBatch::query()->where('code', 'like', '%0004%')->first();
                        $batchId = (int) ($batch->id ?? 0);
                    } catch (\Throwable $e) {
                        $batchId = 0;
                    }
                }
                if ($batchId > 0) {
                    try {
                        $local = $local->merge(ClassBatch::membersOnBatchAtAssignment($batchId, $assignment));
                    } catch (\Throwable $e) {
                        // named/roster only
                    }
                }
            } else {
                $local = $local->merge(ClassBatch::studentUserIdsForSitting($assignment));
            }
            $ids = $ids->merge($local);
            if (Schema::hasColumn('lms_assessment_assignments', 'excluded_user_ids')) {
                $excluded = $excluded->merge(collect($assignment->excluded_user_ids ?? []));
            }
        }
        $excluded = $excluded->map(fn ($id) => (int) $id)->unique()->filter();
        if (Schema::hasTable('lms_assessment_attempts')) {
            $attemptQuery = LmsAssessmentAttempt::query()->whereIn('assessment_id', $aids);
            if ($sittingKind) {
                $kindAsgIds = $this->assignmentIdsForSittingKind($sittingKind);
                if ($kindAsgIds === []) {
                    $attemptQuery = null;
                } else {
                    $attemptQuery->whereIn('assignment_id', $kindAsgIds);
                }
            }
            if ($attemptQuery) {
                $ids = $ids->merge($attemptQuery->pluck('user_id'));
            }
        }

        $ids = $ids->map(fn ($id) => (int) $id)->unique()->filter()->values();

        if ($isCimaMcs && $sittingKind !== LmsAssessmentAssignment::KIND_SELF) {
            try {
                $cohortIds = User::query()
                    ->where(function ($q) {
                        $emails = [
                            'k_vinu83@yahoo.com',
                            'kartik.chaturvedi469@gmail.com',
                            'jahanps@gmail.com',
                            'osamaym@hotmail.com',
                            'vananthan1@gmail.com',
                        ];
                        $q->whereIn('email', $emails);
                        foreach ($emails as $email) {
                            $q->orWhereRaw('LOWER(email) = ?', [$email]);
                        }
                    })
                    ->pluck('id');
                $ids = $ids->merge($cohortIds);
            } catch (\Throwable $e) {
                // keep batch roster
            }
        }

        $ids = $ids->map(fn ($id) => (int) $id)->unique()->filter()->diff($excluded)->values();
        if ($ids->isEmpty() || ! $isCimaMcs) {
            $this->assignedRosterByKindCache[$cacheKey] = $ids->all();

            return $ids;
        }
        try {
            $people = User::query()->whereIn('id', $ids->all())->get(['id', 'name', 'email']);
        } catch (\Throwable $e) {
            $this->assignedRosterByKindCache[$cacheKey] = $ids->all();

            return $ids;
        }

        $hide = $people->filter(function ($person) {
            $name = mb_strtolower(trim((string) ($person->name ?? '')));
            $email = mb_strtolower(trim((string) ($person->email ?? '')));

            return str_contains($name, 'imthiyas') || str_contains($email, 'imthiyas');
        })->pluck('id')->map(fn ($id) => (int) $id);

        $ids = $ids->diff($hide)->diff($excluded)->map(fn ($id) => (int) $id)->unique()->values();
        $this->assignedRosterByKindCache[$cacheKey] = $ids->all();

        return $ids;
    }

    protected function batchIdsForUser(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }
        $found = collect();
        try {
            if (Schema::hasTable('class_batch_student')) {
                if (Schema::hasColumn('class_batch_student', 'student_id')) {
                    $found = $found->merge(DB::table('class_batch_student')->where('student_id', $userId)->pluck('class_batch_id'));
                }
                if (Schema::hasColumn('class_batch_student', 'user_id')) {
                    $found = $found->merge(DB::table('class_batch_student')->where('user_id', $userId)->pluck('class_batch_id'));
                }
            }
        } catch (\Throwable $e) {
            return [];
        }

        return $found->map(fn ($id) => (int) $id)->unique()->filter()->values()->all();
    }

    protected function studentIdsForBatch(int $batchId)
    {
        return ClassBatch::studentUserIdsFor($batchId);
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
