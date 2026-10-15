<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageFeePackage extends Model
{
    protected $table = 'page_fee_packages';

    protected $fillable = [
        'category_id',
        'package_name',
        'currency',
        'price',
        'tax_percentage',
        'discount_amount',
        'short_description',
        'key_point',
        'package_includes',
        'terms_conditions',
        'priority',
        'installments',
        'showonwebsite',
        'status',
    ];

    protected $casts = [
        'package_includes' => 'array',
        'showonwebsite' => 'boolean',
        'status' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(PageFeeCategory::class, 'category_id');
    }

    /** Alias so invoice/cart views can reuse course fee field names. */
    public function getPackageFeatureAttribute()
    {
        return $this->package_includes;
    }
}
