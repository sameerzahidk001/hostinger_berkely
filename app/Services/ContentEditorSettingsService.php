<?php

namespace App\Services;

use App\Models\ContentEditorSetting;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ContentEditorSettingsService
{
    private ?bool $tableReady = null;

    public function tableExists(): bool
    {
        return $this->ensureStorage();
    }

    /**
     * Creates DB table automatically when missing.
     * Falls back to storage JSON if DB create is not possible.
     */
    public function ensureStorage(): bool
    {
        if ($this->tableReady === true) {
            return true;
        }

        try {
            if (Schema::hasTable('content_editor_settings')) {
                $this->tableReady = true;

                return true;
            }

            Schema::create('content_editor_settings', function (Blueprint $table) {
                $table->id();
                $table->string('setting_key', 100)->unique();
                $table->longText('setting_value')->nullable();
                $table->timestamps();
            });

            $this->tableReady = Schema::hasTable('content_editor_settings');

            return $this->tableReady;
        } catch (\Throwable $e) {
            Log::warning('content_editor_settings auto-create failed, using file storage: ' . $e->getMessage());
            $this->tableReady = false;
            $this->ensureFileStorage();

            return false;
        }
    }

    public function defaultLimits(): array
    {
        return config('content_editor.limits', []);
    }

    public function limits(): array
    {
        $this->ensureStorage();
        $defaults = $this->defaultLimits();
        $overrides = $this->getJson('char_limits', []);

        foreach (['pages', 'courses'] as $module) {
            if (! isset($defaults[$module]) || ! is_array($defaults[$module])) {
                continue;
            }
            foreach ($defaults[$module] as $key => $meta) {
                if (isset($overrides[$module][$key]['max'])) {
                    $defaults[$module][$key]['max'] = max(1, (int) $overrides[$module][$key]['max']);
                }
                if (isset($overrides[$module][$key]['label']) && trim((string) $overrides[$module][$key]['label']) !== '') {
                    $defaults[$module][$key]['label'] = (string) $overrides[$module][$key]['label'];
                }
            }
        }

        return $defaults;
    }

    public function saveLimits(array $limits): void
    {
        $this->ensureStorage();
        $normalized = [];
        foreach (['pages', 'courses'] as $module) {
            if (empty($limits[$module]) || ! is_array($limits[$module])) {
                continue;
            }
            foreach ($limits[$module] as $key => $meta) {
                $normalized[$module][$key] = [
                    'max' => max(1, (int) ($meta['max'] ?? 1000)),
                ];
            }
        }

        $this->putJson('char_limits', $normalized);
    }

    public function aiConfig(): array
    {
        $this->ensureStorage();
        $stored = $this->getJson('ai_config', []);
        $defaults = config('content_editor.ai', []);

        $apiKey = trim((string) ($stored['api_key'] ?? ''));
        if ($apiKey === '') {
            $apiKey = trim((string) ($defaults['api_key'] ?? ''));
        }

        return [
            'enabled' => array_key_exists('enabled', $stored)
                ? (bool) $stored['enabled']
                : (bool) ($defaults['enabled'] ?? true),
            'api_key' => $apiKey,
            'api_key_set' => $apiKey !== '',
            'base_url' => rtrim((string) ($stored['base_url'] ?? $defaults['base_url'] ?? 'https://api.openai.com/v1'), '/'),
            'model' => (string) ($stored['model'] ?? $defaults['model'] ?? 'gpt-4o-mini'),
            'timeout' => (int) ($stored['timeout'] ?? $defaults['timeout'] ?? 60),
            'storage' => $this->tableReady ? 'database' : 'file',
        ];
    }

    public function saveAiConfig(array $data): void
    {
        $this->ensureStorage();
        $current = $this->getJson('ai_config', []);
        $payload = [
            'enabled' => (bool) ($data['enabled'] ?? false),
            'base_url' => rtrim((string) ($data['base_url'] ?? ($current['base_url'] ?? config('content_editor.ai.base_url'))), '/'),
            'model' => trim((string) ($data['model'] ?? ($current['model'] ?? config('content_editor.ai.model')))),
            'timeout' => max(10, (int) ($data['timeout'] ?? ($current['timeout'] ?? 60))),
        ];

        $newKey = trim((string) ($data['api_key'] ?? ''));
        if ($newKey !== '') {
            $payload['api_key'] = $newKey;
        } elseif (! empty($current['api_key'])) {
            $payload['api_key'] = $current['api_key'];
        }

        $this->putJson('ai_config', $payload);
    }

    public function frontendConfig(string $module = 'pages'): array
    {
        $this->ensureStorage();
        $limits = $this->limits();
        $ai = $this->aiConfig();

        $flat = [];
        foreach (($limits[$module] ?? []) as $key => $meta) {
            $flat[$key] = (int) ($meta['max'] ?? 1000);
        }

        return [
            'module' => $module,
            'limits' => $flat,
            'limitMeta' => $limits[$module] ?? [],
            'aiEnabled' => (bool) ($ai['enabled'] ?? false) && ($ai['api_key'] ?? '') !== '',
            'aiConfigured' => ($ai['api_key'] ?? '') !== '',
        ];
    }

    public function maxFor(string $module, string $fieldKey): int
    {
        $limits = $this->limits();
        if (isset($limits[$module][$fieldKey]['max'])) {
            return (int) $limits[$module][$fieldKey]['max'];
        }

        return (int) ($limits[$module]['default']['max'] ?? 3000);
    }

    private function getJson(string $key, $default = [])
    {
        if ($this->tableReady) {
            try {
                $row = ContentEditorSetting::query()->where('setting_key', $key)->first();
                if (! $row || $row->setting_value === null || $row->setting_value === '') {
                    return $default;
                }
                $decoded = json_decode($row->setting_value, true);

                return is_array($decoded) ? $decoded : $default;
            } catch (\Throwable $e) {
                Log::warning('content_editor_settings DB read failed: ' . $e->getMessage());
            }
        }

        $all = $this->readFileStore();

        return is_array($all[$key] ?? null) ? $all[$key] : $default;
    }

    private function putJson(string $key, array $value): void
    {
        if ($this->tableReady) {
            try {
                ContentEditorSetting::query()->updateOrCreate(
                    ['setting_key' => $key],
                    ['setting_value' => json_encode($value)]
                );

                return;
            } catch (\Throwable $e) {
                Log::warning('content_editor_settings DB write failed, using file: ' . $e->getMessage());
                $this->tableReady = false;
            }
        }

        $all = $this->readFileStore();
        $all[$key] = $value;
        $this->writeFileStore($all);
    }

    private function ensureFileStorage(): void
    {
        $dir = storage_path('app');
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        $path = $this->filePath();
        if (! File::exists($path)) {
            File::put($path, json_encode(new \stdClass()));
        }
    }

    private function filePath(): string
    {
        return storage_path('app/content_editor_settings.json');
    }

    private function readFileStore(): array
    {
        $this->ensureFileStorage();
        $raw = File::get($this->filePath());
        $decoded = json_decode($raw ?: '{}', true);

        return is_array($decoded) ? $decoded : [];
    }

    private function writeFileStore(array $data): void
    {
        $this->ensureFileStorage();
        File::put($this->filePath(), json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
