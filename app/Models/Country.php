<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Country extends Model
{
    protected $fillable = ['name', 'iso_code', 'currency_id'];

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
}
