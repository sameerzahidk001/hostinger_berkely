<?php

namespace App\Models;

use App\Traits\TracksAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CourseFee extends Model
{
    use HasFactory, TracksAudit;

    protected $fillable = [
        'courses_id', 'package_name', 'currency', 'price',
        'tax_percentage', 'discount_amount', 'short_description', 'key_point',
        'package_includes', 'terms_conditions', 'invoice_terms_template_id', 'priority', 'package_feature', 'installments', 'showonwebsite', 'status',
    ];

    protected $casts = [
        'package_feature' => 'array'
    ];

    public static function ensureTermsColumn(): void
    {
        try {
            if (Schema::hasTable('course_fees') && ! Schema::hasColumn('course_fees', 'terms_conditions')) {
                Schema::table('course_fees', function (Blueprint $table) {
                    $table->longText('terms_conditions')->nullable();
                });
            }
            if (Schema::hasTable('course_fees') && ! Schema::hasColumn('course_fees', 'invoice_terms_template_id')) {
                Schema::table('course_fees', function (Blueprint $table) {
                    $table->unsignedBigInteger('invoice_terms_template_id')->nullable()->index();
                });
            }
            InvoiceTermsTemplate::ensureTable();
        } catch (\Throwable $e) {
            Log::warning('course_fees terms_conditions ensure failed: ' . $e->getMessage());
        }
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

    // Define relationship with Course
    public function course()
    {
        return $this->belongsTo(Course::class, 'courses_id', 'id');
    }
    public function payments()
    {
        return $this->hasMany(Payment::class, 'package_id');
    }
    public function installmentRequest()
{
    return $this->hasMany(InstallmentRequest::class, 'package_id', 'id');
}

}
