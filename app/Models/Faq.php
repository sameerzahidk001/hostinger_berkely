<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TracksAudit;

class Faq extends Model
{
    use HasFactory, TracksAudit;
    protected $fillable = ['page_id','question', 'answer'];

    public function pages()
    {
        return $this->belongsTo(Page::class, 'page_id', 'id');
    }
}
