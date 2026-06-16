<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseStructureSubHeadingFirst extends Model
{
    use HasFactory;
    protected $fillable = ['course_structure_id','sub_heading'];
}
