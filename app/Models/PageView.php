<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PageView extends Model
{
    use HasFactory,SoftDeletes;
    
    protected $fillable = [
        'url', 'referrer', 'session_id', 'ip_address', 'country', 'region', 'postal', 'location', 'city',
        'user_agent', 'browser', 'platform', 'view_count', 'user_id', 'duration_seconds',
    ];

    public static function ensureAnalyticsColumns(): void
    {
        if (! Schema::hasTable('page_views')) {
            return;
        }
        if (! Schema::hasColumn('page_views', 'user_id')) {
            Schema::table('page_views', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->index();
            });
        }
        if (! Schema::hasColumn('page_views', 'duration_seconds')) {
            Schema::table('page_views', function (Blueprint $table) {
                $table->unsignedInteger('duration_seconds')->default(0);
            });
        }
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // A page view has many user behaviors
    public function userBehaviors()
    {
        return $this->hasMany(UserBehavior::class);
    }
}
