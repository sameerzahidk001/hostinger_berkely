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
}
