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
        'status',
        'completed_at',
        'marked_by',
        'marked_by_name',
        'notes',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public static function ensureTable(): bool
    {
        if (Schema::hasTable('student_completions')) {
            return true;
        }

        Schema::create('student_completions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('course_id')->nullable()->index();
            $table->unsignedBigInteger('batch_id')->nullable()->index();
            $table->string('status', 40)->default('completed')->index();
            $table->timestamp('completed_at')->nullable()->index();
            $table->unsignedBigInteger('marked_by')->nullable()->index();
            $table->string('marked_by_name', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

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

    public function certificate()
    {
        return $this->hasOne(LmsCertificate::class, 'completion_id');
    }
}
