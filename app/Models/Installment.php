<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Installment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'duration_months', 
        'remaining_amount', 
        'paid_amount',
        'paid_date',
        'installment_number',
        'due_date',
        'payment_id',
        'user_id',
        'status',
        'payment_method',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function course()
    {
        return $this->hasOneThrough(
            Course::class,
            Payment::class,
            'id', // Foreign key on the payments table (payment_id in installments)
            'id', // Foreign key on the courses table (course_id in payments)
            'payment_id', // Local key in installments
            'course_id' // Local key in payments
        );
    }

    public function courseFee()
    {
        return $this->hasOneThrough(
            CourseFee::class,  // Target table
            Payment::class,    // Intermediate table
            'id',              // Foreign key on payments table (payment_id in installments)
            'id',              // Foreign key on course_fees table (package_id in payments)
            'payment_id',      // Local key in installments table
            'package_id'       // Local key in payments table
        );
    }
}