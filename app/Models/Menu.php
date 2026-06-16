<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'link',
        'parent_id',
        'menu_order',
        'is_active',
        'menu_group'
    ];

    // Get child menus (for multi-level menu)
    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id');
    }

    // Get parent menu
    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }
}
