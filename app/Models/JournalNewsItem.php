<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class JournalNewsItem extends Model
{
    protected $table = 'journal_news_items';

    protected $fillable = [
        'title', 'slug', 'type', 'body', 'is_published', 'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public static function ensureTable(): bool
    {
        try {
            if (Schema::hasTable('journal_news_items')) {
                return true;
            }
            Schema::create('journal_news_items', function (Blueprint $table) {
                $table->id();
                $table->string('title', 220);
                $table->string('slug', 240)->unique();
                $table->string('type', 40)->default('news'); // news | event
                $table->longText('body')->nullable();
                $table->boolean('is_published')->default(false);
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
            });

            return Schema::hasTable('journal_news_items');
        } catch (\Throwable $e) {
            return Schema::hasTable('journal_news_items');
        }
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'item';
        $slug = $base;
        $i = 2;
        while (
            self::query()
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }
}
