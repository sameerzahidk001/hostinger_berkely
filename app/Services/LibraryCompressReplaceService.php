<?php

namespace App\Services;

use App\Models\Course;
use App\Models\DynamicLabels;
use App\Models\MediaAsset;
use App\Models\PageSection;
use App\Models\SiteSettings;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * Keep compressed WebP/AVIF library files, retarget page/course refs, then
 * permanently delete the leftover larger PNG/JPG copies.
 */
class LibraryCompressReplaceService
{
    public function run(int $budgetSeconds = 8): array
    {
        $dir = public_path('images/library');
        $statePath = storage_path('app/library-compress-state.json');
        $state = $this->readState($statePath);
        $deadline = microtime(true) + max(2, $budgetSeconds);

        $pairs = $this->replacementPairs($dir);
        if ($pairs === []) {
            $state['complete'] = true;
            $state['deleted'] = $state['deleted'] ?? 0;
            $this->writeState($statePath, $state);

            return $state;
        }

        if (empty($state['db_done'])) {
            $state = $this->rewriteStoredPaths($pairs, $state, $deadline);
            $this->writeState($statePath, $state);
            if (microtime(true) >= $deadline) {
                return $state;
            }
        }

        if (! empty($state['db_done'])) {
            $deleted = $this->deleteHeavyOriginals($dir, $pairs, $deadline);
            $state['deleted'] = (int) ($state['deleted'] ?? 0) + $deleted;
            $left = $this->replacementPairs($dir);
            $state['complete'] = $left === [];
            $this->writeState($statePath, $state);
            if ($deleted > 0) {
                @unlink(storage_path('app/media-library-index.json'));
            }
        }

        return $state;
    }

    /**
     * @return array<string, string> old basename => compressed basename
     */
    private function replacementPairs(string $dir): array
    {
        if (! is_dir($dir)) {
            return [];
        }
        $names = @scandir($dir);
        if (! is_array($names)) {
            return [];
        }
        $groups = [];
        foreach ($names as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            $ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
            if (! in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'], true)) {
                continue;
            }
            $stem = strtolower((string) pathinfo($name, PATHINFO_FILENAME));
            if ($stem === '') {
                continue;
            }
            $groups[$stem][] = $name;
        }

        $pairs = [];
        foreach ($groups as $files) {
            $keep = $this->preferredCompressed($dir, $files);
            if ($keep === null) {
                continue;
            }
            foreach ($files as $name) {
                if (strcasecmp($name, $keep) === 0) {
                    continue;
                }
                $ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
                if (! in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true)) {
                    continue;
                }
                $pairs[$name] = $keep;
            }
        }

        uksort($pairs, static fn ($a, $b) => strlen((string) $b) <=> strlen((string) $a));

