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

    /** Every user id on this batch: pivot, Eloquent roster, and schedule students. */
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
        try {
            if (Schema::hasTable('class_schedules') && Schema::hasTable('class_schedule_student')) {
                $scheduleQuery = DB::table('class_schedules')->where('batch_id', $batchId);
                if ($batch && Schema::hasColumn('class_schedules', 'batch_name')) {
                    $name = trim((string) $batch->name);
                    $code = trim((string) $batch->code);
                    if ($code !== '') {
                        $scheduleQuery->orWhere('batch_name', $code);
                    }
                    if ($name !== '') {
                        $scheduleQuery->orWhere('batch_name', $name);
                        $scheduleQuery->orWhere('batch_name', 'like', '%' . $name . '%');
                    }
                    if (Schema::hasColumn('class_schedules', 'course_id') && ! empty($batch->course_id)) {
                        $scheduleQuery->orWhere(function ($q) use ($batch, $name, $code) {
                            $q->where('course_id', (int) $batch->course_id);
                            $q->where(function ($n) use ($name, $code) {
                                if ($code !== '') {
                                    $n->orWhere('batch_name', 'like', '%' . $code . '%');
                                }
                                if ($name !== '') {
                                    $n->orWhere('batch_name', 'like', '%' . $name . '%');
                                }
                            });
                        });
                    }
                }
                $scheduleIds = $scheduleQuery->pluck('id');
                if ($scheduleIds->isNotEmpty()) {
                    $srows = DB::table('class_schedule_student')->whereIn('class_schedule_id', $scheduleIds)->get();
                    foreach ($srows as $row) {
                        $arr = (array) $row;
                        foreach (['student_id', 'user_id'] as $col) {
                            if (! empty($arr[$col])) {
                                $ids->push((int) $arr[$col]);
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // keep pivot ids
        }

        return $ids->map(fn ($id) => (int) $id)->unique()->filter()->values();
    }

    /**
     * Batch on this course that contains the most of these people.
     * Skips Masterclass batches unless the test title is a masterclass.
     */
    public static function bestIdForStudents(int $courseId, array $userIds, ?string $assessmentTitle = null): ?int
    {
        $userIds = collect($userIds)->map(fn ($id) => (int) $id)->unique()->filter()->values();
        if ($userIds->isEmpty()) {
            return null;
        }
        $q = static::query();
        if ($courseId > 0 && Schema::hasColumn((new static)->getTable(), 'course_id')) {
            $q->where('course_id', $courseId);
        }
        $title = mb_strtolower(trim((string) $assessmentTitle));
        $wantMaster = str_contains($title, 'masterclass');
        $bestId = null;
        $bestN = 0;
        foreach ($q->get(['id', 'name', 'code', 'course_id']) as $batch) {
            $hay = mb_strtolower(trim((string) ($batch->code ?? '') . ' ' . (string) ($batch->name ?? '')));
            if (str_contains($hay, 'masterclass') && ! $wantMaster) {
                continue;
            }
            $n = static::studentUserIdsFor((int) $batch->id)->intersect($userIds)->count();
            if ($n > $bestN) {
                $bestN = $n;
                $bestId = (int) $batch->id;
            }
        }

        return $bestN > 0 ? $bestId : null;
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
