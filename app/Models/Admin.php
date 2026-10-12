<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
class Admin extends Authenticatable
{
    use HasFactory,SoftDeletes;

    protected $fillable = [
        'username',
        'email',
        'image',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Display helper: live DB has username, not name.
     */
    public function getNameAttribute($value = null): string
    {
        if (is_string($value) && trim($value) !== '') {
            return $value;
        }

        return (string) ($this->attributes['username'] ?? '');
    }
}
