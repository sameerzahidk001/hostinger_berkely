<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class JournalSubject extends Model
{
    protected $table = 'journal_subjects';

    protected $fillable = [
        'name', 'slug', 'description', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function ensureTable(): bool
    {
        try {
            if (Schema::hasTable('journal_subjects')) {
                return true;
            }
            Schema::create('journal_subjects', function (Blueprint $table) {
                $table->id();
                $table->string('name', 180);
                $table->string('slug', 180)->unique();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });

            return Schema::hasTable('journal_subjects');
        } catch (\Throwable $e) {
            return Schema::hasTable('journal_subjects');
        }
    }

    public static function makeSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'subject';
        $slug = $base;
        $i = 2;
        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
