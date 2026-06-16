<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageSection extends Model
{
    // Specify which fields can be mass-assigned
    protected $fillable = ['page_id', 'section_type', 'order', 'data'];

    // Define the relationship with the Page model
    public function page()
    {
        return $this->belongsTo(Page::class);
    }
}
