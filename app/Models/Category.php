<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TracksAudit;

class Category extends Model
{
    use SoftDeletes, TracksAudit;
    protected $fillable = ['name', 'slug', 'description', 'footer'];

    public function schools()
    {
        return $this->belongsToMany(School::class, 'school_category');
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'category_course');
    }

    public function scopeWithCoursesBySchool($query, $schoolId)
    {
        return $query
            ->select('categories.*')
            ->join('category_course', 'categories.id', '=', 'category_course.category_id')
            ->join('school_courses', 'category_course.course_id', '=', 'school_courses.course_id')
            ->where('school_courses.school_id', $schoolId)
            ->distinct('categories.id')
            ->with([
                'courses' => function ($q) use ($schoolId) {
                    $q->select('courses.*')
                        ->join('school_courses', 'courses.id', '=', 'school_courses.course_id')
                        ->where('school_courses.school_id', $schoolId);
                }
            ]);
    }
}