<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('instructor_batch_payments')) {
            return;
        }

        Schema::create('instructor_batch_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_batch_id')->index();
            $table->unsignedBigInteger('instructor_id')->index();
            $table->decimal('rate', 12, 2)->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 10)->default('AED');
            $table->string('status', 20)->default('pending')->index();
            $table->date('paid_date')->nullable();
            $table->text('notes')->nullable();
            $table->string('payment_slip_path')->nullable();
            $table->unsignedBigInteger('created_by_admin_id')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->unsignedBigInteger('updated_by_admin_id')->nullable();
            $table->unsignedBigInteger('updated_by_user_id')->nullable();
            $table->timestamps();

            $table->unique(['class_batch_id', 'instructor_id'], 'ibp_batch_instructor_unique');
        });
    }

    public function down(): void
    {
        // Additive schema retained for live safety.
    }
};
