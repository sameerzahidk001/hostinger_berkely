<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseStructureSubHeading extends Model
{
    use HasFactory;
    protected $fillable = ['course_structure_id','sub_heading'];

    public function subHeadingsUnits()
    {
        return $this->hasMany(CourseStructureSubHeadingUnit::class, 'sub_headings_id', 'id');
    }
}
