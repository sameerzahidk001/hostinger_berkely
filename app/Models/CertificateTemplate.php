<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CertificateTemplate extends Model
{
    protected $table = 'certificate_templates';

    public const DEFAULT_BG = 'certificates/assets/berkeley-certificate-bg.png';

    protected $fillable = [
        'name',
        'eyebrow',
        'title',
        'school_name',
        'intro_line',
        'completion_line',
        'heading_line',
        'body_text',
        'orientation',
        'primary_color',
        'accent_color',
        'background_path',
        'logo_path',
        'seal_path',
        'signer_left_name',
        'signer_left_title',
        'signer_left_signature',
        'signer_right_name',
        'signer_right_title',
        'signer_right_signature',
        'footer_org_line',
        'footer_email',
        'footer_website',
        'verify_heading',
        'default_duration',
        'default_cpd_hours',
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
                $table->string('heading_line', 255)->nullable();
                $table->text('body_text')->nullable();
                $table->string('orientation', 20)->default('portrait');
                $table->string('primary_color', 20)->nullable();
                $table->string('accent_color', 20)->nullable();
                $table->string('background_path', 500)->nullable();
                $table->string('logo_path', 500)->nullable();
                $table->string('seal_path', 500)->nullable();
                $table->string('signer_left_name', 255)->nullable();
                $table->string('signer_left_title', 255)->nullable();
                $table->string('signer_left_signature', 500)->nullable();
                $table->string('signer_right_name', 255)->nullable();
                $table->string('signer_right_title', 255)->nullable();
                $table->string('signer_right_signature', 500)->nullable();
                $table->string('footer_org_line', 500)->nullable();
                $table->string('footer_email', 255)->nullable();
                $table->string('footer_website', 255)->nullable();
                $table->string('verify_heading', 255)->nullable();
                $table->string('default_duration', 255)->nullable();
                $table->string('default_cpd_hours', 100)->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->boolean('is_default')->default(false)->index();
                $table->timestamps();
            });
        }

        static::ensureColumns();
        static::ensureDefault();

        return Schema::hasTable('certificate_templates');
    }

    public static function ensureColumns(): void
    {
        if (! Schema::hasTable('certificate_templates')) {
            return;
        }

        $adds = [
            'heading_line' => fn (Blueprint $t) => $t->string('heading_line', 255)->nullable(),
            'body_text' => fn (Blueprint $t) => $t->text('body_text')->nullable(),
            'background_path' => fn (Blueprint $t) => $t->string('background_path', 500)->nullable(),
            'logo_path' => fn (Blueprint $t) => $t->string('logo_path', 500)->nullable(),
            'seal_path' => fn (Blueprint $t) => $t->string('seal_path', 500)->nullable(),
            'signer_left_name' => fn (Blueprint $t) => $t->string('signer_left_name', 255)->nullable(),
            'signer_left_title' => fn (Blueprint $t) => $t->string('signer_left_title', 255)->nullable(),
            'signer_left_signature' => fn (Blueprint $t) => $t->string('signer_left_signature', 500)->nullable(),
            'signer_right_name' => fn (Blueprint $t) => $t->string('signer_right_name', 255)->nullable(),
            'signer_right_title' => fn (Blueprint $t) => $t->string('signer_right_title', 255)->nullable(),
            'signer_right_signature' => fn (Blueprint $t) => $t->string('signer_right_signature', 500)->nullable(),
            'footer_org_line' => fn (Blueprint $t) => $t->string('footer_org_line', 500)->nullable(),
            'footer_email' => fn (Blueprint $t) => $t->string('footer_email', 255)->nullable(),
            'footer_website' => fn (Blueprint $t) => $t->string('footer_website', 255)->nullable(),
            'verify_heading' => fn (Blueprint $t) => $t->string('verify_heading', 255)->nullable(),
            'default_duration' => fn (Blueprint $t) => $t->string('default_duration', 255)->nullable(),
            'default_cpd_hours' => fn (Blueprint $t) => $t->string('default_cpd_hours', 100)->nullable(),
        ];

        foreach ($adds as $col => $cb) {
            if (! Schema::hasColumn('certificate_templates', $col)) {
                Schema::table('certificate_templates', $cb);
            }
        }
    }

    public static function berkeleyDefaults(): array
    {
        return [
            'name' => 'Berkeley Certificate of Completion',
            'eyebrow' => 'CERTIFICATE',
            'title' => 'OF COMPLETION',
            'heading_line' => 'CERTIFICATE OF COMPLETION',
            'school_name' => 'Berkeley School of Business, Arts & Sciences',
            'intro_line' => '',
            'completion_line' => '',
            'body_text' => 'having attended and successfully completed the training programme, has been awarded this certificate in',
            'orientation' => 'portrait',
            'primary_color' => '#6b1d2a',
            'accent_color' => '#c5a46a',
            'background_path' => self::DEFAULT_BG,
            'signer_left_name' => 'Edward Moroch',
            'signer_left_title' => 'Chairman of Council',
            'signer_right_name' => 'Arthur Davies',
            'signer_right_title' => 'Principal & President',
            'footer_org_line' => 'Berkeley School of Business, Arts & Sciences Ltd. | UKPRN: 10101119',
            'footer_email' => 'Registrar@eduberkeley.com',
            'footer_website' => 'www.eduberkeley.com',
            'verify_heading' => 'CERTIFICATE VERIFICATION',
            'is_active' => true,
            'is_default' => true,
        ];
    }

    public static function ensureDefault(): ?self
    {
        if (! Schema::hasTable('certificate_templates')) {
            return null;
        }

        $existing = static::query()->orderByDesc('is_default')->orderBy('id')->first();
        if ($existing) {
            static::fillEmptyBerkeleyFields($existing);
            if (! $existing->is_default) {
                $existing->is_default = true;
                $existing->save();
            }

            return $existing;
        }

        return static::query()->create(static::berkeleyDefaults());
    }

    public static function fillEmptyBerkeleyFields(self $template): void
    {
        $changed = false;
        foreach (static::berkeleyDefaults() as $key => $value) {
            if (in_array($key, ['name', 'is_default', 'is_active'], true)) {
                continue;
            }
            $current = $template->{$key} ?? null;
            if (($current === null || $current === '') && $value !== null && $value !== '') {
                $template->{$key} = $value;
                $changed = true;
            }
        }
        if ($changed) {
            $template->save();
        }
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

    public function headingText(): string
    {
        $v = trim((string) $this->heading_line);
        if ($v !== '') {
            return $v;
        }
        $parts = array_filter([trim((string) $this->eyebrow), trim((string) $this->title)]);

        return $parts ? implode(' ', $parts) : 'CERTIFICATE OF COMPLETION';
    }

    public function titleText(): string
    {
        return $this->headingText();
    }

    public function bodyText(): string
    {
        $v = trim((string) $this->body_text);
        if ($v !== '') {
            return $v;
        }
        $legacy = trim(trim((string) $this->intro_line) . ' ' . trim((string) $this->completion_line));

        return $legacy !== ''
            ? $legacy
            : 'having attended and successfully completed the training programme, has been awarded this certificate in';
    }

    public function paperOrientation(): string
    {
        return strtolower((string) $this->orientation) === 'landscape' ? 'landscape' : 'portrait';
    }

    public function resolvedBackgroundPath(): string
    {
        $v = trim((string) $this->background_path);

        return $v !== '' ? $v : self::DEFAULT_BG;
    }
}
