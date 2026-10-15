<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LmsAssessmentAssignment extends Model
{
    protected $table = 'lms_assessment_assignments';

    protected $fillable = [
        'assessment_id', 'class_batch_id', 'student_id',
        'available_from', 'available_until',
        'assigned_by_admin_id', 'assigned_by_user_id',
    ];

    protected $casts = [
        'available_from' => 'datetime',
        'available_until' => 'datetime',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(LmsAssessment::class, 'assessment_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ClassBatch::class, 'class_batch_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function isAvailableNow(): bool
    {
        $now = now();
        if ($this->available_from && $now->lt($this->available_from)) {
            return false;
        }
        if ($this->available_until && $now->gt($this->available_until)) {
            return false;
        }

        return true;
    }

    public function scopeLabel(): string
    {
        if ($this->batch) {
            return 'Batch: ' . $this->batch->displayLabel();
        }
        if ($this->student) {
            return 'Student: ' . ($this->student->name ?? '#' . $this->student_id);
        }

        return '—';
    }
}
