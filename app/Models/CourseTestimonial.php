<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseTestimonial extends Model
{
    use HasFactory,SoftDeletes;
    protected $fillable = ['course_id','name','date','text', 'image','city','country','priority','asset_path','asset_type','linkedin_url',
    'rating','user_id', 'status'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}