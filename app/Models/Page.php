<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TracksAudit;
use Illuminate\Support\Str;

class Page extends Model
{
    use HasFactory, TracksAudit;
    protected $fillable = ['page_name', 'url', 'parent_id', 'category_id', 'status', 'fee_heading'];

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
        return $this->hasMany(PageSection::class)->orderBy('order');
    }

    public function feeServices()
    {
        return $this->hasMany(PageFeeService::class)->orderBy('sort_order');
    }

    public function parent()
    {
        return $this->belongsTo(Page::class, 'parent_id');
    }

    public function getFullUrlAttribute()
    {
        if ($this->parent_id) {
            $parent = $this->relationLoaded('parent') ? $this->parent : $this->parent()->first();
            if ($parent) {
                return $parent->full_url . '/' . $this->url;
            }
        }

        if ($this->category_id) {
            $perma = SiteSettings::value('category_perma') ?? 'category';

            return $perma . '/' . $this->url;
        }

        return $this->url;
    }

    public static function slugFromName(string $name): string
    {
        return Str::slug($name);
    }

    public static function findVerifyCertificatePage(): ?self
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('pages')) {
            return null;
        }

        return static::query()
            ->where(function ($q) {
                $q->whereIn('url', ['ecert-verify', 'verify-certificate'])
                    ->orWhere('page_name', 'Verify Certificate');
            })
            ->orderByRaw("CASE WHEN url IN ('ecert-verify','verify-certificate') THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->first();
    }

    public static function ensureVerifyCertificatePage(): ?self
    {
        return static::findVerifyCertificatePage();
    }

}