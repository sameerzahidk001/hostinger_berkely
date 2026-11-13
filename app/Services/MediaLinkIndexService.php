<?php

namespace App\Services;

use App\Models\Course;
use App\Models\DynamicLabels;
use App\Models\PageSection;
use App\Models\SiteSettings;
use Illuminate\Support\Facades\Schema;

/**
 * Filename → page/course map so Media "Linked pages" does not LIKE-scan JSON per row.
 */
class MediaLinkIndexService
{
    public function path(): string
    {
        return storage_path('app/media-link-index.json');
    }

    public function forget(): void
    {
        @unlink($this->path());
    }

    public function read(): array
    {
        $path = $this->path();
        if (! is_file($path)) {
            return $this->emptyState();
        }
        $decoded = json_decode((string) @file_get_contents($path), true);

        return is_array($decoded) ? array_merge($this->emptyState(), $decoded) : $this->emptyState();
    }

    public function ensure(int $budgetSeconds = 7): array
    {
        $state = $this->read();
        if (! empty($state['complete'])) {
            return $state;
        }

        return $this->build($state, $budgetSeconds);
    }

    /**
     * @param  array<int, array{rel?:string,name?:string}>  $files
     * @return array{by_path: array<string, array<int, string>>, by_name: array<string, array<int, string>>}
     */
    public function usagesFor(array $files, ?array $state = null): array
    {
        $state = $state ?? $this->read();
        $byStem = $state['by_stem'] ?? [];
        $byPath = [];
        $byName = [];

        foreach ($files as $file) {
            $rel = strtolower(str_replace('\\', '/', (string) ($file['rel'] ?? '')));
            $name = strtolower((string) ($file['name'] ?? basename($rel)));
            $stem = strtolower((string) pathinfo($name, PATHINFO_FILENAME));
            $hits = $this->uniqueTokens(array_merge(
                $byStem[$rel] ?? [],
                $byStem[$name] ?? [],
                $stem !== '' ? ($byStem[$stem] ?? []) : []
            ));
            if ($rel !== '') {
                $byPath[$rel] = $hits;
            }
            if ($name !== '') {
                $byName[$name] = $hits;
            }
            if (strlen($stem) >= 8) {
                $byName[$stem] = $hits;
            }
        }

        return ['by_path' => $byPath, 'by_name' => $byName];
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private function build(array $state, int $budgetSeconds): array
    {
        $deadline = microtime(true) + max(2, $budgetSeconds);

        if (empty($state['sections_done'])) {
            $state = $this->scanPageSections($state, $deadline);
            $this->write($state);
            if (microtime(true) >= $deadline && empty($state['sections_done'])) {
                return $state;
            }
        }

        if (! empty($state['sections_done']) && empty($state['courses_done'])) {
            $this->scanCourses($state);
            $state['courses_done'] = true;
            $this->write($state);
        }

        if (microtime(true) >= $deadline) {
            return $state;
        }

        if (! empty($state['courses_done']) && empty($state['labels_done'])) {
            $this->scanLabels($state);
            $state['labels_done'] = true;
            $this->write($state);
        }

        if (! empty($state['labels_done']) && empty($state['settings_done'])) {
            $this->scanSettings($state);
            $state['settings_done'] = true;
        }

        $state['complete'] = ! empty($state['sections_done'])
            && ! empty($state['courses_done'])
            && ! empty($state['labels_done'])
            && ! empty($state['settings_done']);
        $this->write($state);

        return $state;
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private function scanPageSections(array $state, float $deadline): array
    {
        if (! class_exists(PageSection::class) || ! Schema::hasTable('page_sections')) {
            $state['sections_done'] = true;

            return $state;
        }

        $after = (int) ($state['section_id'] ?? 0);
        $rows = PageSection::query()
            ->select(['id', 'page_id', 'data'])
            ->with('page:id,page_name')
            ->where('id', '>', $after)
            ->orderBy('id')
            ->limit(80)
            ->get();

        if ($rows->isEmpty()) {
            $state['sections_done'] = true;

            return $state;
        }

        foreach ($rows as $section) {
            if (microtime(true) >= $deadline) {
                break;
            }
            $state['section_id'] = (int) $section->id;
            $page = $section->page;
            if (! $page) {
                continue;
            }
            $label = trim((string) ($page->page_name ?: '')) ?: ('Page #' . $page->id);
            $url = $this->namedUrl('pages.edit', $page->id, '/admin/pages/' . $page->id . '/edit');
            $data = $section->data;
            $blob = is_string($data) ? $data : (string) json_encode($data, JSON_UNESCAPED_SLASHES);
            $this->indexBlob($state, $blob, $label, $url);
        }

        if ($rows->count() < 80) {
            $state['sections_done'] = true;
        }

        return $state;
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function scanCourses(array &$state): void
    {
        if (! class_exists(Course::class) || ! Schema::hasTable('courses')) {
            return;
        }
        $cols = [];
        foreach (['thumbnail', 'post_image', 'overview_img', 'banner_section', 'image_text_2col_card'] as $col) {
            if (Schema::hasColumn('courses', $col)) {
                $cols[] = $col;
            }
        }
        if ($cols === []) {
            return;
        }
        foreach (Course::query()->select(array_merge(['id', 'title'], $cols))->orderBy('id')->cursor() as $course) {
            $label = 'Course: ' . trim((string) ($course->title ?: ('#' . $course->id)));
            $url = $this->namedUrl('course.edit', $course->id, '/admin/course/' . $course->id . '/edit');
            $this->indexBlob($state, json_encode($course->getAttributes(), JSON_UNESCAPED_SLASHES), $label, $url);
        }
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function scanLabels(array &$state): void
    {
        if (! class_exists(DynamicLabels::class) || ! Schema::hasTable('course_dynamic_labels')) {
            return;
        }
        $cols = [];
        foreach ([
            'banner_image', 'who_can_do_img', 'lectures_img', 'practice_session_img',
            'mock_examination_img', 'career_path_section_img', 'exam_information_section_img',
            'learner_stories_img', 'what_you_earn_img',
        ] as $col) {
            if (Schema::hasColumn('course_dynamic_labels', $col)) {
                $cols[] = $col;
            }
        }
        if ($cols === []) {
            return;
        }
        foreach (DynamicLabels::query()->select(array_merge(['id', 'course_id'], $cols))->orderBy('id')->cursor() as $row) {
            $courseId = (int) $row->course_id;
            $label = $courseId > 0 ? ('Course labels #' . $courseId) : 'Course labels';
            $url = $courseId > 0
                ? $this->namedUrl('course.edit', $courseId, '/admin/course/' . $courseId . '/edit')
                : '#';
            $this->indexBlob($state, json_encode($row->getAttributes(), JSON_UNESCAPED_SLASHES), $label, $url);
        }
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function scanSettings(array &$state): void
    {
        if (! class_exists(SiteSettings::class) || ! Schema::hasTable('site_settings')) {
            return;
        }
        $settings = SiteSettings::query()->first();
        if (! $settings) {
            return;
        }
        $this->indexBlob(
            $state,
            json_encode($settings->toArray(), JSON_UNESCAPED_SLASHES),
            'Site Settings',
            $this->namedUrl('site-settings.index', [], '/admin/site-settings')
        );
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function indexBlob(array &$state, string $blob, string $label, string $url): void
    {
        foreach ($this->namesFromBlob($blob) as $key) {
            $token = $label . '|' . $url;
            $list = $state['by_stem'][$key] ?? [];
            if (! in_array($token, $list, true)) {
                $list[] = $token;
                $state['by_stem'][$key] = $list;
            }
        }
    }

    /**
     * @return array<int, string>
     */
    private function namesFromBlob(string $blob): array
    {
        if ($blob === '') {
            return [];
        }
        if (! preg_match_all(
            '#(?:https?://[^/\s"\']+/)?(?:public/)?(/?(?:admin|images|uploads|frontend|storage)/[^\s"\'?<>\\]+\.(?:jpe?g|png|gif|webp|avif|svg)|[a-zA-Z0-9][a-zA-Z0-9._-]{4,}\.(?:jpe?g|png|gif|webp|avif|svg))#i',
            $blob,
            $matches
        )) {
            return [];
        }
        $keys = [];
        foreach ($matches[1] as $raw) {
            $rel = strtolower(str_replace('\\', '/', ltrim((string) $raw, '/')));
            $base = basename($rel);
            if ($base === '') {
                continue;
            }
            $keys[$base] = true;
            if ($rel !== $base) {
                $keys[$rel] = true;
            }
            $stem = strtolower((string) pathinfo($base, PATHINFO_FILENAME));
            if (strlen($stem) >= 8) {
                $keys[$stem] = true;
            }
            $keys['images/library/' . $base] = true;
            if (str_starts_with($rel, 'admin/')) {
                $keys[$rel] = true;
            }
        }

        return array_keys($keys);
    }

    /**
     * @param  array<int, string>  $tokens
     * @return array<int, string>
     */
    private function uniqueTokens(array $tokens): array
    {
        $out = [];
        $seen = [];
        foreach ($tokens as $token) {
            if ($token === '' || isset($seen[$token])) {
                continue;
            }
            $seen[$token] = true;
            $out[] = $token;
        }

        return $out;
    }

    private function namedUrl(string $name, mixed $params, string $fallback): string
    {
        try {
            return route($name, $params);
        } catch (\Throwable $e) {
            return url($fallback);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyState(): array
    {
        return [
            'complete' => false,
            'section_id' => 0,
            'sections_done' => false,
            'courses_done' => false,
            'labels_done' => false,
            'settings_done' => false,
            'by_stem' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function write(array $state): void
    {
        $dir = dirname($this->path());
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $tmp = $this->path() . '.tmp';
        if (@file_put_contents($tmp, json_encode($state)) !== false) {
            @rename($tmp, $this->path());
        }
    }
}
