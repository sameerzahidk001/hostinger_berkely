<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class StudyMaterialInstructorAccess extends Model
{
    protected $table = 'study_material_instructor_access';

    protected $fillable = [
        'folder_id',
        'instructor_id',
        'status',
        'issued_at',
        'access_till',
        'sent_at',
        'is_trainer',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'access_till' => 'date',
        'sent_at' => 'datetime',
        'is_trainer' => 'boolean',
    ];

    /**
     * Marks whether this faculty member is also selected as Instructor
     * (can be true for the same person who is Head of Faculty).
     */
    public static function ensureIsTrainerColumn(): bool
    {
        if (! Schema::hasTable('study_material_instructor_access')) {
            return false;
        }
        if (Schema::hasColumn('study_material_instructor_access', 'is_trainer')) {
            return true;
        }

        Schema::table('study_material_instructor_access', function (Blueprint $table) {
            $table->boolean('is_trainer')->default(true)->after('instructor_id');
        });

        return Schema::hasColumn('study_material_instructor_access', 'is_trainer');
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(StudyMaterialFolder::class, 'folder_id');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }
}
