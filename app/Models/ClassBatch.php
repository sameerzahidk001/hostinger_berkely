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
