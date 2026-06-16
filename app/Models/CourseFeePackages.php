<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseFeePackages extends Model
{
    use HasFactory;
    protected $fillable = ['courses_id','package_name','price', 'discounted_price','discount_percentage', 'is_recommended'];

    public function packageFeatures()
    {
        return $this->hasOne(FeePackagesFeatures::class, 'fee_packages_id', 'id');
    }

}
