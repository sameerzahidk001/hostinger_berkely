<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'page_id',
        'page_fee_service_id',
        'page_fee_category_id',
        'page_fee_package_id',
        'price',
        'currency',
        'payment_method',
        'status',
        'source',
        'package_id',
        'terms_conditions',
        'total_installment',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function page()
    {
        return $this->belongsTo(Page::class, 'page_id');
    }

    public function pageFeeService()
    {
        return $this->belongsTo(PageFeeService::class, 'page_fee_service_id');
    }

    public function pageFeeCategory()
    {
        return $this->belongsTo(PageFeeCategory::class, 'page_fee_category_id');
    }

    public function pageFeePackage()
    {
        return $this->belongsTo(PageFeePackage::class, 'page_fee_package_id');
    }

    public function courseFee()
    {
        return $this->belongsTo(CourseFee::class, 'package_id', 'id');
    }

    public function isPageFee(): bool
    {
        return ! empty($this->page_fee_package_id);
    }

    public function feePackage()
    {
        return $this->isPageFee() ? $this->pageFeePackage : $this->courseFee;
    }

    public function programTitle(): string
    {
        if ($this->isPageFee()) {
            $page = $this->page?->page_name;
            $service = $this->pageFeeService?->name;
            if ($page && $service) {
                return $page . ' — ' . $service;
            }

            return $page ?: ($service ?: 'Page Fee');
        }

        return $this->course?->title ?? ('Course #' . ($this->course_id ?: ''));
    }

    public function installment_request()
    {
        return $this->hasOne(InstallmentRequest::class, 'payment_id');
    }

    public function installments()
    {
        return $this->hasMany(Installment::class, 'payment_id');
    }

    protected static function booted()
    {
        static::saving(function ($payment) {
            if (! Schema::hasColumn($payment->getTable(), 'source')) {
                unset($payment->source);
            }
            foreach (['page_id', 'page_fee_service_id', 'page_fee_category_id', 'page_fee_package_id'] as $col) {
                if (! Schema::hasColumn($payment->getTable(), $col)) {
                    unset($payment->{$col});
                }
            }
        });

        static::creating(function ($payment) {
            // If terms_conditions is missing or empty
            if (empty($payment->terms_conditions)) {
                // Get the latest previous terms_conditions
                $payment->terms_conditions = Payment::latest('id')->value('terms_conditions') ?? 'Default Terms and Conditions';
            }
        });
    }
}
