<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TracksAudit;

class Page extends Model
{
    use HasFactory, TracksAudit;
    protected $fillable = ['page_name', 'url', 'parent_id', 'category_id'];

    public function faqs()
    {
        return $this->hasMany(Faq::class, 'page_id');
    }

    public function seo()
    {
        return $this->hasOne(PagesSEO::class, 'page_id');
    }
    
    public function sections()
    {
        return $this->hasMany(PageSection::class);
    }

    public function parent()
    {
        return $this->belongsTo(Page::class, 'parent_id');
    }

    public function getFullUrlAttribute()
    {
        if ($this->parent) {
            return $this->parent->full_url . '/' . $this->url;
        }
        return $this->url;
    }

}