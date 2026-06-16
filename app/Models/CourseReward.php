<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class CourseReward extends Model
{
    use HasFactory,SoftDeletes;
    protected $fillable = ['course_id','image','title','description', 'conditon','type', 'status'];
    
    public function earning()
    {
        return $this->belongsTo(Courses::class);
    }
}
