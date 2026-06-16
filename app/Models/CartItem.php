<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CartItem extends Model
{
    protected $fillable = ['user_id', 'course_fee_package_id', 'quantity'];

    /**
     * Custom JOIN method to get cart + package info
     */

    public function courseFee()
    {
        return $this->belongsTo(CourseFee::class, 'course_fee_package_id');
    }

    public function scopeWithValidPackage($query)
    {
        return $query->whereHas('courseFee');
    }

    public static function forUser(int $userId)
    {
        static::where('user_id', $userId)->whereDoesntHave('courseFee')->delete();

        return static::with('courseFee')
            ->where('user_id', $userId)
            ->whereHas('courseFee')
            ->get();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}