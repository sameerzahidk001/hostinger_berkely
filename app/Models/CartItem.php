<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class CartItem extends Model
{
    protected $fillable = ['user_id', 'course_fee_package_id', 'page_fee_package_id', 'quantity'];

    public function courseFee()
    {
        return $this->belongsTo(CourseFee::class, 'course_fee_package_id');
    }

    public function pageFeePackage()
    {
        return $this->belongsTo(PageFeePackage::class, 'page_fee_package_id');
    }

    public function isPageFee(): bool
    {
        return ! empty($this->page_fee_package_id);
    }

    public function feePackage()
    {
        return $this->isPageFee() ? $this->pageFeePackage : $this->courseFee;
    }

    public function scopeWithValidPackage($query)
    {
        $hasPageCol = Schema::hasColumn((new static)->getTable(), 'page_fee_package_id');

        return $query->where(function ($q) use ($hasPageCol) {
            $q->whereHas('courseFee');
            if ($hasPageCol) {
                $q->orWhereHas('pageFeePackage');
            }
        });
    }

    public static function forUser(int $userId)
    {
        $hasPageCol = Schema::hasColumn((new static)->getTable(), 'page_fee_package_id');

        static::where('user_id', $userId)
            ->where(function ($q) use ($hasPageCol) {
                $q->where(function ($inner) {
                    $inner->whereNotNull('course_fee_package_id')->whereDoesntHave('courseFee');
                });
                if ($hasPageCol) {
                    $q->orWhere(function ($inner) {
                        $inner->whereNotNull('page_fee_package_id')->whereDoesntHave('pageFeePackage');
                    });
                }
            })
            ->delete();

        $with = ['courseFee.course'];
        if ($hasPageCol) {
            $with[] = 'pageFeePackage.category.service.page';
        }

        return static::with($with)
            ->where('user_id', $userId)
            ->where(function ($q) use ($hasPageCol) {
                $q->whereHas('courseFee');
                if ($hasPageCol) {
                    $q->orWhereHas('pageFeePackage');
                }
            })
            ->get();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
