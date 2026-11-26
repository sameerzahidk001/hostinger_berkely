<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class JournalPolicy extends Model
{
    protected $table = 'journal_policies';

    protected $fillable = [
        'key', 'title', 'body', 'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public static function ensureTable(): bool
    {
        try {
            if (Schema::hasTable('journal_policies')) {
                return true;
            }
            Schema::create('journal_policies', function (Blueprint $table) {
                $table->id();
                $table->string('key', 80)->unique();
                $table->string('title', 180);
                $table->longText('body')->nullable();
                $table->boolean('is_published')->default(false);
                $table->timestamps();
            });

            return Schema::hasTable('journal_policies');
        } catch (\Throwable $e) {
            return Schema::hasTable('journal_policies');
        }
    }

    public static function ensureDefaults(): void
    {
        if (! self::ensureTable()) {
            return;
        }

        $defaults = [
            'ethics' => 'Publication ethics',
            'ai_disclosure' => 'AI disclosure',
            'conflict_of_interest' => 'Conflict of interest',
            'plagiarism' => 'Plagiarism and originality',
            'author_guidelines' => 'Author guidelines',
        ];

        foreach ($defaults as $key => $title) {
            self::query()->firstOrCreate(
                ['key' => $key],
                ['title' => $title, 'body' => '', 'is_published' => false]
            );
        }
    }
}
