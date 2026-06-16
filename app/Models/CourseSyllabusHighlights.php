<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class CourseSyllabusHighlights extends Model
{
    use HasFactory,SoftDeletes;
    protected $fillable = ['course_syllabus_id','title','short_description','description','image','video','status'];
    
    public function courseSyllabusHighlights()
    {
        return $this->belongsTo(CourseSyllabus::class);
    }
}
