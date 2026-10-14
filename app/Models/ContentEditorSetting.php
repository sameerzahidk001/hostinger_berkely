<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentEditorSetting extends Model
{
    protected $table = 'content_editor_settings';

    protected $fillable = [
        'setting_key',
        'setting_value',
    ];
}
