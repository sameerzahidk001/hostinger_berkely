<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageFeePackage extends Model
{
    protected $table = 'page_fee_packages';

    protected $fillable = [
        'category_id',
        'package_name',
        'currency',
        'price',
        'tax_percentage',
        'discount_amount',
        'short_description',
        'key_point',
        'package_includes',
        'terms_conditions',
        'invoice_terms_template_id',
        'priority',
        'installments',
        'showonwebsite',
        'status',
    ];

    protected $casts = [
        'package_includes' => 'array',
        'showonwebsite' => 'boolean',
        'status' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(PageFeeCategory::class, 'category_id');
    }

    public function invoiceTermsTemplate()
    {
        return $this->belongsTo(InvoiceTermsTemplate::class, 'invoice_terms_template_id');
    }

    /** Resolve T&C HTML for invoices: template body first, then legacy package text. */
    public function resolvedTermsHtml(): string
    {
        $fromTemplate = trim((string) ($this->invoiceTermsTemplate?->body ?? ''));
        if ($fromTemplate !== '') {
            return $fromTemplate;
        }

        return (string) ($this->terms_conditions ?? '');
    }

    /** Alias so invoice/cart views can reuse course fee field names. */
    public function getPackageFeatureAttribute()
    {
        return $this->package_includes;
    }
}
