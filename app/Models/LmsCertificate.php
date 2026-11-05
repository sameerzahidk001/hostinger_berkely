<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class LmsCertificate extends Model
{
    protected $table = 'lms_certificates';

    protected $fillable = [
        'completion_id',
        'user_id',
        'course_id',
        'certificate_code',
        'status',
        'issued_at',
        'revoked_at',
        'emailed_at',
        'student_name',
        'course_title',
        'completion_date',
        'pdf_path',
        'qr_payload',
        'issued_by',
        'issued_by_name',
        'revoke_reason',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'revoked_at' => 'datetime',
        'emailed_at' => 'datetime',
        'completion_date' => 'date',
    ];

    public static function ensureTable(): bool
    {
        if (Schema::hasTable('lms_certificates')) {
            return true;
        }

        Schema::create('lms_certificates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('completion_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('course_id')->nullable()->index();
            $table->string('certificate_code', 64)->unique();
            $table->string('status', 40)->default('issued')->index();
            $table->timestamp('issued_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('emailed_at')->nullable();
            $table->string('student_name', 255)->nullable();
            $table->string('course_title', 500)->nullable();
            $table->date('completion_date')->nullable();
            $table->string('pdf_path', 500)->nullable();
            $table->string('qr_payload', 500)->nullable();
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->string('issued_by_name', 255)->nullable();
            $table->text('revoke_reason')->nullable();
            $table->timestamps();
        });

        return Schema::hasTable('lms_certificates');
    }

    public function isValid(): bool
    {
        return strtolower((string) $this->status) === 'issued';
    }

    public function isRevoked(): bool
    {
        return strtolower((string) $this->status) === 'revoked';
    }

    public function verifyUrl(): string
    {
        return url('/verify-certificate/' . urlencode((string) $this->certificate_code));
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function completion()
    {
        return $this->belongsTo(StudentCompletion::class, 'completion_id');
    }
}
