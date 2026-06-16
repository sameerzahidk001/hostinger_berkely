<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class CourseBeneficiaries extends Model
{
    use HasFactory,SoftDeletes;
    protected $fillable = ['course_id','title','description'];
   
    public function courseBeneficiaryPoint()
    {
        return $this->belongsTo(Courses::class);
    }
}
