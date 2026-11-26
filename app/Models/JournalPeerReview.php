<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class JournalPeerReview extends Model
{
    protected $table = 'journal_peer_reviews';

    protected $fillable = [
        'manuscript_id', 'reviewer_user_id', 'status', 'recommendation',
        'comments_to_author', 'comments_to_editor', 'invited_at',
        'accepted_at', 'completed_at',
    ];

    protected $casts = [
        'invited_at' => 'datetime',
        'accepted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public static function ensureTable(): bool
    {
        try {
            if (Schema::hasTable('journal_peer_reviews')) {
                return true;
            }
            Schema::create('journal_peer_reviews', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('manuscript_id')->index();
                $table->unsignedBigInteger('reviewer_user_id')->index();
                $table->string('status', 40)->default('invited');
                // invited|accepted|in_progress|completed|declined
                $table->string('recommendation', 40)->nullable();
                // accept|minor_revision|major_revision|reject
                $table->text('comments_to_author')->nullable();
                $table->text('comments_to_editor')->nullable();
                $table->timestamp('invited_at')->nullable();
                $table->timestamp('accepted_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });

            return Schema::hasTable('journal_peer_reviews');
        } catch (\Throwable $e) {
            return Schema::hasTable('journal_peer_reviews');
        }
    }

    public function manuscript()
    {
        return $this->belongsTo(JournalManuscript::class, 'manuscript_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }
}