        return $pairs;
    }

    /**
     * @param  array<int, string>  $files
     */
    private function preferredCompressed(string $dir, array $files): ?string
    {
        foreach (['webp', 'avif'] as $ext) {
            foreach ($files as $name) {
                if (strtolower((string) pathinfo($name, PATHINFO_EXTENSION)) !== $ext) {
                    continue;
                }
                $full = $dir . DIRECTORY_SEPARATOR . $name;
                if (is_file($full) && filesize($full) > 24) {
                    return $name;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, string>  $pairs
     */
    private function rewriteStoredPaths(array $pairs, array $state, float $deadline): array
    {
        $from = (int) ($state['section_id'] ?? 0);
        if (class_exists(PageSection::class) && Schema::hasTable('page_sections')) {
            $q = PageSection::query()->select(['id', 'data'])->where('id', '>', $from)->orderBy('id')->limit(80);
            foreach ($q->get() as $section) {
                if (microtime(true) >= $deadline) {
                    $state['section_id'] = (int) $section->id - 1;

                    return $state;
                }
                $data = $section->data;
                $asString = is_string($data);
                $blob = $asString ? $data : (string) json_encode($data, JSON_UNESCAPED_SLASHES);
                $next = $this->applyPairs($blob, $pairs);
                if ($next === $blob) {
                    $state['section_id'] = (int) $section->id;
                    continue;
                }
                $section->data = $asString ? $next : (json_decode($next, true) ?: $data);
                $section->save();
                $state['rewritten'] = (int) ($state['rewritten'] ?? 0) + 1;
                $state['section_id'] = (int) $section->id;
            }
            $more = PageSection::query()->where('id', '>', (int) ($state['section_id'] ?? 0))->exists();
            if ($more) {
                return $state;
            }
        }

        if (microtime(true) >= $deadline) {
            return $state;
        }

        $this->rewriteModelColumns($pairs);
        $state['db_done'] = true;

        return $state;
    }

    /**
     * @param  array<string, string>  $pairs
     */
    private function rewriteModelColumns(array $pairs): void
    {
        $cols = ['thumbnail', 'post_image', 'overview_img', 'banner_section', 'image_text_2col_card'];
        if (class_exists(Course::class) && Schema::hasTable('courses')) {
            $existing = array_values(array_filter($cols, fn ($c) => Schema::hasColumn('courses', $c)));
            if ($existing !== []) {
                foreach (Course::query()->select(array_merge(['id'], $existing))->orderBy('id')->cursor() as $course) {
                    $dirty = false;
                    foreach ($existing as $col) {
                        $was = (string) ($course->{$col} ?? '');
                        $now = $this->applyPairs($was, $pairs);
                        if ($now !== $was) {
                            $course->{$col} = $now;
                            $dirty = true;
                        }
                    }
                    if ($dirty) {
                        $course->save();
                    }
                }
            }
        }

        $labelCols = [
            'banner_image', 'who_can_do_img', 'lectures_img', 'practice_session_img',
            'mock_examination_img', 'career_path_section_img', 'exam_information_section_img',
            'learner_stories_img', 'what_you_earn_img',
        ];
        if (class_exists(DynamicLabels::class) && Schema::hasTable('course_dynamic_labels')) {
            $existing = array_values(array_filter($labelCols, fn ($c) => Schema::hasColumn('course_dynamic_labels', $c)));
            if ($existing !== []) {
                foreach (DynamicLabels::query()->select(array_merge(['id'], $existing))->orderBy('id')->cursor() as $row) {
                    $dirty = false;
                    foreach ($existing as $col) {
                        $was = (string) ($row->{$col} ?? '');
                        $now = $this->applyPairs($was, $pairs);
                        if ($now !== $was) {
                            $row->{$col} = $now;
                            $dirty = true;
                        }
                    }
                    if ($dirty) {
                        $row->save();
                    }
                }
            }
        }

        if (class_exists(MediaAsset::class) && Schema::hasTable('media_assets')) {
            foreach (MediaAsset::query()->orderBy('id')->cursor() as $asset) {
                $was = (string) ($asset->path ?? '');
                $now = $this->applyPairs($was, $pairs);
                if ($now !== $was) {
                    $asset->path = $now;
                    $asset->save();
                }
            }
        }

        if (class_exists(SiteSettings::class) && Schema::hasTable('site_settings')) {
            $settings = SiteSettings::query()->first();
            if ($settings) {
                $arr = $settings->toArray();
                $blob = (string) json_encode($arr, JSON_UNESCAPED_SLASHES);
                $next = $this->applyPairs($blob, $pairs);
                if ($next !== $blob) {
                    $decoded = json_decode($next, true);
                    if (is_array($decoded)) {
                        $settings->fill($decoded);
                        $settings->save();
                    }
                }
            }
        }
    }

    /**
     * @param  array<string, string>  $pairs
     */
    private function applyPairs(string $blob, array $pairs): string
    {
        if ($blob === '') {
            return $blob;
        }
        foreach ($pairs as $old => $new) {
            if ($old === '' || strcasecmp($old, $new) === 0) {
                continue;
            }
            $blob = str_replace($old, $new, $blob);
            $blob = str_replace(rawurlencode($old), rawurlencode($new), $blob);
        }

        return $blob;
    }

    /**
     * @param  array<string, string>  $pairs
     */
    private function deleteHeavyOriginals(string $dir, array $pairs, float $deadline): int
    {
        $n = 0;
        foreach ($pairs as $old => $new) {
            if (microtime(true) >= $deadline) {
                break;
            }
            $keep = $dir . DIRECTORY_SEPARATOR . $new;
            $gone = $dir . DIRECTORY_SEPARATOR . $old;
            if (! is_file($keep) || ! is_file($gone)) {
                continue;
            }
            if (@unlink($gone)) {
                $n++;
            }
        }

        return $n;
    }

    private function readState(string $path): array
    {
        if (! is_file($path)) {
            return ['section_id' => 0, 'db_done' => false, 'deleted' => 0, 'complete' => false, 'rewritten' => 0];
        }
        $decoded = json_decode((string) @file_get_contents($path), true);

        return is_array($decoded) ? $decoded : ['section_id' => 0, 'db_done' => false, 'deleted' => 0, 'complete' => false];
    }

    private function writeState(string $path, array $state): void
    {
        $dir = dirname($path);
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents($path, json_encode($state));
    }
}
