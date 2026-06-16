<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseStructureSubHeadingUnit extends Model
{
    use HasFactory;
    protected $fillable = ['sub_headings_id','unit_title', 'unit_video', 'thumbnail'];
}
