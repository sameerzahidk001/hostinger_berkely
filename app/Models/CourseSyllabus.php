<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class CourseSyllabus extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'course_syllabus';
    protected $fillable = ['course_id','subtitle','title','description', 'duration','duration_unit','higlights','status'];
    
    public function courseSyllabus()
    {
        return $this->belongsTo(Courses::class);
    }

    protected $casts = [
        'higlights' => 'array',
    ];

    
    public function courseSyllabusHighlights()
    {
        return $this->hasMany(CourseSyllabusHighlights::class, 'course_syllabus_id', 'id');
    }
}
