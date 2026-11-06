<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
        'stripe_session_id',
        'stripe_payment_intent',
        'charge_currency',
        'charge_amount',
        'fx_rate_to_aed',
    ];

    public static function ensureStripeColumns(): void
    {
        if (! Schema::hasTable('installments')) {
            return;
        }

        $adds = [
            'stripe_session_id' => fn (Blueprint $t) => $t->string('stripe_session_id', 191)->nullable()->index(),
            'stripe_payment_intent' => fn (Blueprint $t) => $t->string('stripe_payment_intent', 191)->nullable()->index(),
            'charge_currency' => fn (Blueprint $t) => $t->string('charge_currency', 10)->nullable(),
            'charge_amount' => fn (Blueprint $t) => $t->decimal('charge_amount', 12, 2)->nullable(),
            'fx_rate_to_aed' => fn (Blueprint $t) => $t->decimal('fx_rate_to_aed', 12, 6)->nullable(),
        ];
        foreach ($adds as $col => $cb) {
            if (! Schema::hasColumn('installments', $col)) {
                Schema::table('installments', $cb);
            }
        }
    }

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