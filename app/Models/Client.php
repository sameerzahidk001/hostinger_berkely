<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TracksAudit;

class Client extends Model
{
    use HasFactory, TracksAudit;

    // Define fillable fields
    protected $fillable = ['title', 'url', 'open_new_tab', 'nofollow', 'description', 'image', 'active']; 
}
