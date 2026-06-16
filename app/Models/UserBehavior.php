<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserBehavior extends Model
{
    use HasFactory;
    protected $table = 'user_behavior';

    protected $fillable = [
        'page_view_id', 'event_type', 'data'
    ];

    // A behavior belongs to a page view
    public function pageView()
    {
        return $this->belongsTo(PageView::class);
    }
}
