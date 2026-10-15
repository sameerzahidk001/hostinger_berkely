<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InstructorStudentReview extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'student_id',
        'instructor_id',
        'class_batch_id',
        'study_material_folder_id',
        'scope_key',
        'stars',
        'review_text',
        'is_published',
        'is_locked',
        'hide_student_name',
    ];

    public static function scopeKey(?int $batchId, ?int $folderId): string
    {
        if ($batchId) {
            return 'batch:' . $batchId;
        }
        if ($folderId) {
            return 'folder:' . $folderId;
        }

        return 'none';
    }

    protected $casts = [
        'stars' => 'integer',
        'is_published' => 'boolean',
        'is_locked' => 'boolean',
        'hide_student_name' => 'boolean',
    ];

    public static function ensureTable(): bool
    {
        if (Schema::hasTable('instructor_student_reviews')) {
            return true;
        }

        Schema::create('instructor_student_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id')->index();
            $table->unsignedBigInteger('instructor_id')->index();
            $table->unsignedBigInteger('class_batch_id')->nullable()->index();
            $table->unsignedBigInteger('study_material_folder_id')->nullable()->index();
            $table->string('scope_key', 64);
            $table->unsignedTinyInteger('stars');
            $table->text('review_text')->nullable();
            $table->boolean('is_published')->default(false)->index();
            $table->boolean('is_locked')->default(false);
            $table->boolean('hide_student_name')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['student_id', 'instructor_id', 'scope_key'],
                'isr_student_instructor_scope_unique'
            );
        });

        return Schema::hasTable('instructor_student_reviews');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ClassBatch::class, 'class_batch_id');
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(StudyMaterialFolder::class, 'study_material_folder_id');
    }

    public function scopeForInstructor($query, int $instructorId)
    {
        return $query->where('instructor_id', $instructorId);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function studentDisplayName(): string
    {
        if ($this->hide_student_name) {
            return 'Student';
        }

        return $this->student->name ?? 'Student';
    }

    public function contextLabel(): string
    {
        if ($this->batch) {
            return $this->batch->displayLabel();
        }
        if ($this->folder) {
            return ($this->folder->code ? $this->folder->code . ' — ' : '') . ($this->folder->name ?? 'Folder');
        }

        return '—';
    }
}
