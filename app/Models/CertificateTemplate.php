<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CertificateTemplate extends Model
{
    protected $table = 'certificate_templates';

    protected $fillable = [
        'name',
        'eyebrow',
        'title',
        'school_name',
        'intro_line',
        'completion_line',
        'orientation',
        'primary_color',
        'accent_color',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public static function ensureTable(): bool
    {
        if (! Schema::hasTable('certificate_templates')) {
            Schema::create('certificate_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name', 255);
                $table->string('eyebrow', 255)->nullable();
                $table->string('title', 255)->nullable();
                $table->string('school_name', 255)->nullable();
                $table->string('intro_line', 500)->nullable();
                $table->string('completion_line', 500)->nullable();
                $table->string('orientation', 20)->default('landscape');
                $table->string('primary_color', 20)->nullable();
                $table->string('accent_color', 20)->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->boolean('is_default')->default(false)->index();
                $table->timestamps();
            });
        }

        static::ensureDefault();

        return Schema::hasTable('certificate_templates');
    }

    public static function ensureDefault(): ?self
    {
        if (! Schema::hasTable('certificate_templates')) {
            return null;
        }

        $existing = static::query()->orderByDesc('is_default')->orderBy('id')->first();
        if ($existing) {
            if (! $existing->is_default) {
                $existing->is_default = true;
                $existing->save();
            }

            return $existing;
        }

        return static::query()->create([
            'name' => 'Default Certificate',
            'eyebrow' => 'Certificate of Completion',
            'title' => 'Digital Certificate',
            'school_name' => 'Berkeley School of Business, Arts & Sciences',
            'intro_line' => 'This is to certify that',
            'completion_line' => 'has successfully completed',
            'orientation' => 'landscape',
            'primary_color' => '#000435',
            'accent_color' => '#f8961f',
            'is_active' => true,
            'is_default' => true,
        ]);
    }

    public static function activeOptions()
    {
        static::ensureTable();

        return static::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    public function markAsDefault(): void
    {
        static::query()->where('id', '!=', $this->id)->update(['is_default' => false]);
        $this->is_default = true;
        $this->save();
    }

    public function schoolName(): string
    {
        $v = trim((string) $this->school_name);

        return $v !== '' ? $v : 'Berkeley School of Business, Arts & Sciences';
    }

    public function eyebrowText(): string
    {
        $v = trim((string) $this->eyebrow);

        return $v !== '' ? $v : 'Certificate of Completion';
    }

    public function titleText(): string
    {
        $v = trim((string) $this->title);

        return $v !== '' ? $v : 'Digital Certificate';
    }

    public function introLineText(): string
    {
        $v = trim((string) $this->intro_line);

        return $v !== '' ? $v : 'This is to certify that';
    }

    public function completionLineText(): string
    {
        $v = trim((string) $this->completion_line);

        return $v !== '' ? $v : 'has successfully completed';
    }

    public function paperOrientation(): string
    {
        return strtolower((string) $this->orientation) === 'portrait' ? 'portrait' : 'landscape';
    }
}
