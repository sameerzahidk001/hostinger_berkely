<?php

namespace App\Models;

use App\Traits\TracksAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseFee extends Model
{
    use HasFactory, TracksAudit;

    protected $fillable = [
        'courses_id', 'package_name', 'currency', 'price',
        'tax_percentage', 'discount_amount', 'short_description', 'key_point', 
        'package_includes', 'priority', 'package_feature','installments','showonwebsite','status'
    ];

    protected $casts = [
        'package_feature' => 'array'
    ];

    // Define relationship with Course
    public function course()
    {
        return $this->belongsTo(Course::class, 'courses_id', 'id');
    }
    public function payments()
    {
        return $this->hasMany(Payment::class, 'package_id');
    }
    public function installmentRequest()
{
    return $this->hasMany(InstallmentRequest::class, 'package_id', 'id');
}

}
