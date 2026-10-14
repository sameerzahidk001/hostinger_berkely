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
        // Soft-deleted courses still belong on paid invoices — keep the real title on dashboards.
        return $this->belongsTo(Course::class, 'course_id')->withTrashed();
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
            $page = trim((string) ($this->page?->page_name ?? ''));
            $service = trim((string) ($this->pageFeeService?->name ?? ''));
            $package = trim((string) ($this->pageFeePackage?->package_name ?? ''));

            $parts = array_values(array_filter([$page, $service, $package], fn ($p) => $p !== ''));
            if ($parts !== []) {
                return implode(' — ', $parts);
            }

            return 'Page fee program';
        }

        $title = trim((string) ($this->course?->title ?? ''));
        if ($title !== '') {
            return $title;
        }

        if (! empty($this->course_id)) {
            return 'Untitled course (#' . $this->course_id . ')';
        }

        return 'Unknown program';
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
            foreach ([
                'page_id', 'page_fee_service_id', 'page_fee_category_id', 'page_fee_package_id',
                'zoho_crm_contact_id', 'zoho_crm_deal_id', 'zoho_crm_invoice_id',
                'zoho_crm_sync_status', 'zoho_crm_last_error', 'zoho_crm_synced_at',
            ] as $col) {
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
