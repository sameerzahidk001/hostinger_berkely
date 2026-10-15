<?php

namespace App\Models;

use App\Traits\TracksAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CourseFee extends Model
{
    use HasFactory, TracksAudit;

    protected $fillable = [
        'courses_id', 'package_name', 'currency', 'price',
        'tax_percentage', 'discount_amount', 'short_description', 'key_point',
        'package_includes', 'terms_conditions', 'priority', 'package_feature', 'installments', 'showonwebsite', 'status',
    ];

    protected $casts = [
        'package_feature' => 'array'
    ];

    public static function ensureTermsColumn(): void
    {
        try {
            if (Schema::hasTable('course_fees') && ! Schema::hasColumn('course_fees', 'terms_conditions')) {
                Schema::table('course_fees', function (Blueprint $table) {
                    $table->longText('terms_conditions')->nullable();
                });
            }
        } catch (\Throwable $e) {
            Log::warning('course_fees terms_conditions ensure failed: ' . $e->getMessage());
        }
    }

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
