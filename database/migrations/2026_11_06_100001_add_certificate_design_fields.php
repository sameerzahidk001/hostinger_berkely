<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (class_exists(\App\Models\CertificateTemplate::class)) {
            \App\Models\CertificateTemplate::ensureTable();
        }
        if (class_exists(\App\Models\StudentCompletion::class)) {
            \App\Models\StudentCompletion::ensureTable();
        }
        if (class_exists(\App\Models\LmsCertificate::class)) {
            \App\Models\LmsCertificate::ensureTable();
        }
    }

    public function down(): void
    {
        // Additive columns retained for live safety.
    }
};
