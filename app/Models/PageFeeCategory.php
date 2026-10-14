<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PageFeeCategory extends Model
{
    protected $table = 'page_fee_categories';

    protected $fillable = [
        'service_id',
        'name',
        'sort_order',
        'is_visible',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    public function service()
    {
        return $this->belongsTo(PageFeeService::class, 'service_id');
    }

    public function packages(): HasMany
    {
        return $this->hasMany(PageFeePackage::class, 'category_id')->orderBy('priority');
    }
}
