<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PageFeeService extends Model
{
    protected $table = 'page_fee_services';

    protected $fillable = [
        'page_id',
        'name',
        'sort_order',
        'is_visible',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(PageFeeCategory::class, 'service_id')->orderBy('sort_order');
    }
}
