<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class JournalManuscript extends Model
{
    protected $table = 'journal_manuscripts';

    protected $fillable = [
        'title', 'slug', 'abstract', 'keywords', 'status',
        'author_user_id', 'editor_user_id', 'subject_id', 'issue_id',
        'corresponding_author_name', 'corresponding_author_email', 'orcid',
        'doi', 'html_body', 'pdf_path', 'references_text',
        'ai_disclosure', 'conflict_of_interest', 'ethics_confirmed',
        'submitted_at', 'decision_at', 'published_at', 'view_count',
        'decision_notes',
    ];

    protected $casts = [
        'ethics_confirmed' => 'boolean',
        'submitted_at' => 'datetime',
        'decision_at' => 'datetime',
        'published_at' => 'datetime',
        'view_count' => 'integer',
    ];

    public static function ensureTable(): bool
    {
        try {
            if (Schema::hasTable('journal_manuscripts')) {
                return true;
            }
            Schema::create('journal_manuscripts', function (Blueprint $table) {
                $table->id();
                $table->string('title', 500);
                $table->string('slug', 220)->unique();
                $table->text('abstract')->nullable();
                $table->string('keywords', 500)->nullable();
                $table->string('status', 40)->default('draft');
                // draft|submitted|under_review|revision|accepted|rejected|published|withdrawn
                $table->unsignedBigInteger('author_user_id')->nullable()->index();
                $table->unsignedBigInteger('editor_user_id')->nullable()->index();
                $table->unsignedBigInteger('subject_id')->nullable()->index();
                $table->unsignedBigInteger('issue_id')->nullable()->index();
                $table->string('corresponding_author_name', 180)->nullable();
                $table->string('corresponding_author_email', 180)->nullable();
                $table->string('orcid', 40)->nullable();
                $table->string('doi', 120)->nullable();
                $table->longText('html_body')->nullable();
                $table->string('pdf_path', 255)->nullable();
                $table->longText('references_text')->nullable();
                $table->text('ai_disclosure')->nullable();
                $table->text('conflict_of_interest')->nullable();
                $table->boolean('ethics_confirmed')->default(false);
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('decision_at')->nullable();
                $table->timestamp('published_at')->nullable();
                $table->unsignedInteger('view_count')->default(0);
                $table->text('decision_notes')->nullable();
                $table->timestamps();
            });

            return Schema::hasTable('journal_manuscripts');
        } catch (\Throwable $e) {
            return Schema::hasTable('journal_manuscripts');
        }
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'editor_user_id');
    }

    public function subject()
    {
        return $this->belongsTo(JournalSubject::class, 'subject_id');
    }

    public function issue()
    {
        return $this->belongsTo(JournalIssue::class, 'issue_id');
    }

    public function reviews()
    {
        return $this->hasMany(JournalPeerReview::class, 'manuscript_id');
    }

    public static function makeSlug(string $title): string
    {
        $base = Str::slug(Str::limit($title, 80, '')) ?: 'manuscript';
        $slug = $base . '-' . Str::lower(Str::random(6));
        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . Str::lower(Str::random(6));
        }

        return $slug;
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}
