<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class School extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'slug', 'short_description', 'icon', 'image'];

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'school_category');
    }

    // Add this relation
    public function courses()
    {
        return $this->hasManyThrough(
            Course::class,
            Category::class,
            'school_id',
            'category_id',
            'id',
            'id'
        );
    }
}