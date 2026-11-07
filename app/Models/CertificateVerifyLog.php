<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CertificateVerifyLog extends Model
{
    protected $table = 'certificate_verify_logs';

    protected $fillable = [
        'certificate_code',
        'result',
        'ip',
        'user_agent',
        'verifier_name',
        'verifier_email',
        'verifier_phone',
        'verifier_organization',
        'verifier_position',
        'verifier_reason',
        'student_number',
        'ip_city',
        'ip_country',
    ];

    public static function ensureTable(): bool
    {
        if (! Schema::hasTable('certificate_verify_logs')) {
            Schema::create('certificate_verify_logs', function (Blueprint $table) {
                $table->id();
                $table->string('certificate_code', 64)->nullable()->index();
                $table->string('result', 40)->nullable()->index();
                $table->string('ip', 64)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->timestamps();
            });
        }

        static::ensureColumns();

        return Schema::hasTable('certificate_verify_logs');
    }

    public static function ensureColumns(): void
    {
        if (! Schema::hasTable('certificate_verify_logs')) {
            return;
        }

        $adds = [
            'verifier_name' => fn (Blueprint $t) => $t->string('verifier_name', 255)->nullable(),
            'verifier_email' => fn (Blueprint $t) => $t->string('verifier_email', 255)->nullable(),
            'verifier_phone' => fn (Blueprint $t) => $t->string('verifier_phone', 80)->nullable(),
            'verifier_organization' => fn (Blueprint $t) => $t->string('verifier_organization', 255)->nullable(),
            'verifier_position' => fn (Blueprint $t) => $t->string('verifier_position', 255)->nullable(),
            'verifier_reason' => fn (Blueprint $t) => $t->string('verifier_reason', 500)->nullable(),
            'student_number' => fn (Blueprint $t) => $t->string('student_number', 100)->nullable(),
            'ip_city' => fn (Blueprint $t) => $t->string('ip_city', 120)->nullable(),
            'ip_country' => fn (Blueprint $t) => $t->string('ip_country', 120)->nullable(),
        ];
        foreach ($adds as $col => $cb) {
            if (! Schema::hasColumn('certificate_verify_logs', $col)) {
                Schema::table('certificate_verify_logs', $cb);
            }
        }
    }
}
