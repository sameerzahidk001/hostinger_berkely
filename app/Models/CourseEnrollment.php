<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class CourseEnrollment extends Model
{
    use HasFactory,SoftDeletes;
    protected $fillable = ['course_id','starting_date','application_deadline','brochure','discount','status'];
    
    public function CourseEnrollment()
    {
        return $this->belongsTo(Courses::class);
    }

    protected $casts = [
        'starting_date' => 'datetime',
        'application_deadline' => 'datetime'
    ];
}
