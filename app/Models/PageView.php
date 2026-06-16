<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class PageView extends Model
{
    use HasFactory,SoftDeletes;
    
    protected $fillable = [
        'url', 'referrer', 'session_id', 'ip_address', 'country', 'region', 'postal', 'location', 'city', 'user_agent', 'browser', 'platform', 'view_count'
    ];

    // A page view has many user behaviors
    public function userBehaviors()
    {
        return $this->hasMany(UserBehavior::class);
    }
}
