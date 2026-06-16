<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseStructure extends Model
{
    use HasFactory;
    protected $fillable = ['courses_id','title','heading','exam_format','exam_duration','overview'];
    
    public function subHeadings()
    {
        return $this->hasMany(CourseStructureSubHeading::class, 'course_structures_id', 'id');
    }
}
