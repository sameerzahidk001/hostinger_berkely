<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyMaterialStudentAccess extends Model
{
    protected $table = 'study_material_student_access';

    protected $fillable = [
        'folder_id',
        'student_id',
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

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
