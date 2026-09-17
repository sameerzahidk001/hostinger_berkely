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
        'created_by_admin_id',
        'created_by_user_id',
    ];

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

    public function primaryInstructorId(): ?int
    {
        $id = $this->instructors()->orderBy('users.name')->value('users.id');

        return $id ? (int) $id : null;
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
