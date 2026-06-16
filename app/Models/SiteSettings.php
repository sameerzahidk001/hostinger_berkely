<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteSettings extends Model
{
    use HasFactory;

    protected $table = 'site_settings';

    protected $fillable = [
        'logo',
        'button_text',
        'button_url',
        'search',
        'login',
        'register',
        'created_at',
        'updated_at'
    ];
}