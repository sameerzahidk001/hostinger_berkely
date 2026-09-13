<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    protected $casts = [
        'issued_at' => 'date',
        'access_till' => 'date',
        'sent_at' => 'datetime',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(StudyMaterialFolder::class, 'folder_id');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }
}
