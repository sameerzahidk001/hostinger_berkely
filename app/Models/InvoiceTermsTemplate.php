<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class InvoiceTermsTemplate extends Model
{
    protected $table = 'invoice_terms_templates';

    protected $fillable = [
        'name',
        'body',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function ensureTable(): void
    {
        try {
            if (! Schema::hasTable('invoice_terms_templates')) {
                Schema::create('invoice_terms_templates', function (Blueprint $table) {
                    $table->id();
                    $table->string('name');
                    $table->longText('body')->nullable();
                    $table->boolean('is_active')->default(true);
                    $table->timestamps();
                });
            }

            if (Schema::hasTable('course_fees') && ! Schema::hasColumn('course_fees', 'invoice_terms_template_id')) {
                Schema::table('course_fees', function (Blueprint $table) {
                    $table->unsignedBigInteger('invoice_terms_template_id')->nullable()->index();
                });
            }

            if (Schema::hasTable('page_fee_packages') && ! Schema::hasColumn('page_fee_packages', 'invoice_terms_template_id')) {
                Schema::table('page_fee_packages', function (Blueprint $table) {
                    $table->unsignedBigInteger('invoice_terms_template_id')->nullable()->index();
                });
            }
        } catch (\Throwable $e) {
            Log::warning('invoice_terms_templates ensure failed: ' . $e->getMessage());
        }
    }

    public static function optionsForSelect(bool $activeOnly = true)
    {
        self::ensureTable();

        $query = static::query()->orderBy('name');
        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->get(['id', 'name', 'body', 'is_active']);
    }

    public function courseFees()
    {
        return $this->hasMany(CourseFee::class, 'invoice_terms_template_id');
    }

    public function pageFeePackages()
    {
        return $this->hasMany(PageFeePackage::class, 'invoice_terms_template_id');
    }
}
