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

    public static function ensureStripe(): self
    {
        return static::query()->firstOrCreate(
            ['slug' => 'stripe'],
            [
                'name' => 'Stripe (USD)',
                'status' => 'test',
                'active' => false,
                'test_keys' => ['client_id' => '', 'secret_key' => '', 'webhook_secret' => ''],
                'production_keys' => ['client_id' => '', 'secret_key' => '', 'webhook_secret' => ''],
            ]
        );
    }

    public function isStripe(): bool
    {
        return strtolower((string) $this->slug) === 'stripe';
    }
}
