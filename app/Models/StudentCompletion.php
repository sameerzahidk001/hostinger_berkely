<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class StudentCompletion extends Model
{
    protected $table = 'student_completions';

    protected $fillable = [
        'user_id',
        'course_id',
        'batch_id',
        'template_id',
        'status',
        'completed_at',
        'programme_duration',
        'cpd_hours',
        'course_subtitle',
        'student_number',
        'marked_by',
        'marked_by_name',
        'notes',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public static function ensureTable(): bool
    {
        if (! Schema::hasTable('student_completions')) {
            Schema::create('student_completions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('course_id')->nullable()->index();
                $table->unsignedBigInteger('batch_id')->nullable()->index();
                $table->unsignedBigInteger('template_id')->nullable()->index();
                $table->string('status', 40)->default('completed')->index();
                $table->timestamp('completed_at')->nullable()->index();
                $table->string('programme_duration', 255)->nullable();
                $table->string('cpd_hours', 100)->nullable();
                $table->string('course_subtitle', 500)->nullable();
                $table->string('student_number', 100)->nullable();
                $table->unsignedBigInteger('marked_by')->nullable()->index();
                $table->string('marked_by_name', 255)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        } else {
            if (! Schema::hasColumn('student_completions', 'template_id')) {
                Schema::table('student_completions', function (Blueprint $table) {
                    $table->unsignedBigInteger('template_id')->nullable()->index();
                });
            }
            foreach ([
                'programme_duration' => fn (Blueprint $t) => $t->string('programme_duration', 255)->nullable(),
                'cpd_hours' => fn (Blueprint $t) => $t->string('cpd_hours', 100)->nullable(),
                'course_subtitle' => fn (Blueprint $t) => $t->string('course_subtitle', 500)->nullable(),
                'student_number' => fn (Blueprint $t) => $t->string('student_number', 100)->nullable(),
            ] as $col => $cb) {
                if (! Schema::hasColumn('student_completions', $col)) {
                    Schema::table('student_completions', $cb);
                }
            }
        }

        return Schema::hasTable('student_completions');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function batch()
    {
        return $this->belongsTo(ClassBatch::class, 'batch_id');
    }

    public function template()
    {
        return $this->belongsTo(CertificateTemplate::class, 'template_id');
    }

    /** Prefer certificates() — a completion may have multiple certificates. */
    public function certificate()
    {
        return $this->hasOne(LmsCertificate::class, 'completion_id')->latest('id');
    }

    public function certificates()
    {
        return $this->hasMany(LmsCertificate::class, 'completion_id')->orderByDesc('id');
    }

    public function issuedCertificates()
    {
        return $this->certificates()->where('status', 'issued');
    }
}
