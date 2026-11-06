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
        'template_id',
        'certificate_code',
        'status',
        'issued_at',
        'revoked_at',
        'emailed_at',
        'student_name',
        'student_number',
        'course_title',
        'course_subtitle',
        'programme_duration',
        'cpd_hours',
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
        if (! Schema::hasTable('lms_certificates')) {
            Schema::create('lms_certificates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('completion_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('course_id')->nullable()->index();
                $table->unsignedBigInteger('template_id')->nullable()->index();
                $table->string('certificate_code', 64)->unique();
                $table->string('status', 40)->default('issued')->index();
                $table->timestamp('issued_at')->nullable()->index();
                $table->timestamp('revoked_at')->nullable();
                $table->timestamp('emailed_at')->nullable();
                $table->string('student_name', 255)->nullable();
                $table->string('student_number', 100)->nullable();
                $table->string('course_title', 500)->nullable();
                $table->string('course_subtitle', 500)->nullable();
                $table->string('programme_duration', 255)->nullable();
                $table->string('cpd_hours', 100)->nullable();
                $table->date('completion_date')->nullable();
                $table->string('pdf_path', 500)->nullable();
                $table->string('qr_payload', 500)->nullable();
                $table->unsignedBigInteger('issued_by')->nullable();
                $table->string('issued_by_name', 255)->nullable();
                $table->text('revoke_reason')->nullable();
                $table->timestamps();
            });
        } else {
            if (! Schema::hasColumn('lms_certificates', 'template_id')) {
                Schema::table('lms_certificates', function (Blueprint $table) {
                    $table->unsignedBigInteger('template_id')->nullable()->index();
                });
            }
            foreach ([
                'student_number' => fn (Blueprint $t) => $t->string('student_number', 100)->nullable(),
                'course_subtitle' => fn (Blueprint $t) => $t->string('course_subtitle', 500)->nullable(),
                'programme_duration' => fn (Blueprint $t) => $t->string('programme_duration', 255)->nullable(),
                'cpd_hours' => fn (Blueprint $t) => $t->string('cpd_hours', 100)->nullable(),
            ] as $col => $cb) {
                if (! Schema::hasColumn('lms_certificates', $col)) {
                    Schema::table('lms_certificates', $cb);
                }
            }
        }

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

    public function template()
    {
        return $this->belongsTo(CertificateTemplate::class, 'template_id');
    }
}
