<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeePackagesFeatures extends Model
{
    use HasFactory;
    
    protected $fillable = ['fee_packages_id','heading','course_access','payment_option','pass_guarantee','exam_day_ready_features','unmatched_resources_and_tools'];

    protected $casts = [
        //'course_access' => 'array',
        //'payment_option' => 'array',
        'pass_guarantee' => 'array',
        'exam_day_ready_features' => 'array',
        'unmatched_resources_and_tools' => 'array'
    ];
}
