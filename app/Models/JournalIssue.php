<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class JournalIssue extends Model
{
    protected $table = 'journal_issues';

    protected $fillable = [
        'title', 'volume', 'number', 'issue_type', 'published_at',
        'is_current', 'description',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'published_at' => 'date',
    ];

    public static function ensureTable(): bool
    {
        try {
            if (Schema::hasTable('journal_issues')) {
                return true;
            }
            Schema::create('journal_issues', function (Blueprint $table) {
                $table->id();
                $table->string('title', 220);
                $table->string('volume', 40)->nullable();
                $table->string('number', 40)->nullable();
                $table->string('issue_type', 40)->default('regular'); // regular|special|previous
                $table->date('published_at')->nullable();
                $table->boolean('is_current')->default(false);
                $table->text('description')->nullable();
                $table->timestamps();
            });

            return Schema::hasTable('journal_issues');
        } catch (\Throwable $e) {
            return Schema::hasTable('journal_issues');
        }
    }
}
