<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseStructureFirst extends Model
{
    use HasFactory;
    protected $fillable = ['courses_id','title','heading','exam_format','exam_duration','overview'];
    
    public function subHeadingsFirst()
    {
        return $this->hasMany(CourseStructureSubHeadingFirst::class, 'course_structures_id', 'id');
    }
}
