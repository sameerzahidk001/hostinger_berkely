<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClassBatch extends Model
{
    protected $fillable = [
        'code',
        'name',
        'course_id',
        'head_of_faculty_id',
        'status',
        'rate_and_review_enabled',
        'rate_review_text_required',
        'created_by_admin_id',
        'created_by_user_id',
    ];

    protected $casts = [
        'rate_and_review_enabled' => 'boolean',
        'rate_review_text_required' => 'boolean',
    ];

    public static function ensureRateAndReviewColumns(): bool
    {
        if (! Schema::hasTable('class_batches')) {
            return false;
        }

        if (! Schema::hasColumn('class_batches', 'rate_and_review_enabled')) {
            Schema::table('class_batches', function ($table) {
                $table->boolean('rate_and_review_enabled')->default(true);
            });
        }
        if (! Schema::hasColumn('class_batches', 'rate_review_text_required')) {
            Schema::table('class_batches', function ($table) {
                $table->boolean('rate_review_text_required')->default(false);
            });
        }

        return Schema::hasColumn('class_batches', 'rate_and_review_enabled');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function headOfFaculty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_of_faculty_id');
    }

    public function instructors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_batch_instructor', 'class_batch_id', 'instructor_id')
            ->withTimestamps();
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_batch_student', 'class_batch_id', 'student_id')
            ->withTimestamps();
    }

    /** Student ids on this batch roster (class_batch_student only — not class schedule). */
    public static function studentUserIdsFor(int $batchId)
    {
        $ids = collect();
        if ($batchId <= 0) {
            return $ids;
        }
        $batch = static::query()->find($batchId);
        try {
            if (Schema::hasTable('class_batch_student')) {
                $rows = DB::table('class_batch_student')->where('class_batch_id', $batchId)->get();
                foreach ($rows as $row) {
                    $arr = (array) $row;
                    foreach (['student_id', 'user_id', 'learner_id'] as $col) {
                        if (! empty($arr[$col])) {
                            $ids->push((int) $arr[$col]);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // keep other sources
        }
        try {
            if ($batch) {
                $ids = $ids->merge($batch->students()->pluck('users.id'));
            }
        } catch (\Throwable $e) {
            // pivot relation may disagree with live columns
        }

        return $ids->map(fn ($id) => (int) $id)->unique()->filter()->values();
    }

    /**
     * People on this sitting: saved assign snapshot, else this batch only
     * (members already on the batch when it was assigned — not class schedule).
     */
    public static function studentUserIdsForSitting($assignment)
    {
        $ids = collect();
        if (! $assignment) {
            return $ids;
        }
        $excluded = collect();
        try {
            if (Schema::hasColumn('lms_assessment_assignments', 'excluded_user_ids')) {
                $excluded = collect($assignment->excluded_user_ids ?? [])->map(fn ($id) => (int) $id)->filter();
            }
        } catch (\Throwable $e) {
            $excluded = collect();
        }
        $rosterSaved = false;
        try {
            if (Schema::hasColumn('lms_assessment_assignments', 'roster_user_ids')) {
                $rawRoster = $assignment->getAttributes()['roster_user_ids'] ?? null;
                $rosterSaved = $rawRoster !== null && $rawRoster !== '';
                $ids = collect($assignment->roster_user_ids ?? [])->map(fn ($id) => (int) $id)->filter()->unique()->values();
            }
        } catch (\Throwable $e) {
            $ids = collect();
        }
        $batchId = (int) ($assignment->class_batch_id ?? 0);
        $batchSitting = $batchId > 0 && empty($assignment->student_id);
        if ($rosterSaved && $ids->isNotEmpty()) {
            if ($batchSitting) {
                $ids = $ids->merge(static::membersOnBatchAtAssignment($batchId, $assignment));
            }

            return $ids->map(fn ($id) => (int) $id)->unique()->filter()
                ->reject(fn ($id) => $excluded->contains((int) $id))
                ->values();
        }
        if ($rosterSaved && ! $batchSitting) {
            return $ids->reject(fn ($id) => $excluded->contains((int) $id))->values();
        }
        if ($batchId > 0) {
            $ids = $ids->merge(static::membersOnBatchAtAssignment($batchId, $assignment))
                ->map(fn ($id) => (int) $id)->unique()->filter()
                ->reject(fn ($id) => $excluded->contains((int) $id))
                ->values();
            if ($ids->isNotEmpty()) {
                return $ids;
            }
        }
        if ($ids->isNotEmpty()) {
            return $ids->reject(fn ($id) => $excluded->contains((int) $id))->values();
        }
        $title = (string) (optional($assignment->assessment)->title ?? '');
        $sitterIds = collect();
        if (! empty($assignment->student_id)) {
            $sitterIds->push((int) $assignment->student_id);
        }
        if ($batchId <= 0) {
            try {
                if (Schema::hasTable('lms_assessment_attempts') && ! empty($assignment->id) && ! empty($assignment->assessment_id)) {
                    $sitterIds = $sitterIds->merge(
                        \App\Models\LmsAssessmentAttempt::query()
                            ->where('assessment_id', (int) $assignment->assessment_id)
                            ->where('assignment_id', (int) $assignment->id)
                            ->whereNotNull('user_id')
                            ->pluck('user_id')
                    );
                }
            } catch (\Throwable $e) {
                // keep student_id
            }
            $batchId = (int) (static::idFromSittersSkipMasterclass($sitterIds->all(), $title) ?? 0);
            $hay = mb_strtolower(trim($title));
            $isMcs = str_contains($hay, 'mcs') || str_contains($hay, 'management case');
            if ($isMcs && $sitterIds->isNotEmpty()) {
                try {
                    $b3 = static::query()->where('code', 'BAT-0003')->first();
                    $on0003 = $b3
                        ? static::studentUserIdsFor((int) $b3->id)->intersect($sitterIds->map(fn ($id) => (int) $id))->isNotEmpty()
                        : false;
                    if ($b3 && $on0003) {
                        $batchId = (int) $b3->id;
                    }
                } catch (\Throwable $e) {
                    // keep inferred batch
                }
            }
        }
        if ($batchId <= 0) {
            return $ids->reject(fn ($id) => $excluded->contains((int) $id))->values();
        }
        $atAssign = static::membersOnBatchAtAssignment($batchId, $assignment)
            ->reject(fn ($id) => $excluded->contains((int) $id))
            ->values();
        if ($atAssign->isNotEmpty()) {
            return $atAssign;
        }

        return static::studentUserIdsFor($batchId);
    }

    /** Batch members already on the roster when this sitting was assigned. Late joiners stay out. */
    public static function membersOnBatchAtAssignment(int $batchId, $assignment)
    {
        $live = static::studentUserIdsFor($batchId);
        $at = $assignment->created_at ?? null;
        if (! $at || ! Schema::hasTable('class_batch_student') || ! Schema::hasColumn('class_batch_student', 'created_at')) {
            return $live;
        }
        try {
            $atStr = $at instanceof \DateTimeInterface ? $at->format('Y-m-d H:i:s') : (string) $at;
            $rows = DB::table('class_batch_student')->where('class_batch_id', $batchId)->get();
            $kept = collect();
            $dated = 0;
            foreach ($rows as $row) {
                $arr = (array) $row;
                $uid = (int) ($arr['student_id'] ?? $arr['user_id'] ?? 0);
                if ($uid <= 0) {
                    continue;
                }
                $joined = $arr['created_at'] ?? null;
                if ($joined === null || $joined === '') {
                    $kept->push($uid);
                    continue;
                }
                $dated++;
                if ((string) $joined <= $atStr) {
                    $kept->push($uid);
                }
            }
            if ($dated > 0) {
                $kept = $kept->unique()->values();

                return $kept->isNotEmpty() ? $kept : $live;
            }
        } catch (\Throwable $e) {
            return $live;
        }

        return $live;
    }

    /** Batch with the most of these sitters. Never Masterclass unless the test is a masterclass. No course match. */
    public static function idFromSittersSkipMasterclass(array $userIds, ?string $assessmentTitle = null): ?int
    {
        if (! Schema::hasTable('class_batches')) {
            return null;
        }
        $userIds = collect($userIds)->map(fn ($id) => (int) $id)->unique()->filter()->values();
        if ($userIds->isEmpty()) {
            return null;
        }
        $title = mb_strtolower(trim((string) $assessmentTitle));
        $wantMaster = str_contains($title, 'masterclass');
        $bestId = null;
        $bestN = 0;
        $prefer0004 = false;
        foreach (static::query()->get(['id', 'name', 'code']) as $batch) {
            $hay = mb_strtolower(trim((string) ($batch->code ?? '') . ' ' . (string) ($batch->name ?? '')));
            if (str_contains($hay, 'masterclass') && ! $wantMaster) {
                continue;
            }
            $n = static::studentUserIdsFor((int) $batch->id)->intersect($userIds)->count();
            if ($n <= 0) {
                continue;
            }
            $is0004 = str_contains(mb_strtolower(trim((string) ($batch->code ?? ''))), '0004');
            if ($n > $bestN || ($n === $bestN && $is0004 && ! $prefer0004)) {
                $bestN = $n;
                $bestId = (int) $batch->id;
                $prefer0004 = $is0004;
            }
        }

        return $bestN > 0 ? $bestId : null;
    }

    /**
     * Batch that belongs with this test: student overlap first, then title.
     * Does not require the batch course_id to match the test course (SCS mocks sit on Fast-Track).
     * Skips Masterclass unless the test title is a masterclass.
     */
    public static function bestIdForStudents(int $courseId, array $userIds, ?string $assessmentTitle = null): ?int
    {
        if (! Schema::hasTable('class_batches')) {
            return null;
        }
        $userIds = collect($userIds)->map(fn ($id) => (int) $id)->unique()->filter()->values();
        $title = mb_strtolower(trim((string) $assessmentTitle));
        $wantMaster = str_contains($title, 'masterclass');
        $bestId = null;
        $bestScore = -1;
        $bestOverlap = 0;
        foreach (static::query()->get(['id', 'name', 'code', 'course_id']) as $batch) {
            $hay = mb_strtolower(trim((string) ($batch->code ?? '') . ' ' . (string) ($batch->name ?? '')));
            if (str_contains($hay, 'masterclass') && ! $wantMaster) {
                continue;
            }
            $n = $userIds->isEmpty() ? 0 : static::studentUserIdsFor((int) $batch->id)->intersect($userIds)->count();
            $score = $n * 100;
            if ($courseId > 0 && (int) ($batch->course_id ?? 0) === $courseId) {
                $score += 8;
            }
            foreach (['strategic case', 'cima scs', ' scs ', 'november 2026', 'nov 2026'] as $tok) {
                $look = $tok === ' scs ' ? 'scs' : $tok;
                $inTitle = $tok === ' scs '
                    ? (str_contains($title, 'scs') || str_contains($title, 'strategic case'))
                    : str_contains($title, $look);
                $inBatch = $tok === ' scs '
                    ? (str_contains($hay, 'scs') || str_contains($hay, 'strategic case'))
                    : (str_contains($hay, $look) || ($look === 'november 2026' && str_contains($hay, 'nov 2026')));
                if ($inTitle && $inBatch) {
                    $score += 15;
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestId = (int) $batch->id;
                $bestOverlap = $n;
            }
        }
        if ($bestOverlap > 0) {
            return $bestId;
        }

        return $bestScore >= 15 ? $bestId : null;
    }

    /** BAT-0004 for CIMA SCS November 2026 mocks (not Masterclass). */
    public static function idForCimaScsNovember(?string $assessmentTitle = null): ?int
    {
        $title = mb_strtolower(trim((string) $assessmentTitle));
        if ($title === '') {
            return null;
        }
        if (str_contains($title, 'masterclass')) {
            return null;
        }
        $isScs = str_contains($title, 'scs') || str_contains($title, 'strategic case');
        $isNov = str_contains($title, 'november 2026') || str_contains($title, 'nov 2026');
        if (! $isScs || ! $isNov) {
            return null;
        }
        try {
            $byCode = static::query()->whereRaw('LOWER(TRIM(code)) = ?', ['bat-0004'])->first()
                ?: static::query()->where('code', 'BAT-0004')->first();
            if ($byCode) {
                return (int) $byCode->id;
            }
            $byName = static::query()
                ->where(function ($q) {
                    $q->where('name', 'like', '%November 2026%')
                        ->orWhere('name', 'like', '%Nov 2026%');
                })
                ->where(function ($q) {
                    $q->where('name', 'like', '%SCS%')
                        ->orWhere('name', 'like', '%Strategic Case%');
                })
                ->where('name', 'not like', '%Masterclass%')
                ->orderBy('id')
                ->first();

            return $byName ? (int) $byName->id : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class, 'batch_id');
    }

    public function displayLabel(): string
    {
        return $this->code . ' — ' . $this->name;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function trainerInstructors()
    {
        $people = $this->relationLoaded('instructors')
            ? $this->instructors
            : $this->instructors()->get();
        $hofId = (int) $this->head_of_faculty_id;
        $hof = $this->relationLoaded('headOfFaculty')
            ? $this->headOfFaculty
            : $this->headOfFaculty()->first();

        $trainers = $people
            ->reject(fn ($user) => $hofId > 0 && (int) $user->id === $hofId)
            ->values();

        if ($trainers->isEmpty() && $hof) {
            return collect([$hof]);
        }

        return $trainers;
    }

    public function primaryInstructorId(): ?int
    {
        $trainer = $this->trainerInstructors()->first();

        return $trainer ? (int) $trainer->id : null;
    }

    public static function generateCode(): string
    {
        if (! Schema::hasTable('class_batches')) {
            return 'BAT-0001';
        }

        $last = static::query()
            ->where('code', 'like', 'BAT-%')
            ->orderByDesc('id')
            ->value('code');

        $num = 1;
        if ($last && preg_match('/BAT-(\d+)/i', $last, $m)) {
            $num = ((int) $m[1]) + 1;
        }

        return 'BAT-' . str_pad((string) $num, 4, '0', STR_PAD_LEFT);
    }

    public static function ensureCode(self $batch): void
    {
        if (filled($batch->code)) {
            return;
        }

        do {
            $code = static::generateCode();
        } while (static::query()->where('code', $code)->exists());

        $batch->code = $code;
        $batch->save();
    }

    /**
     * Convert legacy schedules (batch_name + course_id, no batch_id) into ClassBatch rows.
     *
     * @return array{batches_created:int, schedules_linked:int, instructors_synced:int, students_synced:int}
     */
    public static function backfillFromLegacySchedules(): array
    {
        $stats = [
            'batches_created' => 0,
            'schedules_linked' => 0,
            'instructors_synced' => 0,
            'students_synced' => 0,
        ];

        if (! Schema::hasTable('class_batches') || ! Schema::hasTable('class_schedules')) {
            return $stats;
        }

        if (! Schema::hasColumn('class_schedules', 'batch_id')) {
            return $stats;
        }

        $legacy = ClassSchedule::query()
            ->with('students')
            ->whereNull('batch_id')
            ->whereNotNull('course_id')
            ->where('batch_name', '!=', '')
            ->orderBy('scheduled_at')
            ->get();

        if ($legacy->isEmpty()) {
            return $stats;
        }

        $groups = $legacy->groupBy(function (ClassSchedule $row) {
            $name = mb_strtolower(trim((string) $row->batch_name));

            return $name . '|' . (int) $row->course_id;
        });

        foreach ($groups as $sessions) {
            /** @var \Illuminate\Support\Collection<int, ClassSchedule> $sessions */
            $first = $sessions->sortBy('scheduled_at')->first();
            $name = trim((string) $first->batch_name);
            if ($name === '') {
                continue;
            }

            $batch = static::query()
                ->where('course_id', $first->course_id)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->first();

            if (! $batch) {
                $batch = new static();
                $batch->name = $name;
                $batch->course_id = $first->course_id;
                $batch->head_of_faculty_id = $first->head_of_faculty_id;
                $batch->status = 'active';
                $batch->code = static::generateCode();
                while (static::query()->where('code', $batch->code)->exists()) {
                    $batch->code = static::generateCode();
                }
                $batch->save();
                $stats['batches_created']++;
            } elseif (! $batch->head_of_faculty_id && $first->head_of_faculty_id) {
                $batch->head_of_faculty_id = $first->head_of_faculty_id;
                $batch->save();
            }

            $instructorIds = $sessions->pluck('instructor_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
            if ($instructorIds !== []) {
                $batch->instructors()->syncWithoutDetaching($instructorIds);
                $stats['instructors_synced'] += count($instructorIds);
            }

            $studentIds = $sessions
                ->flatMap(fn (ClassSchedule $row) => $row->students->pluck('id'))
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
            if ($studentIds !== []) {
                $batch->students()->syncWithoutDetaching($studentIds);
                $stats['students_synced'] += count($studentIds);
            }

            $ids = $sessions->pluck('id')->all();
            $updated = ClassSchedule::query()
                ->whereIn('id', $ids)
                ->whereNull('batch_id')
                ->update(['batch_id' => $batch->id]);
            $stats['schedules_linked'] += (int) $updated;
        }

        // Assign default meeting account to schedules missing one.
        if (Schema::hasColumn('class_schedules', 'meeting_account_id') && Schema::hasTable('meeting_accounts')) {
            $defaultId = MeetingAccount::defaultId();
            if ($defaultId) {
                ClassSchedule::query()
                    ->whereNull('meeting_account_id')
                    ->update(['meeting_account_id' => $defaultId]);
            }
        }

        return $stats;
    }
}
