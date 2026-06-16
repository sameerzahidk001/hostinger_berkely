<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstallmentRequest extends Model
{
    protected $fillable = ['user_id', 'package_id', 'payment_id', 'installments_requested', 'request_type', 'status'];

    // Relationship method should be inside the class body
    public function user()
{
    return $this->belongsTo(User::class, 'user_id', 'id');
}

public function package()
{
    return $this->belongsTo(CourseFee::class, 'package_id', 'id');
}

}
