<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearnerStory extends Model
{
    use HasFactory;
    protected $fillable = ['subjects_id','name', 'details', 'image'];

    public function category()
    {
        return $this->belongsTo(Category::class, 'subjects_id', 'id');
    }
}
