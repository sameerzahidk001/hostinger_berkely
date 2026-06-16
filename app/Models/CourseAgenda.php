<?php

namespace App\Models;

use App\Traits\TracksAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseAgenda extends Model
{
    use HasFactory, TracksAudit;

    protected $fillable = [
        'course_id', 'subject', 'delivery_type', 'country_id', 'city',
        'from', 'to', 'inquiry', 'description', 'created_by', 'updated_by',
    ];

    // Add this relationship
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
}