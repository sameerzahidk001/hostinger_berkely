<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'price',
        'currency',
        'payment_method',
        'status',
        'source',
        'package_id',
        'terms_conditions',
        'total_installment',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function courseFee()
    {
        return $this->belongsTo(CourseFee::class, 'package_id', 'id');
    }

    public function installment_request()
    {
        return $this->hasOne(InstallmentRequest::class, 'payment_id');
    }

    public function installments()
    {
        return $this->hasMany(Installment::class, 'payment_id');
    }

    protected static function booted()
    {
        static::creating(function ($payment) {
            // If terms_conditions is missing or empty
            if (empty($payment->terms_conditions)) {
                // Get the latest previous terms_conditions
                $payment->terms_conditions = Payment::latest('id')->value('terms_conditions') ?? 'Default Terms and Conditions';
            }
        });
    }
}
