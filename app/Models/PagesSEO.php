<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TracksAudit;

class PagesSEO extends Model
{
    use HasFactory, TracksAudit;

    protected $table = 'pages_s_e_o_s';
    protected $fillable = ['page_id','course_id','title','meta_description', 'focus_keyword', 'keywords', 'additional_keywords', 'thumbnail', 'thumbnail_alt'];

    public function page()
    {
        return $this->belongsTo(Page::class, 'page_id', 'id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'id');
    }
}