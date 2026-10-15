<?php

namespace App\Models;

use App\Traits\TracksAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class InvoiceTermsTemplate extends Model
{
    use TracksAudit;

    protected $table = 'invoice_terms_templates';

    protected $fillable = [
        'name',
        'body',
        'is_active',
        'created_by',
        'updated_by',
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
                    $table->unsignedBigInteger('created_by')->nullable()->index();
                    $table->unsignedBigInteger('updated_by')->nullable()->index();
                    $table->timestamps();
                });
            }

            if (Schema::hasTable('invoice_terms_templates') && ! Schema::hasColumn('invoice_terms_templates', 'created_by')) {
                Schema::table('invoice_terms_templates', function (Blueprint $table) {
                    $table->unsignedBigInteger('created_by')->nullable()->index();
                });
            }
            if (Schema::hasTable('invoice_terms_templates') && ! Schema::hasColumn('invoice_terms_templates', 'updated_by')) {
                Schema::table('invoice_terms_templates', function (Blueprint $table) {
                    $table->unsignedBigInteger('updated_by')->nullable()->index();
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

    /**
     * Assign this template to all fee packages and refresh invoice T&C text.
     *
     * @return array{packages:int, invoices:int}
     */
    public function linkToAllPackagesAndInvoices(): array
    {
        $packages = 0;
        $invoices = 0;

        if (Schema::hasTable('course_fees') && Schema::hasColumn('course_fees', 'invoice_terms_template_id')) {
            $payload = ['invoice_terms_template_id' => $this->id];
            if (Schema::hasColumn('course_fees', 'terms_conditions')) {
                $payload['terms_conditions'] = $this->body;
            }
            $packages += CourseFee::query()->update($payload);
        }

        if (Schema::hasTable('page_fee_packages') && Schema::hasColumn('page_fee_packages', 'invoice_terms_template_id')) {
            $payload = ['invoice_terms_template_id' => $this->id];
            if (Schema::hasColumn('page_fee_packages', 'terms_conditions')) {
                $payload['terms_conditions'] = $this->body;
            }
            $packages += PageFeePackage::query()->update($payload);
        }

        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'terms_conditions')) {
            $invoices = Payment::query()->update(['terms_conditions' => $this->body]);
        }

        return ['packages' => (int) $packages, 'invoices' => (int) $invoices];
    }

    /** Public display code, e.g. PAYTC1 */
    public function displayCode(): string
    {
        return 'PAYTC' . (int) $this->id;
    }

    public function optionLabel(): string
    {
        return $this->displayCode() . ' — ' . $this->name;
    }

    public static function optionsForSelect(bool $activeOnly = true)
    {
        self::ensureTable();

        $query = static::query()->orderBy('id');
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
