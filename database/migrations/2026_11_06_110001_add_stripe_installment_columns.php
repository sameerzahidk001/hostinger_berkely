<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (class_exists(\App\Models\Installment::class)) {
            \App\Models\Installment::ensureStripeColumns();
        }
        if (class_exists(\App\Models\PaymentGateway::class)) {
            \App\Models\PaymentGateway::ensureStripe();
        }
    }

    public function down(): void
    {
        // Additive columns retained for live safety.
    }
};
