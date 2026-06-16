<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'test_keys',
        'production_keys',
        'active',
        'status',
    ];

    protected $casts = [
        'test_keys' => 'array',
        'production_keys' => 'array',
        'active' => 'boolean',
    ];
}
