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
    ];

    public static function ensureTable(): bool
    {
        if (Schema::hasTable('certificate_verify_logs')) {
            return true;
        }

        Schema::create('certificate_verify_logs', function (Blueprint $table) {
            $table->id();
            $table->string('certificate_code', 64)->nullable()->index();
            $table->string('result', 40)->nullable()->index();
            $table->string('ip', 64)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();
        });

        return Schema::hasTable('certificate_verify_logs');
    }
}
