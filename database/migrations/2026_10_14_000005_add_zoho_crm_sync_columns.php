<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (! Schema::hasColumn('payments', 'zoho_crm_contact_id')) {
                    $table->string('zoho_crm_contact_id')->nullable();
                }
                if (! Schema::hasColumn('payments', 'zoho_crm_deal_id')) {
                    $table->string('zoho_crm_deal_id')->nullable();
                }
                if (! Schema::hasColumn('payments', 'zoho_crm_invoice_id')) {
                    $table->string('zoho_crm_invoice_id')->nullable();
                }
                if (! Schema::hasColumn('payments', 'zoho_crm_sync_status')) {
                    $table->string('zoho_crm_sync_status')->nullable()->index();
                }
                if (! Schema::hasColumn('payments', 'zoho_crm_last_error')) {
                    $table->text('zoho_crm_last_error')->nullable();
                }
                if (! Schema::hasColumn('payments', 'zoho_crm_synced_at')) {
                    $table->timestamp('zoho_crm_synced_at')->nullable();
                }
            });
        }

        if (Schema::hasTable('installments')) {
            Schema::table('installments', function (Blueprint $table) {
                if (! Schema::hasColumn('installments', 'zoho_crm_receipt_id')) {
                    $table->string('zoho_crm_receipt_id')->nullable();
                }
                if (! Schema::hasColumn('installments', 'zoho_crm_sync_status')) {
                    $table->string('zoho_crm_sync_status')->nullable()->index();
                }
                if (! Schema::hasColumn('installments', 'zoho_crm_last_error')) {
                    $table->text('zoho_crm_last_error')->nullable();
                }
                if (! Schema::hasColumn('installments', 'zoho_crm_synced_at')) {
                    $table->timestamp('zoho_crm_synced_at')->nullable();
                }
            });
        }

        if (! Schema::hasTable('zoho_crm_sync_logs')) {
            Schema::create('zoho_crm_sync_logs', function (Blueprint $table) {
                $table->id();
                $table->string('entity_type', 40);
                $table->unsignedBigInteger('entity_id')->nullable()->index();
                $table->string('action', 80)->nullable();
                $table->boolean('success')->default(false);
                $table->text('request_summary')->nullable();
                $table->text('response_summary')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Additive columns retained for live safety.
    }
};
