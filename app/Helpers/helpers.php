<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Category;
use App\Models\Page;
use App\Models\Role;

if (!function_exists('get_page_faqs')) {
    function get_page_faqs(Request $request)
    {
        $currentUrl = $request->path();
        $page = Page::where('url', $currentUrl)->with('faqs')->first();
        return $page ? $page : collect();
    }
}

if (!function_exists('getFooterCategories')) {
    function getFooterCategories()
    {
        return Category::where('footer', 1)->get();
    }
}

function getYouTubeVideoID($url) {
    preg_match("/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^\"\n\s]+)/", $url, $matches);
    return $matches[1] ?? null;
}

if (!function_exists('section_color')) {
    function section_color(mixed $value, string $default = 'transparent'): string
    {
        $color = trim((string) $value);

        return $color !== '' ? $color : $default;
    }
}

if (!function_exists('section_bg_color')) {
    /** Section-level backgrounds: only fill in when empty; keeps intentional black/dark colors. */
    function section_bg_color(mixed $value, string $default = 'transparent'): string
    {
        return section_color($value, $default);
    }
}

if (!function_exists('banner_bg_color')) {
    /** Banner left panel: restore dark/black default when admin value is empty or white. */
    function banner_bg_color(mixed $value, string $default = '#000000'): string
    {
        $color = strtolower(trim((string) $value));

        if ($color === '' || $color === 'transparent' || in_array($color, ['#ffffff', '#fff', 'white'], true)) {
            return $default;
        }

        return (string) $value;
    }
}

if (!function_exists('card_bg_color')) {
    /**
     * Card backgrounds only. Cards without images that were saved as #000000 by the
     * transparent-checkbox bug should fall back to white instead of solid black.
     */
    function card_bg_color(mixed $value, bool $hasImage, string $defaultWhenEmpty = '#ffffff'): string
    {
        $color = strtolower(trim((string) $value));

        if ($color === '') {
            return $hasImage ? 'transparent' : $defaultWhenEmpty;
        }

        if (! $hasImage && in_array($color, ['#000000', '#000', 'black'], true)) {
            return $defaultWhenEmpty;
        }

        return (string) $value;
    }
}

if (!function_exists('normalize_section_background_value')) {
    function normalize_section_background_value(mixed $value, string $emptyDefault = ''): string
    {
        $color = trim((string) $value);

        return $color === '' ? $emptyDefault : $color;
    }
}

if (!function_exists('normalize_section_backgrounds')) {
    function normalize_section_backgrounds(array $section): array
    {
        foreach (['background', 'background_color', 'content_background', 'card_background', 'border_color', 'content_border_color', 'card_border_color', 'card_hover_background', 'card_hover_border_color'] as $field) {
            if (! array_key_exists($field, $section)) {
                continue;
            }

            $default = in_array($field, ['card_background', 'card_hover_background'], true) ? '#ffffff' : '';
            $section[$field] = normalize_section_background_value($section[$field], $default);
        }

        if (! empty($section['cards']) && is_array($section['cards'])) {
            foreach ($section['cards'] as $index => $card) {
                if (! is_array($card) || ! array_key_exists('background', $card)) {
                    continue;
                }

                $section['cards'][$index]['background'] = normalize_section_background_value($card['background'], '#ffffff');
            }
        }

        return $section;
    }
}

if (!function_exists('section_type_aliases')) {
    function section_type_aliases(): array
    {
        return [
            'hero-banner' => 'hero-banner',
            'banner-section' => 'banner',
            'banner' => 'banner',
            'school-category' => 'school-category',
            'category' => 'category',
            'grid-cards' => 'grid-cards',
            'overlay-cards' => 'overlay-cards',
            'title-section' => 'title-section',
            'media-section' => 'media-section',
            'image-+-text-2-column-card' => 'media-section',
            'image-text-2-column-card' => 'media-section',
            'image-plus-text-2-column-card' => 'media-section',
            'cards' => 'cards',
            'clients' => 'clients',
            'list-section' => 'list',
            'list' => 'list',
            'programmes' => 'programmes',
            'contact-us' => 'contactus',
            'contactus' => 'contactus',
            'separator' => 'separator',
            'separator-section' => 'separator',
            'certificate' => 'certificate',
            'certificate-section' => 'certificate',
            'filter-courses' => 'filter-courses',
            'filter-courses-section' => 'filter-courses',
            'career' => 'career',
            'career-section' => 'career',
            'search-bar' => 'search-bar',
            'search-section' => 'search-section',
            'course-agendas' => 'course-agendas',
            'agenda' => 'course-agendas',
            'agendas' => 'course-agendas',
            'training-calendar' => 'course-agendas',
            'testimonials' => 'testimonials',
            'content' => 'content',
            'instructors' => 'instructors',
        ];
    }
}

if (!function_exists('normalize_section_type_key')) {
    /** Map admin labels (e.g. "Hero Banner", "Banner Section") to frontend/editor keys. */
    function normalize_section_type_key(?string $sectionType): ?string
    {
        if ($sectionType === null || trim($sectionType) === '') {
            return null;
        }

        $normalized = strtolower(str_replace(['_', ' '], '-', trim($sectionType)));
        $aliases = section_type_aliases();

        return $aliases[$normalized] ?? $normalized;
    }
}

if (!function_exists('media_url')) {
    function media_url(mixed $path): ?string
    {
        if (is_array($path)) {
            $path = $path['path'] ?? $path['url'] ?? $path['image'] ?? ($path[0] ?? null);
        }

        if (!is_string($path) || $path === '' || $path === 'null') {
            return null;
        }

        $path = str_replace(
            [
                'https://eduberkeley.com/public/',
                'http://eduberkeley.com/public/',
                'https://eduberkeley.com/',
                'http://eduberkeley.com/',
            ],
            '',
            $path
        );

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $path = ltrim($path, '/');

        $path = ltrim(str_replace('\\', '/', $path), '/');
        $encoded = implode('/', array_map(static function (string $part): string {
            return rawurlencode(rawurldecode($part));
        }, explode('/', $path)));

        if (str_starts_with($path, 'admin/')) {
            return asset($encoded);
        }

        if (str_starts_with($path, 'images/') || str_starts_with($path, 'frontend/')) {
            return asset($encoded);
        }

        return asset('images/library/' . $encoded);
    }
}

if (!function_exists('normalize_public_media_relative_path')) {
    function normalize_public_media_relative_path(mixed $path): ?string
    {
        if (is_array($path)) {
            $path = $path['path'] ?? $path['url'] ?? $path['image'] ?? ($path[0] ?? null);
        }

        if (! is_string($path) || trim($path) === '' || $path === 'null') {
            return null;
        }

        $path = str_replace('\\', '/', trim($path));

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            $host = parse_url($path, PHP_URL_HOST);
            $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

            if ($host && $appHost && ! in_array($host, [$appHost, 'www.' . $appHost, 'berkeleyschool.org', 'www.berkeleyschool.org', 'eduberkeley.com', 'www.eduberkeley.com'], true)) {
                return null;
            }

            $path = ltrim((string) parse_url($path, PHP_URL_PATH), '/');
        }

        $path = str_replace(
            [
                'https://eduberkeley.com/public/',
                'http://eduberkeley.com/public/',
                'https://eduberkeley.com/',
                'http://eduberkeley.com/',
                'https://berkeleyschool.org/public/',
                'http://berkeleyschool.org/public/',
                'https://berkeleyschool.org/',
                'http://berkeleyschool.org/',
            ],
            '',
            $path
        );

        $path = rawurldecode(ltrim($path, '/'));

        if (str_starts_with($path, 'public/')) {
            $path = substr($path, 7);
        }

        if ($path === '' || str_contains($path, '..')) {
            return null;
        }

        if (str_starts_with($path, 'images/') || str_starts_with($path, 'frontend/') || str_starts_with($path, 'admin/')) {
            return $path;
        }

        return 'images/library/' . $path;
    }
}

if (!function_exists('restore_public_upload_if_missing')) {
    function restore_public_upload_if_missing(mixed $path): bool
    {
        $relative = normalize_public_media_relative_path($path);

        if ($relative === null) {
            return false;
        }

        $dest = public_path($relative);

        if (is_file($dest)) {
            return true;
        }

        $src = persistent_uploads_root() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);

        if (! is_file($src)) {
            return false;
        }

        $parent = dirname($dest);

        if (! is_dir($parent) && ! @mkdir($parent, 0755, true) && ! is_dir($parent)) {
            return false;
        }

        return @copy($src, $dest) && is_file($dest);
    }
}

if (!function_exists('count_flat_files')) {
    function count_flat_files(string $dir): int
    {
        if (! is_dir($dir)) {
            return 0;
        }

        $handle = @opendir($dir);

        if ($handle === false) {
            return 0;
        }

        $count = 0;

        while (($entry = readdir($handle)) !== false) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            if (is_file($dir . DIRECTORY_SEPARATOR . $entry)) {
                $count++;
            }
        }

        closedir($handle);

        return $count;
    }
}

if (!function_exists('local_media_exists')) {
    function local_media_exists(mixed $path): bool
    {
        if (is_array($path)) {
            $path = $path['path'] ?? $path['url'] ?? $path['image'] ?? ($path[0] ?? null);
        }

        if (! is_string($path) || trim($path) === '' || $path === 'null') {
            return false;
        }

        $raw = str_replace('\\', '/', trim($path));

        if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) {
            $host = parse_url($raw, PHP_URL_HOST);
            $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

            if ($host && $appHost && ! in_array($host, [$appHost, 'www.' . $appHost, 'berkeleyschool.org', 'www.berkeleyschool.org', 'eduberkeley.com', 'www.eduberkeley.com'], true)) {
                return true;
            }
        }

        $relative = normalize_public_media_relative_path($path);

        if ($relative === null) {
            return false;
        }

        if (is_file(public_path($relative))) {
            return true;
        }

        return restore_public_upload_if_missing($relative);
    }
}

if (!function_exists('persistent_uploads_root')) {
    function persistent_uploads_root(): string
    {
        $configured = env('PERSISTENT_UPLOADS_PATH');
        if (is_string($configured) && trim($configured) !== '') {
            return rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $configured), DIRECTORY_SEPARATOR);
        }

        $outside = dirname(base_path()) . DIRECTORY_SEPARATOR . 'persistent-uploads';

        if (is_dir($outside) || (@mkdir($outside, 0755, true) && is_dir($outside))) {
            if (is_writable($outside)) {
                return $outside;
            }
        }

        return storage_path('app/preserved-public-uploads');
    }
}

if (!function_exists('persistent_upload_dirs')) {
    function persistent_upload_dirs(): array
    {
        return [
            'admin/courses',
            'images/profiles',
            'images/library',
            'images/clients',
            'images',
        ];
    }
}

if (!function_exists('copy_directory_contents')) {
    function copy_directory_contents(string $from, string $to): void
    {
        if (! is_dir($from)) {
            return;
        }

        if (! is_dir($to) && ! @mkdir($to, 0755, true) && ! is_dir($to)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($from, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $target = $to . DIRECTORY_SEPARATOR . $iterator->getSubPathName();

            if ($item->isDir()) {
                if (! is_dir($target)) {
                    @mkdir($target, 0755, true);
                }
                continue;
            }

            $parent = dirname($target);
            if (! is_dir($parent)) {
                @mkdir($parent, 0755, true);
            }

            if (is_file($target) && (int) $item->getSize() === (int) @filesize($target)) {
                continue;
            }

            @copy($item->getPathname(), $target);
        }
    }
}

if (!function_exists('remember_public_upload')) {
    function remember_public_upload(?string $relativePath): void
    {
        $relativePath = ltrim(str_replace('\\', '/', (string) $relativePath), '/');

        if ($relativePath === '' || str_contains($relativePath, '..')) {
            return;
        }

        $source = public_path($relativePath);

        if (! is_file($source)) {
            return;
        }

        $destination = persistent_uploads_root() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $parent = dirname($destination);

        if (! is_dir($parent) && ! @mkdir($parent, 0755, true) && ! is_dir($parent)) {
            return;
        }

        @copy($source, $destination);
    }
}

if (!function_exists('persist_moved_upload')) {
    function persist_moved_upload(string $destinationPath, string $fileName): void
    {
        $publicRoot = rtrim(str_replace('\\', '/', public_path()), '/');
        $dest = rtrim(str_replace('\\', '/', $destinationPath), '/');

        if ($publicRoot === '' || ! str_starts_with($dest, $publicRoot)) {
            return;
        }

        $relative = ltrim(substr($dest, strlen($publicRoot)) . '/' . $fileName, '/');
        remember_public_upload($relative);
    }
}

if (!function_exists('public_upload_move')) {
    function public_upload_move($file, string $destinationPath, string $fileName): void
    {
        if (! is_dir($destinationPath)) {
            @mkdir($destinationPath, 0755, true);
        }

        $file->move($destinationPath, $fileName);
        persist_moved_upload($destinationPath, $fileName);

        // Compress + WebP/variants when GD is available (never deletes the original path).
        try {
            $absolute = rtrim($destinationPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;
            app(\App\Services\ImageOptimizationService::class)->optimizeExistingPath($absolute);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Upload image optimize skipped: ' . $e->getMessage());
        }
    }
}

if (!function_exists('optimized_image_tag')) {
    /**
     * Responsive <picture> with WebP when available + lazy-load by default.
     */
    function optimized_image_tag(?string $src, string $alt = '', array $attrs = []): string
    {
        $src = trim((string) $src);
        if ($src === '') {
            return '';
        }

        $url = str_starts_with($src, 'http') ? $src : asset(ltrim($src, '/'));
        $webp = app(\App\Services\ImageOptimizationService::class)->webpUrl(
            str_starts_with($src, 'http') ? null : ltrim($src, '/')
        );

        $lazy = ($attrs['loading'] ?? 'lazy') !== 'eager';
        $loading = $lazy ? 'lazy' : 'eager';
        $class = e($attrs['class'] ?? '');
        $width = isset($attrs['width']) ? ' width="' . (int) $attrs['width'] . '"' : '';
        $height = isset($attrs['height']) ? ' height="' . (int) $attrs['height'] . '"' : '';
        $style = isset($attrs['style']) ? ' style="' . e($attrs['style']) . '"' : '';
        $decoding = ' decoding="async"';

        $img = '<img src="' . e($url) . '" alt="' . e($alt) . '" loading="' . $loading . '"'
            . $decoding . $width . $height . $style
            . ($class !== '' ? ' class="' . $class . '"' : '')
            . '>';

        if ($webp) {
            return '<picture><source srcset="' . e($webp) . '" type="image/webp">' . $img . '</picture>';
        }

        return $img;
    }
}

if (!function_exists('current_deploy_id')) {
    function current_deploy_id(): string
    {
        $headFile = base_path('.git/HEAD');
        $head = trim((string) @file_get_contents($headFile));

        if ($head === '') {
            return (string) @filemtime(base_path('vendor/autoload.php'));
        }

        if (str_starts_with($head, 'ref: ')) {
            $ref = trim(substr($head, 5));
            $hash = trim((string) @file_get_contents(base_path('.git/' . $ref)));

            return $hash !== '' ? $hash : $head;
        }

        return $head;
    }
}

if (!function_exists('persist_public_uploads_tree')) {
    function persist_public_uploads_tree(): void
    {
        $root = persistent_uploads_root() . DIRECTORY_SEPARATOR . 'public';

        foreach (persistent_upload_dirs() as $dir) {
            copy_directory_contents(public_path($dir), $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $dir));
        }
    }
}

if (!function_exists('restore_persistent_uploads_if_needed')) {
    function restore_persistent_uploads_if_needed(): void
    {
        static $alreadyRan = false;

        if ($alreadyRan || app()->runningUnitTests()) {
            return;
        }

        $alreadyRan = true;

        try {

        $root = persistent_uploads_root();
        $publicMirror = $root . DIRECTORY_SEPARATOR . 'public';
        $marker = $root . DIRECTORY_SEPARATOR . '.restored-for';
        $deployId = current_deploy_id();

        if (! is_dir($root)) {
            persist_public_uploads_tree();
            @file_put_contents($marker, $deployId);

            return;
        }

        $last = trim((string) @file_get_contents($marker));
        $backupCourses = count_flat_files($publicMirror . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'courses');
        $publicCourses = count_flat_files(public_path('admin/courses'));
        $backupLooksFuller = $backupCourses > 0 && $publicCourses < (int) floor($backupCourses * 0.8);
        $deployChanged = $last !== $deployId || $last === '';

        if (! $deployChanged && ! $backupLooksFuller) {
            return;
        }

        if (is_dir($publicMirror)) {
            foreach (persistent_upload_dirs() as $dir) {
                copy_directory_contents(
                    $publicMirror . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $dir),
                    public_path($dir)
                );
            }
        }

        $publicCoursesAfter = count_flat_files(public_path('admin/courses'));
        $backupCoursesNow = count_flat_files($publicMirror . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'courses');

        // Never copy a wiped public tree over a fuller backup.
        if ($backupCoursesNow === 0 || $publicCoursesAfter >= (int) floor($backupCoursesNow * 0.8)) {
            persist_public_uploads_tree();
        }

        @file_put_contents($marker, $deployId);
        } catch (\Throwable $e) {
            // Never block the site if upload backup/restore fails.
        }
    }
}

if (!function_exists('displayable_media_url')) {
    /** URL for frontend display. Restores from backup when possible; does not hide a valid path. */
    function displayable_media_url(mixed $path): ?string
    {
        restore_public_upload_if_missing($path);

        return media_url($path);
    }
}

if (!function_exists('card_image_url')) {
    /** Resolve card images, with fallbacks when library files were deleted or never uploaded. */
    function card_image_url(mixed $path, ?string $title = null): ?string
    {
        restore_public_upload_if_missing($path);

        if (local_media_exists($path)) {
            return media_url($path);
        }

        $basename = is_string($path) ? strtolower(basename(str_replace('\\', '/', $path))) : '';

        $knownMissing = [
            'key-stage-4-6a3f0e7e5d3e1.png' => 'frontend/images/jpg/GCSE.jpg',
            'key-stage-5-6a3f0e7e5ca66.png' => 'frontend/images/jpg/six-form.jpg',
        ];

        if ($basename !== '' && isset($knownMissing[$basename])) {
            return asset($knownMissing[$basename]);
        }

        $label = strtolower((string) $title);

        if (str_contains($label, 'key stage 4') || str_contains($label, 'igcse') || str_contains($label, 'gcse')) {
            return asset('frontend/images/jpg/GCSE.jpg');
        }

        if (str_contains($label, 'key stage 5') || str_contains($label, 'sixth form') || str_contains($label, 'a level')) {
            return asset('frontend/images/jpg/six-form.jpg');
        }

        if (str_contains($label, 'key stage 3') || str_contains($label, 'lower secondary')) {
            return asset('frontend/images/jpg/Primary-1.jpg');
        }

        return media_url($path);
    }
}

if (!function_exists('demote_page_headings')) {
    /**
     * CMS/editor HTML often contains extra H1 tags. Keep a single page H1 in the template only.
     */
    function demote_page_headings(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        return preg_replace(['/<(\/?)h1\b/i'], ['<$1h2'], $html) ?? $html;
    }
}

if (!function_exists('strip_cms_inline_backgrounds')) {
    /**
     * Remove accidental black/inline backgrounds pasted from Word or Summernote.
     */
    function strip_cms_inline_backgrounds(string $html): string
    {
        $html = preg_replace('/\sbgcolor=(["\'])[^"\']*\1/i', '', $html) ?? $html;

        return preg_replace_callback(
            '/\sstyle=(["\'])(.*?)\1/is',
            function (array $matches): string {
                $quote = $matches[1];
                $style = $matches[2];

                $style = preg_replace('/\s*background(?:-color)?\s*:\s*[^;"]+;?/i', '', $style) ?? $style;
                $style = trim($style, " ;");

                if ($style === '') {
                    return '';
                }

                return ' style=' . $quote . $style . $quote;
            },
            $html
        ) ?? $html;
    }
}

if (!function_exists('rewrite_cms_asset_urls')) {
    /**
     * CMS HTML often embeds full URLs from old hosts (e.g. derbygirls.co.uk) with /public/ in the path.
     */
    function rewrite_cms_asset_urls(string $html): string
    {
        $html = preg_replace_callback(
            '#https?://[^"\'>\s]+?/(?:public/)?(frontend/[^"\'>\s]+|images/[^"\'>\s]+)#i',
            static function (array $matches): string {
                return asset($matches[1]);
            },
            $html
        ) ?? $html;

        return preg_replace_callback(
            '#\bsrc=(["\'])(?:\./)?(?:public/)?(frontend/images/svgs/[^"\']+)\1#i',
            static function (array $matches): string {
                return 'src=' . $matches[1] . asset($matches[2]) . $matches[1];
            },
            $html
        ) ?? $html;
    }
}

if (!function_exists('render_cms_html')) {
    function render_cms_html(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        return strip_cms_inline_backgrounds(
            rewrite_public_hrefs(rewrite_cms_asset_urls(demote_page_headings($html)))
        );
    }
}

if (!function_exists('generateFileName')) {
    function generateFileName($file, $prefix = '')
    {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $slug = Str::slug($originalName);
        $unique = uniqid();
        return ($prefix ? $prefix . '_' : '') . $slug . '-' . $unique . '.' . $extension;
    }
}

if (!function_exists('panel_role_name')) {
    function panel_role_name(): ?string
    {
        if (Auth::guard('admin')->check()) {
            return 'admin';
        }

        return Auth::user()?->roles()->value('name');
    }
}

if (!function_exists('lms_portal_layout')) {
    function lms_portal_layout(): string
    {
        return app(\App\Services\StudyMaterialService::class)->isInstructorActor()
            ? 'user.layout.app'
            : 'admin.layout.app';
    }
}

if (!function_exists('lms_portal_home')) {
    function lms_portal_home(): string
    {
        return app(\App\Services\StudyMaterialService::class)->isInstructorActor()
            ? route('user.home')
            : route('admin.home');
    }
}

if (!function_exists('lms_format_number')) {
    /** Format marks/scores: keep fractions, drop trailing .00 (e.g. 80.00 → 80, 53.30 → 53.3). */
    function lms_format_number($value, string $empty = '—'): string
    {
        if ($value === null || $value === '') {
            return $empty;
        }
        if (! is_numeric($value)) {
            return (string) $value;
        }
        $n = (float) $value;
        if (abs($n - round($n)) < 0.0000001) {
            return (string) (int) round($n);
        }
        $s = rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');

        return $s === '' ? '0' : $s;
    }
}

if (!function_exists('instructor_can_view_site_analytics')) {
    function instructor_can_view_site_analytics($user = null): bool
    {
        if (Auth::guard('admin')->check()) {
            return false;
        }

        $user = $user ?? Auth::user();
        if (! $user || ! method_exists($user, 'roles')) {
            return false;
        }

        if (! $user->roles()->where('name', 'instructor')->exists()) {
            return false;
        }

        return is_musa_shaikh_user($user);
    }
}

if (!function_exists('is_musa_shaikh_user')) {
    function is_musa_shaikh_user($user = null): bool
    {
        $user = $user ?? panel_profile_user();
        if (! $user) {
            return false;
        }

        $hay = strtolower(trim(($user->name ?? '') . ' ' . ($user->email ?? '') . ' ' . ($user->username ?? '')));

        if (str_contains($hay, 'musa') && (str_contains($hay, 'shaikh') || str_contains($hay, 'sheikh'))) {
            return true;
        }

        return str_contains($hay, 'musa');
    }
}

if (!function_exists('panel_sees_all_dashboards')) {
    function panel_sees_all_dashboards(): bool
    {
        if (Auth::guard('admin')->check()) {
            return true;
        }

        $role = normalize_panel_role(panel_role_name());
        if ($role === null || $role === 'admin') {
            return true;
        }

        // Head of Faculty (Musa) sees Staff / Instructors / Students / Accountant like Admin.
        return is_musa_shaikh_user();
    }
}

if (!function_exists('panel_profile_user')) {
    function panel_profile_user()
    {
        if (Auth::guard('admin')->check()) {
            return Auth::guard('admin')->user();
        }

        return Auth::user();
    }
}

if (!function_exists('panel_profile_name')) {
    function panel_profile_name($user = null): string
    {
        $user = $user ?? panel_profile_user();

        if (!$user) {
            return '';
        }

        return $user->username ?? $user->name ?? '';
    }
}

if (!function_exists('normalize_profile_image_path')) {
    function normalize_profile_image_path(mixed $path): ?string
    {
        if ($path === null) {
            return null;
        }

        if (is_array($path)) {
            $path = $path['path'] ?? $path['url'] ?? $path['image'] ?? ($path[0] ?? null);
        }

        $path = trim(str_replace('\\', '/', (string) $path));

        if ($path === '' || $path === 'null') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            $path = (string) preg_replace('#^https?://[^/]+/#', '', $path);
        }

        $path = ltrim($path, '/');

        if (str_starts_with($path, 'public/')) {
            $path = substr($path, 7);
        }

        if (str_starts_with($path, 'images/profiles/')) {
            return $path;
        }

        if (str_starts_with($path, '/images/profiles/')) {
            return ltrim($path, '/');
        }

        if (! str_contains($path, '/')) {
            return 'images/profiles/' . $path;
        }

        return $path;
    }
}

if (!function_exists('user_avatar_url')) {
    function user_avatar_url($user = null): string
    {
        $user = $user ?? panel_profile_user();

        $path = normalize_profile_image_path(data_get($user, 'image'));

        if ($path === null) {
            $path = normalize_profile_image_path(data_get($user, 'avatar'));
        }

        if ($path !== null) {
            restore_public_upload_if_missing($path);
            $url = media_url($path);

            if ($url) {
                return $url;
            }
        }

        foreach (['images/profiles/user.png', 'admin/images/logo.png'] as $fallback) {
            if (local_media_exists($fallback)) {
                return asset($fallback);
            }
        }

        return asset('admin/images/logo.png');
    }
}

if (!function_exists('normalize_panel_role')) {
    function normalize_panel_role(?string $role): ?string
    {
        if ($role === null) {
            return null;
        }

        $normalized = str_replace(['-', ' '], '_', strtolower($role));

        if (in_array($normalized, ['librarian', 'content_writer'], true)) {
            return 'content_writer';
        }

        return $normalized;
    }
}

if (!function_exists('normalize_role_key')) {
    function normalize_role_key(?string $role): string
    {
        return str_replace(['-', ' '], '_', strtolower(trim((string) $role)));
    }
}

if (!function_exists('is_admin_login_role')) {
    function is_admin_login_role(?string $role): bool
    {
        return in_array(normalize_role_key($role), ['admin', 'superadmin'], true);
    }
}

if (!function_exists('public_login_url')) {
    function public_login_url(): string
    {
        return url('/login');
    }
}

if (!function_exists('admin_login_url')) {
    function admin_login_url(): string
    {
        return route('admin.login');
    }
}

if (!function_exists('is_content_writer_role_key')) {
    function is_content_writer_role_key(?string $role): bool
    {
        return in_array(normalize_role_key($role), ['librarian', 'content_writer'], true);
    }
}

if (!function_exists('content_writer_role_ids')) {
    function content_writer_role_ids(): array
    {
        return Role::query()
            ->get()
            ->filter(function (Role $role) {
                if (is_content_writer_role_key($role->name)) {
                    return true;
                }

                return str_contains(strtolower((string) $role->description), 'content writer');
            })
            ->pluck('id')
            ->unique()
            ->values()
            ->all();
    }
}

if (!function_exists('role_ids_for_list_type')) {
    function role_ids_for_list_type(string $type): array
    {
        if (is_content_writer_role_key($type)) {
            return content_writer_role_ids();
        }

        $target = normalize_role_key($type);

        return Role::query()
            ->get()
            ->filter(fn (Role $role) => normalize_role_key($role->name) === $target)
            ->pluck('id')
            ->unique()
            ->values()
            ->all();
    }
}

if (!function_exists('resolve_content_writer_role_id')) {
    function resolve_content_writer_role_id(?int $roleId): ?int
    {
        if (! $roleId) {
            return null;
        }

        $role = Role::find($roleId);
        if (! $role || ! is_content_writer_role_key($role->name)) {
            return $roleId;
        }

        $ids = content_writer_role_ids();

        return $ids[0] ?? $roleId;
    }
}

if (!function_exists('seo_field_limits')) {
    function seo_field_limits(): array
    {
        return [
            'title_max' => 60,
            'meta_description_max' => 160,
            'priority_keywords_max_tags' => 10,
            'additional_keywords_max_tags' => 15,
            'keyword_tag_max_length' => 60,
            'focus_keyword_max' => 80,
            'priority_keywords_max_total' => 400,
            'additional_keywords_max_total' => 400,
        ];
    }
}

if (!function_exists('seo_validation_rules')) {
    function seo_validation_rules(bool $titleRequired = true): array
    {
        $limits = seo_field_limits();

        return [
            'title' => ($titleRequired ? 'required' : 'nullable') . '|string|max:' . $limits['title_max'],
            'meta_description' => 'nullable|string|max:' . $limits['meta_description_max'],
            'focus_keyword' => 'nullable|string|max:' . $limits['focus_keyword_max'],
            'keywords' => 'nullable|string|max:' . $limits['priority_keywords_max_total'],
            'additional_keywords' => 'nullable|string|max:' . $limits['additional_keywords_max_total'],
            'thumbnail_alt' => 'nullable|string|max:125',
            'robots_index' => 'nullable|in:index,noindex',
            'robots_follow' => 'nullable|in:follow,nofollow',
        ];
    }
}

if (!function_exists('seo_focus_keyword')) {
    function seo_focus_keyword(mixed $seo): string
    {
        if (is_object($seo)) {
            $dedicated = trim((string) ($seo->focus_keyword ?? ''));
            if ($dedicated !== '') {
                return $dedicated;
            }

            return seo_list_focus_keyword($seo->keywords ?? '');
        }

        return seo_list_focus_keyword(is_string($seo) ? $seo : '');
    }
}

if (!function_exists('seo_list_focus_keyword')) {
    function seo_list_focus_keyword(?string $keywords): string
    {
        $keywords = trim((string) $keywords);
        if ($keywords === '') {
            return '';
        }

        $parts = preg_split('/[,;|]+/', $keywords);

        return trim((string) ($parts[0] ?? ''));
    }
}

if (!function_exists('seo_metadata_list_score')) {
    function seo_metadata_list_score($seo): int
    {
        $title = trim((string) ($seo->title ?? ''));
        $description = trim((string) ($seo->meta_description ?? ''));
        $keywords = trim((string) ($seo->keywords ?? ''));

        $passed = 0;
        $total = 5;

        if ($title !== '') {
            $passed++;
        }

        if ($description !== '') {
            $passed++;
        }

        if ($keywords !== '') {
            $passed++;
        }

        $titleLength = strlen($title);
        if ($titleLength >= 30 && $titleLength <= 60) {
            $passed++;
        }

        $descriptionLength = strlen($description);
        if ($descriptionLength >= 120 && $descriptionLength <= 160) {
            $passed++;
        }

        return (int) round(($passed / $total) * 100);
    }
}

if (!function_exists('seo_list_item_url')) {
    function seo_list_item_url($seo, ?string $categoryPerma = 'category'): string
    {
        if (! empty($seo->course_id)) {
            $seo->loadMissing('course');
            $slug = (string) ($seo->course->slug ?? '');

            return $slug !== '' ? 'course/' . ltrim($slug, '/') : '';
        }

        if (! empty($seo->page_id)) {
            $seo->loadMissing(['page.parent']);
            $page = $seo->page;
            if (! $page) {
                return '';
            }

            if ($page->parent_id && $page->parent) {
                return trim($page->parent->url . '/' . $page->url, '/');
            }

            if ($page->category_id) {
                return trim(($categoryPerma ?: 'category') . '/' . $page->url, '/');
            }

            return (string) ($page->url ?? '');
        }

        return '';
    }
}

if (!function_exists('seo_list_item_public_href')) {
    function seo_list_item_public_href($seo, ?string $categoryPerma = 'category'): string
    {
        if (! empty($seo->course_id)) {
            $seo->loadMissing('course');
            $slug = (string) ($seo->course->slug ?? '');

            return $slug !== '' ? route('course.details', ['course' => $slug]) : '';
        }

        $path = seo_list_item_url($seo, $categoryPerma);

        return $path !== '' ? url($path) : '';
    }
}

if (!function_exists('is_content_writer_role')) {
    function is_content_writer_role(?string $role): bool
    {
        return normalize_panel_role($role) === 'content_writer';
    }
}

if (!function_exists('is_restricted_panel_role')) {
    function is_restricted_panel_role(?string $role): bool
    {
        return in_array(normalize_panel_role($role), ['content_writer', 'accountant'], true);
    }
}

if (!function_exists('user_list_type_param')) {
    function user_list_type_param(?string $roleName): string
    {
        if (is_content_writer_role($roleName)) {
            return 'content-writer';
        }

        return $roleName ?? 'student';
    }
}

if (!function_exists('role_display_name')) {
    function role_display_name(?string $role): string
    {
        return match ($role) {
            'librarian', 'content_writer', 'content-writer' => 'Content Writer',
            'accountant' => 'Accountant',
            default => ucfirst($role ?? ''),
        };
    }
}

if (!function_exists('user_list_role_names')) {
    function user_list_role_names(string $type): array
    {
        $normalized = str_replace(['-', ' '], '_', strtolower($type));

        if (in_array($normalized, ['librarian', 'content_writer'], true)) {
            return ['content_writer', 'librarian', 'content-writer'];
        }

        return [$type];
    }
}

if (!function_exists('user_list_label')) {
    function user_list_label(string $type): string
    {
        $normalized = str_replace(['-', ' '], '_', strtolower($type));

        if (in_array($normalized, ['librarian', 'content_writer'], true)) {
            return 'Content Writers';
        }

        return ucfirst(Str::plural($type));
    }
}

if (!function_exists('admin_can_delete')) {
    function admin_can_delete(): bool
    {
        return !is_restricted_panel_role(panel_role_name());
    }
}

if (!function_exists('admin_menu_allowed')) {
    function admin_menu_allowed(string $menu): bool
    {
        $role = normalize_panel_role(panel_role_name());

        if ($role === null || $role === 'admin') {
            return true;
        }

        $contentWriterMenus = [
            'dashboard', 'courses', 'training-calendar', 'school', 'categories',
            'pages', 'faq', 'seo', 'analytics', 'clients', 'assessments', 'profile', 'logout',
        ];

        $accountantMenus = [
            'dashboard', 'courses', 'currency-rate-setup',
            'payments', 'payment-gateway', 'assessments', 'profile', 'logout',
        ];

        $instructorMenus = [
            'dashboard', 'study-materials', 'class-schedules', 'assessments', 'profile', 'history', 'logout',
        ];

        if ($role === 'instructor') {
            return in_array($menu, $instructorMenus, true);
        }
        if ($role === 'content_writer') {
            if (in_array($menu, $contentWriterMenus, true)) {
                return true;
            }

            $permissionMenus = [
                'analytics' => 'analytic-list',
            ];

            if (isset($permissionMenus[$menu])) {
                $user = panel_profile_user();

                return $user
                    && method_exists($user, 'hasPermission')
                    && $user->hasPermission($permissionMenus[$menu]);
            }

            return false;
        }

        if ($role === 'accountant') {
            return in_array($menu, $accountantMenus, true);
        }

        return true;
    }
}

if (!function_exists('audit_user_id')) {
    function audit_user_id(): ?int
    {
        if (Auth::guard('admin')->check()) {
            return Auth::guard('admin')->id();
        }

        return Auth::id();
    }
}

if (!function_exists('site_twitter_handle')) {
    function site_twitter_handle(): ?string
    {
        static $resolved = false;
        static $handle = null;

        if ($resolved) {
            return $handle ?: null;
        }

        $resolved = true;
        $configured = trim((string) env('TWITTER_SITE_HANDLE', ''));
        if ($configured !== '') {
            $handle = str_starts_with($configured, '@') ? $configured : '@' . $configured;

            return $handle;
        }

        try {
            $url = trim((string) (\App\Models\SiteSettings::value('twitter_url') ?? ''));
        } catch (\Throwable $e) {
            $url = '';
        }

        if ($url === '') {
            return null;
        }

        if (preg_match('/(?:twitter\.com|x\.com)\/(@?[\w]+)/i', $url, $matches)) {
            $handle = '@' . ltrim($matches[1], '@');

            return $handle;
        }

        $handle = str_starts_with($url, '@') ? $url : '@' . ltrim($url, '@');

        return $handle;
    }
}

if (!function_exists('panel_actor_display_name')) {
    function panel_actor_display_name(?string $name, ?string $email = null, ?string $username = null): string
    {
        $name = trim((string) $name);
        if ($name !== '') {
            return $name;
        }

        $username = trim((string) $username);
        if ($username !== '') {
            return $username;
        }

        $email = trim((string) $email);
        if ($email !== '' && str_contains($email, '@')) {
            return ucwords(str_replace(['.', '_', '-'], ' ', (string) \Illuminate\Support\Str::before($email, '@')));
        }

        return $email !== '' ? $email : '-';
    }
}

if (!function_exists('activity_action_label')) {
    function activity_action_label(?string $action): string
    {
        $action = trim((string) $action);
        $action = str_ireplace(' Logout', ' Log out', $action);

        if (preg_match('/^(Admin|Staff|User)\s+Login$/i', $action)) {
            return 'Login';
        }

        if (preg_match('/^(Admin|Staff|User)\s+Log out$/i', $action)) {
            return 'Log out';
        }

        return $action !== '' ? $action : '—';
    }
}

if (!function_exists('activity_is_profile_noise')) {
    /** Hide User Profile Loaded / Updated (and similar) for admin, instructor, and student history. */
    function activity_is_profile_noise(?string $action, ?string $item = null): bool
    {
        $hay = strtolower(trim((string) $action) . ' ' . trim((string) $item));
        if ($hay === '' || ! str_contains($hay, 'profile')) {
            return false;
        }

        return str_contains($hay, 'loaded')
            || str_contains($hay, 'updated')
            || str_contains($hay, 'opened');
    }
}

if (!function_exists('audit_user_name')) {
    function audit_user_name($model, $fallbackId = null): string
    {
        if ($model) {
            return panel_actor_display_name(
                $model->name ?? null,
                $model->email ?? null,
                $model->username ?? null
            );
        }

        if ($fallbackId) {
            $admin = \App\Models\Admin::find($fallbackId);
            if ($admin) {
                return panel_actor_display_name(
                    $admin->username ?? $admin->name ?? null,
                    $admin->email ?? null
                );
            }

            $user = \App\Models\User::find($fallbackId);
            if ($user) {
                return panel_actor_display_name($user->name ?? null, $user->email ?? null, $user->username ?? null);
            }
        }

        return 'Admin';
    }
}

if (!function_exists('image_alt')) {
    function image_alt(?string $alt, ?string $fallback = null): string
    {
        $alt = trim((string) $alt);
        if ($alt !== '') {
            return \Illuminate\Support\Str::limit($alt, 125, '');
        }

        $fallback = trim((string) $fallback);
        if ($fallback !== '') {
            return \Illuminate\Support\Str::limit($fallback, 125, '');
        }

        return 'Image';
    }
}

if (!function_exists('merge_image_alts')) {
    function merge_image_alts($existing, ?array $incoming): array
    {
        $merged = is_array($existing) ? $existing : (json_decode((string) $existing, true) ?: []);

        if (! is_array($incoming)) {
            return $merged;
        }

        foreach ($incoming as $key => $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                $merged[$key] = \Illuminate\Support\Str::limit($value, 125, '');
            } else {
                unset($merged[$key]);
            }
        }

        return $merged;
    }
}

if (!function_exists('form_image_alt_value')) {
    function form_image_alt_value($model, string $key, ?string $oldKey = null): string
    {
        if ($oldKey !== null) {
            $fromOld = old($oldKey);
            if ($fromOld !== null) {
                return trim((string) $fromOld);
            }
        }

        if (! $model) {
            return '';
        }

        $alts = $model->image_alts ?? null;
        $parsed = is_array($alts) ? $alts : (json_decode((string) $alts, true) ?: []);

        return trim((string) data_get($parsed, $key, ''));
    }
}

if (!function_exists('seo_prepare_save_data')) {
    function seo_prepare_save_data(array $data): array
    {
        ensure_seo_focus_keyword_column_exists();
        ensure_seo_thumbnail_alt_column_exists();
        ensure_seo_robots_columns_exist();

        if (! \Illuminate\Support\Facades\Schema::hasColumn('pages_s_e_o_s', 'focus_keyword')) {
            unset($data['focus_keyword']);
        }

        if (! \Illuminate\Support\Facades\Schema::hasColumn('pages_s_e_o_s', 'thumbnail_alt')) {
            unset($data['thumbnail_alt']);
        }

        if (! \Illuminate\Support\Facades\Schema::hasColumn('pages_s_e_o_s', 'robots_index')) {
            unset($data['robots_index'], $data['robots_follow']);
        } else {
            $data['robots_index'] = in_array(($data['robots_index'] ?? 'index'), ['index', 'noindex'], true)
                ? $data['robots_index']
                : 'index';
            $data['robots_follow'] = in_array(($data['robots_follow'] ?? 'follow'), ['follow', 'nofollow'], true)
                ? $data['robots_follow']
                : 'follow';
        }

        return $data;
    }
}

if (!function_exists('ensure_seo_robots_columns_exist')) {
    function ensure_seo_robots_columns_exist(): bool
    {
        static $ready = null;

        if ($ready === true) {
            return true;
        }

        if ($ready === false) {
            return false;
        }

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('pages_s_e_o_s')
                && ! \Illuminate\Support\Facades\Schema::hasColumn('pages_s_e_o_s', 'robots_index')) {
                \Illuminate\Support\Facades\Schema::table('pages_s_e_o_s', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->string('robots_index', 20)->default('index')->nullable();
                    $table->string('robots_follow', 20)->default('follow')->nullable();
                });
            }

            $ready = true;

            return true;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Could not ensure SEO robots columns: ' . $e->getMessage());
            $ready = false;

            return false;
        }
    }
}

if (!function_exists('seo_robots_content')) {
    function seo_robots_content(?\App\Models\PagesSEO $seo): string
    {
        ensure_seo_robots_columns_exist();

        $index = 'index';
        $follow = 'follow';

        if ($seo) {
            $index = in_array((string) ($seo->robots_index ?? 'index'), ['index', 'noindex'], true)
                ? (string) $seo->robots_index
                : 'index';
            $follow = in_array((string) ($seo->robots_follow ?? 'follow'), ['follow', 'nofollow'], true)
                ? (string) $seo->robots_follow
                : 'follow';
        }

        return $index . ', ' . $follow;
    }
}

if (! function_exists('seo_courses_hub_url')) {
    function seo_courses_hub_url(): string
    {
        return url('/courses');
    }
}

if (! function_exists('public_menu_href')) {
    function public_menu_href(?string $link): string
    {
        $link = trim((string) $link);
        if ($link === '' || $link === '#') {
            return $link;
        }

        $path = parse_url($link, PHP_URL_PATH);
        $path = ltrim(strtolower((string) ($path !== null && $path !== '' ? $path : $link)), '/');

        if ($path === 'home' || $path === 'index.php' || $path === 'index.html') {
            return url('/');
        }
        if ($path === 'legal' || $path === 'course/legal') {
            return url('/legal');
        }
        if ($path === 'berkeley-girls/sixth-form' || $path === 'berkeley-girls/sixth-form/') {
            return url('/sixth-form');
        }
        if ($path === 'search-courses' || $path === 'search_courses') {
            return url('/search');
        }

        $isAbsolute = str_starts_with($link, '/')
            || str_starts_with($link, '#')
            || str_starts_with($link, 'mailto:')
            || str_starts_with($link, 'tel:')
            || (bool) preg_match('~^(?:https?:)?//~i', $link);

        if (! $isAbsolute) {
            return url('/' . ltrim($link, '/'));
        }

        return $link;
    }
}

if (! function_exists('rewrite_public_hrefs')) {
    function rewrite_public_hrefs(string $html): string
    {
        $html = preg_replace('#href=(["\'])(?:\./)?legal\1#i', 'href=$1/legal$1', $html) ?? $html;
        $html = preg_replace('#href=(["\'])(?:\./)?home/?\1#i', 'href=$1/$1', $html) ?? $html;
        $html = preg_replace('#href=(["\'])(?:\./)?search[-_]courses/?\1#i', 'href=$1/search$1', $html) ?? $html;

        return preg_replace(
            '#href=(["\'])(?:\./)?berkeley-girls/sixth-form/?\1#i',
            'href=$1/sixth-form$1',
            $html
        ) ?? $html;
    }
}

if (! function_exists('seo_is_utility_path')) {
    function seo_is_utility_path(?string $path = null): bool
    {
        $path = strtolower(trim($path ?? request()->path(), '/'));
        $prefixes = [
            'cart', 'checkout', 'login', 'register', 'password', 'search',
            'search-courses', 'search_courses',
            'agenda_search', 'faculty_search', 'user', 'approval-notice',
            'thank-you-for-registering', 'page-not-found', 'installments/payment-success',
        ];
        foreach ($prefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('seo_preferred_canonical')) {
    function seo_preferred_canonical(?\Illuminate\Http\Request $request = null): string
    {
        $request = $request ?: request();
        $base = rtrim((string) config('app.url'), '/');
        $path = '/' . ltrim((string) $request->getPathInfo(), '/');
        if ($path === '/') {
            return $base . '/';
        }

        return $base . rtrim($path, '/');
    }
}

if (! function_exists('seo_document_language')) {
    /**
     * @return array{lang: string, dir: ?string}
     */
    function seo_document_language(?string $path = null): array
    {
        $path = strtolower(trim($path ?? request()->path(), '/'));
        if (preg_match('~(^|/|-)arabic(/|-|$)|(^|/)ar(/|$)|(^|/)ar-[a-z]{2}(/|$)~', $path)) {
            return ['lang' => 'ar', 'dir' => 'rtl'];
        }
        if (preg_match('~(^|/|-)chinese(/|-|$)|(^|/|-)zh(-hans|-cn|-tw)?(/|-|$)~', $path)) {
            return ['lang' => 'zh-Hans', 'dir' => null];
        }

        return ['lang' => 'en', 'dir' => null];
    }
}

if (! function_exists('seo_hreflang_alternates')) {
    /**
     * @return array<string, string>
     */
    function seo_hreflang_alternates(?string $path = null): array
    {
        static $memo = [];
        $path = trim($path ?? request()->path(), '/');
        if (array_key_exists($path, $memo)) {
            return $memo[$path];
        }

        $isCourse = str_starts_with(strtolower($path), 'course/');
        $slug = $isCourse ? substr($path, strlen('course/')) : $path;
        $root = preg_replace('/-(arabic|chinese|ar|zh-hans|zh)$/i', '', $slug) ?: $slug;
        $candidates = [
            'en' => $root,
            'ar' => $root . '-arabic',
            'zh-Hans' => $root . '-chinese',
        ];

        $links = [];
        foreach ($candidates as $hreflang => $candidate) {
            if ($candidate === '') {
                continue;
            }
            $exists = $isCourse
                ? \App\Models\Course::where('slug', $candidate)->exists()
                : \App\Models\Page::where('url', $candidate)->exists();
            if ($exists) {
                $links[$hreflang] = $isCourse
                    ? rtrim((string) config('app.url'), '/') . '/course/' . $candidate
                    : rtrim((string) config('app.url'), '/') . '/' . $candidate;
            }
        }

        if (count($links) < 2) {
            return $memo[$path] = [];
        }

        if (isset($links['en'])) {
            $links['x-default'] = $links['en'];
        }

        return $memo[$path] = $links;
    }
}

if (!function_exists('ensure_seo_focus_keyword_column_exists')) {
    function ensure_seo_focus_keyword_column_exists(): bool
    {
        static $ready = null;

        if ($ready === true) {
            return true;
        }

        if ($ready === false) {
            return false;
        }

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('pages_s_e_o_s')
                && ! \Illuminate\Support\Facades\Schema::hasColumn('pages_s_e_o_s', 'focus_keyword')) {
                \Illuminate\Support\Facades\Schema::table('pages_s_e_o_s', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->string('focus_keyword', 80)->nullable()->after('meta_description');
                });
            }

            $ready = true;

            return true;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Could not ensure focus_keyword column: ' . $e->getMessage());
            $ready = false;

            return false;
        }
    }
}

if (!function_exists('ensure_seo_thumbnail_alt_column_exists')) {
    function ensure_seo_thumbnail_alt_column_exists(): bool
    {
        static $ready = null;

        if ($ready === true) {
            return true;
        }

        if ($ready === false) {
            return false;
        }

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('pages_s_e_o_s')
                && ! \Illuminate\Support\Facades\Schema::hasColumn('pages_s_e_o_s', 'thumbnail_alt')) {
                \Illuminate\Support\Facades\Schema::table('pages_s_e_o_s', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->string('thumbnail_alt', 255)->nullable();
                });
            }

            $ready = true;

            return true;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Could not ensure thumbnail_alt column: ' . $e->getMessage());
            $ready = false;

            return false;
        }
    }
}

if (!function_exists('ensure_image_alt_columns_exist')) {
    function ensure_image_alt_columns_exist(): bool
    {
        static $ready = null;

        if ($ready === true) {
            return true;
        }

        if ($ready === false) {
            return false;
        }

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('courses')
                && ! \Illuminate\Support\Facades\Schema::hasColumn('courses', 'image_alts')) {
                \Illuminate\Support\Facades\Schema::table('courses', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->json('image_alts')->nullable();
                });
            }

            if (\Illuminate\Support\Facades\Schema::hasTable('course_dynamic_labels')
                && ! \Illuminate\Support\Facades\Schema::hasColumn('course_dynamic_labels', 'image_alts')) {
                \Illuminate\Support\Facades\Schema::table('course_dynamic_labels', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->json('image_alts')->nullable();
                });
            }

            $ready = true;

            return true;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Could not ensure image_alts columns: ' . $e->getMessage());
            $ready = false;

            return false;
        }
    }
}

if (!function_exists('request_image_alts')) {
    function request_image_alts(\Illuminate\Http\Request $request, string $dotKey): ?array
    {
        $value = $request->input($dotKey);

        if (is_array($value)) {
            return $value;
        }

        if ($dotKey === 'label.image_alts') {
            $label = $request->input('label');

            if (is_array($label) && is_array($label['image_alts'] ?? null)) {
                return $label['image_alts'];
            }
        }

        if ($dotKey === 'image_alts' && is_array($request->input('image_alts'))) {
            return $request->input('image_alts');
        }

        return null;
    }
}

if (!function_exists('persist_model_image_alts')) {
    function persist_model_image_alts($model, ?array $incoming): bool
    {
        if (! $model || ! is_array($incoming)) {
            return false;
        }

        $model->setAttribute('image_alts', merge_image_alts($model->image_alts ?? null, $incoming));

        try {
            return $model->save();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning(
                'Failed to save image_alts on ' . $model->getTable() . ': ' . $e->getMessage()
            );

            return false;
        }
    }
}

if (!function_exists('label_request_has_content')) {
    function label_request_has_content(?array $labelData): bool
    {
        if (! is_array($labelData) || $labelData === []) {
            return false;
        }

        foreach ($labelData as $key => $value) {
            if ($key === 'image_alts' && is_array($value)) {
                foreach ($value as $alt) {
                    if (trim((string) $alt) !== '') {
                        return true;
                    }
                }

                continue;
            }

            if (is_array($value)) {
                if ($value !== []) {
                    return true;
                }

                continue;
            }

            if ($value !== null && $value !== '') {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('pages_status_enabled')) {
    function pages_status_enabled(): bool
    {
        static $enabled = null;

        if ($enabled === null) {
            $enabled = \Illuminate\Support\Facades\Schema::hasColumn('pages', 'status');
        }

        return $enabled;
    }
}

if (!function_exists('normalize_page_status')) {
    function normalize_page_status(mixed $value): int
    {
        if (in_array($value, ['disable', 'disabled', '0', 0, false], true)) {
            return 0;
        }

        return 1;
    }
}

if (!function_exists('save_uploaded_profile_image')) {
    function save_uploaded_profile_image(
        \Illuminate\Http\Request $request,
        string $field = 'image',
        ?string $currentPath = null
    ): ?string {
        $alternateFields = $field === 'image'
            ? ['local_file_input']
            : [];

        foreach (array_merge([$field], $alternateFields) as $uploadField) {
            if ($request->hasFile($uploadField)) {
                $file = $request->file($uploadField);
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $fileName = \Illuminate\Support\Str::slug($originalName) . '-' . time() . '.' . $extension;
                $destinationPath = public_path('images/profiles/');

                if (! is_dir($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                public_upload_move($file, $destinationPath, $fileName);

                return 'images/profiles/' . $fileName;
            }
        }

        if ($request->boolean('remove_image')) {
            return '';
        }

        if ($request->filled('image_path')) {
            $rawPath = trim(str_replace('\\', '/', (string) $request->input('image_path')));
            $normalized = normalize_profile_image_path($rawPath);

            if ($normalized === null) {
                return $currentPath;
            }

            $hasUpload = collect(array_merge([$field], $alternateFields, ['image', 'local_file_input']))
                ->contains(fn (string $uploadField) => $request->hasFile($uploadField));

            if (
                ! $hasUpload
                && ! str_contains($rawPath, '/')
                && $currentPath !== null
                && basename($currentPath) !== basename($rawPath)
            ) {
                return $currentPath;
            }

            return $normalized;
        }

        $submittedCurrent = $request->input('current_image');
        if (is_string($submittedCurrent) && trim($submittedCurrent) !== '') {
            return normalize_profile_image_path($submittedCurrent) ?? $currentPath;
        }

        return $currentPath;
    }
}

if (!function_exists('set_profile_image_column')) {
    function set_profile_image_column($model, string $column, mixed $value): void
    {
        if (! $model) {
            return;
        }

        if (in_array($model->getTable(), ['users', 'admins'], true)) {
            $model->setAttribute($column, $value);

            return;
        }

        assign_column_if_exists($model, $column, $value);
    }
}

if (!function_exists('apply_profile_image_from_request')) {
    function apply_profile_image_from_request(
        $model,
        \Illuminate\Http\Request $request,
        string $field = 'image',
        string $column = 'image'
    ): void {
        if (! $model) {
            return;
        }

        $currentPath = normalize_profile_image_path($model->{$column} ?? null);
        $submittedCurrent = $request->input('current_image');

        if (is_string($submittedCurrent) && trim($submittedCurrent) !== '') {
            $currentPath = normalize_profile_image_path($submittedCurrent) ?? $currentPath;
        }

        $newPath = save_uploaded_profile_image($request, $field, $currentPath);

        if ($newPath === null) {
            if ($currentPath !== null) {
                set_profile_image_column($model, $column, $currentPath);
            }

            return;
        }

        if ($newPath === '') {
            set_profile_image_column($model, $column, null);

            return;
        }

        set_profile_image_column($model, $column, $newPath);
    }
}

if (!function_exists('assign_column_if_exists')) {
    function assign_column_if_exists($model, string $column, mixed $value): void
    {
        if (! $model) {
            return;
        }

        $table = $model->getTable();

        if (\Illuminate\Support\Facades\Schema::hasColumn($table, $column)) {
            $model->setAttribute($column, $value);
        }
    }
}

if (!function_exists('stored_image_alt')) {
    function stored_image_alt($model, string $key, ?string $fallback = null): string
    {
        if (! $model) {
            return image_alt(null, $fallback);
        }

        $alts = $model->image_alts ?? null;

        return image_alt(data_get(is_array($alts) ? $alts : (json_decode((string) $alts, true) ?: []), $key), $fallback);
    }
}

if (!function_exists('course_image_alt')) {
    function course_image_alt($course, string $imageKey, ?string $sectionFallback = null): string
    {
        $fallback = $sectionFallback ?? $course?->title ?? 'Course image';

        if ($imageKey === 'overview_img') {
            return stored_image_alt($course, 'overview_img', $fallback);
        }

        return stored_image_alt($course?->dynamicLabel, $imageKey, $fallback);
    }
}

if (!function_exists('activity_audience_for_role')) {
    function activity_audience_for_role(?string $role): string
    {
        $normalized = normalize_panel_role($role);

        if (in_array($normalized, ['admin', 'instructor', 'content_writer', 'accountant'], true)) {
            return 'staff';
        }

        return 'student';
    }
}

if (!function_exists('course_instructor_ids')) {
    function course_instructor_ids($course): array
    {
        if (! $course) {
            return [];
        }

        $raw = method_exists($course, 'getRawOriginal')
            ? ($course->getRawOriginal('instructor_id') ?? $course->instructor_id)
            : ($course->instructor_id ?? null);

        if (is_array($raw)) {
            return array_values(array_filter(array_map('intval', $raw)));
        }

        if (is_string($raw)) {
            $trimmed = trim($raw);

            if ($trimmed !== '' && str_starts_with($trimmed, '[')) {
                $decoded = json_decode($trimmed, true);
                if (is_array($decoded)) {
                    return array_values(array_filter(array_map('intval', $decoded)));
                }
            }

            return array_values(array_filter(array_map('intval', explode(',', $raw))));
        }

        if (is_numeric($raw)) {
            $id = (int) $raw;

            return $id > 0 ? [$id] : [];
        }

        return [];
    }
}

if (!function_exists('course_details_url')) {
    function course_details_url($course): ?string
    {
        if (! $course) {
            return null;
        }

        $key = trim((string) ($course->slug ?? ''));
        if ($key === '') {
            $key = (string) ($course->id ?? '');
        }

        return $key !== '' ? route('course.details', ['course' => $key]) : null;
    }
}

if (!function_exists('page_public_url')) {
    function page_public_url($page): ?string
    {
        if (! $page) {
            return null;
        }

        if (method_exists($page, 'loadMissing')) {
            $page->loadMissing('parent');
        }

        $categoryPerma = \App\Models\SiteSettings::value('category_perma') ?? 'category';

        if (! empty($page->parent_id) && $page->parent) {
            $path = trim((string) ($page->parent->url . '/' . $page->url), '/');
        } elseif (! empty($page->category_id)) {
            $path = trim((string) (($categoryPerma ?: 'category') . '/' . $page->url), '/');
        } else {
            $path = trim((string) ($page->url ?? ''), '/');
        }

        return $path !== '' ? url($path) : null;
    }
}

if (!function_exists('courses_for_instructor')) {
    function courses_for_instructor(int $instructorId): \Illuminate\Support\Collection
    {
        return \App\Models\Course::query()
            ->where('status', 1)
            ->whereNotNull('instructor_id')
            ->where('instructor_id', '!=', '')
            ->orderBy('title')
            ->get(['id', 'title', 'slug', 'description', 'instructor_id', 'status'])
            ->filter(fn ($course) => in_array($instructorId, course_instructor_ids($course), true))
            ->values();
    }
}

if (!function_exists('activity_audience_for_user')) {
    function activity_audience_for_user(?\App\Models\User $user): string
    {
        if (! $user) {
            return 'staff';
        }

        // Prefer staff when the user has any staff role (e.g. instructor + student).
        $roleNames = $user->roles()->pluck('name')->all();
        foreach ($roleNames as $roleName) {
            if (activity_audience_for_role($roleName) === 'staff') {
                return 'staff';
            }
        }

        return activity_audience_for_role($roleNames[0] ?? null);
    }
}

if (!function_exists('record_user_activity')) {
    function record_user_activity(
        string $action,
        ?string $item = null,
        ?string $url = null,
        ?string $audience = null,
        ?int $userId = null,
        ?int $adminId = null,
        ?\Illuminate\Http\Request $request = null,
        ?string $sessionId = null
    ): void {
        $request = $request ?? request();

        if ($sessionId === null && $request?->hasSession()) {
            $sessionId = $request->session()->getId();
        }

        app(\App\Services\UserActivityLogService::class)->log(
            $action,
            $audience ?? 'staff',
            $item,
            $url,
            $userId,
            $adminId,
            $request?->ip(),
            $sessionId,
        );
    }
}

if (!function_exists('record_panel_activity')) {
    function record_panel_activity(
        string $action,
        ?string $item = null,
        ?string $url = null,
        ?\Illuminate\Http\Request $request = null
    ): void {
        $request = $request ?? request();

        // Prevent LogAdminPanelActivity middleware from double-writing the same request.
        if ($request) {
            $request->attributes->set('panel_activity_recorded', true);
        }

        if (\Illuminate\Support\Facades\Auth::guard('admin')->check()) {
            record_user_activity(
                $action,
                $item,
                $url,
                'staff',
                null,
                \Illuminate\Support\Facades\Auth::guard('admin')->id(),
                $request
            );

            return;
        }

        if (\Illuminate\Support\Facades\Auth::check()) {
            record_user_activity(
                $action,
                $item,
                $url,
                activity_audience_for_user(\Illuminate\Support\Facades\Auth::user()),
                \Illuminate\Support\Facades\Auth::id(),
                null,
                $request
            );
        }
    }
}

if (!function_exists('ensure_user_update_email_template')) {
    function ensure_user_update_email_template(): ?\App\Models\Email
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('emails')) {
            return null;
        }

        $template = \App\Models\Email::query()
            ->whereIn('name', ['user-update', 'User Update', 'profile-update', 'user-profile-update'])
            ->orderByRaw("CASE name WHEN 'user-update' THEN 0 ELSE 1 END")
            ->first();

        if ($template) {
            return $template;
        }

        return \App\Models\Email::query()->firstOrCreate(
            ['name' => 'user-update'],
            [
                'subject' => 'Your profile was updated | Berkeley School of Business',
                'cc' => null,
                'bcc' => null,
                'body' => 'Dear {name},<br><br>Your profile has been updated successfully.<br><br>Email on file: {email}<br><br>If you did not make this change, please contact support.',
            ]
        );
    }
}

if (!function_exists('send_user_profile_update_email')) {
    /**
     * Send the User Update email template after a student/instructor/staff user saves their profile.
     * @return string|null Error message when sending fails; null on success or when skipped.
     */
    function send_user_profile_update_email($user): ?string
    {
        if (! $user || empty($user->email)) {
            return 'Profile email skipped: user has no email address.';
        }

        $emailTemplate = ensure_user_update_email_template();
        if (! $emailTemplate) {
            return 'Profile email skipped: User Update template is missing.';
        }

        try {
            $placeholders = ['{name}', '{email}', '{password}'];
            $values = [$user->name ?? '', $user->email ?? '', ''];
            $emailBody = str_replace($placeholders, $values, (string) $emailTemplate->body);

            $cc = ! empty($emailTemplate->cc) ? array_filter(array_map('trim', explode(',', $emailTemplate->cc))) : [];
            $bcc = ! empty($emailTemplate->bcc) ? array_filter(array_map('trim', explode(',', $emailTemplate->bcc))) : [];

            \Illuminate\Support\Facades\Mail::to($user->email)->cc($cc)->bcc($bcc)
                ->send(new \App\Mail\UserMail($user, $emailTemplate->subject, $emailBody));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Profile update email failed: ' . $e->getMessage());

            return 'Email sending failed: ' . $e->getMessage();
        }

        return null;
    }
}

if (!function_exists('touch_content_audit')) {
    function touch_content_audit(\Illuminate\Database\Eloquent\Model $model): void
    {
        $actorId = audit_user_id();

        if (! $actorId) {
            return;
        }

        $table = $model->getTable();

        if (! \Illuminate\Support\Facades\Schema::hasColumn($table, 'updated_by')) {
            return;
        }

        \Illuminate\Support\Facades\DB::table($table)
            ->where('id', $model->getKey())
            ->update([
                'updated_by' => $actorId,
                'updated_at' => now(),
            ]);
    }
}

if (!function_exists('log_panel_course_update')) {
    function log_panel_course_update(\App\Models\Course $course): void
    {
        static $loggedCourseKeys = [];

        $module = trim((string) request()->input('course_module', ''));
        $logKey = $course->id . ':' . ($module !== '' ? $module : 'save');

        if (isset($loggedCourseKeys[$logKey])) {
            return;
        }

        $loggedCourseKeys[$logKey] = true;

        $item = $course->title ?: 'Course #' . $course->id;
        if ($module !== '') {
            $sectionLabel = ucwords(str_replace('_', ' ', preg_replace('/_section$/', '', $module)));
            $item .= ' — ' . $sectionLabel;
        }

        record_panel_activity(
            'Course Updated',
            $item,
            route('course.edit', $course->id)
        );
    }
}

if (!function_exists('log_panel_seo_activity')) {
    function log_panel_seo_activity(\App\Models\PagesSEO $seo, string $action): void
    {
        $seo->loadMissing(['page', 'course']);

        $type = $seo->course_id ? 'Course' : 'Page';
        $name = $seo->course_id
            ? ($seo->course?->title ?: 'Course #' . $seo->course_id)
            : ($seo->page?->page_name ?: 'Page #' . $seo->page_id);

        $item = $type . ': ' . $name;
        $seoTitle = trim((string) $seo->title);
        if ($seoTitle !== '') {
            $item .= ' — ' . $seoTitle;
        }

        $url = $seo->id
            ? route('courses-pages-seo.edit', $seo->id)
            : null;

        record_panel_activity($action, $item, $url);
    }
}

if (!function_exists('cart_item_count')) {
    function cart_item_count(): int
    {
        if (!Auth::check()) {
            return 0;
        }

        $query = \App\Models\CartItem::where('user_id', Auth::id())
            ->where(function ($q) {
                $q->whereHas('courseFee');
                if (\Illuminate\Support\Facades\Schema::hasColumn('cart_items', 'page_fee_package_id')) {
                    $q->orWhereHas('pageFeePackage');
                }
            });

        return (int) $query->sum('quantity');
    }
}

if (!function_exists('currency_rates_to_aed')) {
    function currency_rates_to_aed(): array
    {
        static $rates = null;

        if ($rates === null) {
            $rates = ['AED' => 1.0];

            try {
                $rows = \App\Models\CurrencyRate::query()
                    ->join('currencies', 'currencies.id', '=', 'currency_rates.currency_id')
                    ->pluck('currency_rates.rate_to_aed', 'currencies.code');

                foreach ($rows as $code => $rate) {
                    $rates[strtoupper((string) $code)] = (float) $rate;
                }
            } catch (\Throwable $e) {
                // Keep AED fallback when rates are unavailable.
            }
        }

        return $rates;
    }
}

if (!function_exists('convert_to_aed')) {
    function convert_to_aed($amount, ?string $currency = 'AED'): float
    {
        $amount = (float) $amount;
        $currency = strtoupper(trim($currency ?: 'AED'));

        if ($currency === 'AED') {
            return round($amount, 2);
        }

        $rate = currency_rates_to_aed()[$currency] ?? null;

        if (!$rate) {
            return round($amount, 2);
        }

        return round($amount * $rate, 2);
    }
}

if (!function_exists('convert_from_aed')) {
    function convert_from_aed(float $aedAmount, ?string $currency = 'AED'): float
    {
        $currency = strtoupper(trim($currency ?: 'AED'));

        if ($currency === 'AED') {
            return round($aedAmount, 2);
        }

        $rate = currency_rates_to_aed()[$currency] ?? null;

        if (!$rate) {
            return round($aedAmount, 2);
        }

        return round($aedAmount / $rate, 2);
    }
}

if (!function_exists('payment_display_currency')) {
    function payment_display_currency($payment): string
    {
        $currency = trim((string) ($payment->currency ?? ''));

        // Some historical records have `payments.currency` stored as empty string.
        // Treat it as missing so we can fall back to the package currency.
        if ($currency === '') {
            $currency = trim((string) (optional($payment->courseFee)->currency ?? ''));
        }

        return package_currency_code($currency !== '' ? $currency : 'AED');
    }
}

if (!function_exists('payment_display_amount_from_aed')) {
    function payment_display_amount_from_aed($payment, float $aedAmount): float
    {
        $currency = payment_display_currency($payment);

        if ($currency === 'AED') {
            return round($aedAmount, 2);
        }

        $settlingTotal = (float) ($payment->price ?? 0);

        if ($settlingTotal > 0) {
            $fullDisplay = convert_from_aed($settlingTotal, $currency);

            return round(($aedAmount / $settlingTotal) * $fullDisplay, 2);
        }

        return convert_from_aed($aedAmount, $currency);
    }
}

if (!function_exists('payment_aed_from_display_amount')) {
    function payment_aed_from_display_amount($payment, float $displayAmount): float
    {
        $currency = payment_display_currency($payment);

        if ($currency === 'AED') {
            return round($displayAmount, 2);
        }

        $settlingTotal = (float) ($payment->price ?? 0);
        $packagePrice = (float) (optional($payment->courseFee)->price ?? 0);

        if ($settlingTotal > 0 && $packagePrice > 0) {
            return round(($displayAmount / $packagePrice) * $settlingTotal, 2);
        }

        return convert_to_aed($displayAmount, $currency);
    }
}

if (!function_exists('payment_bracket_aed_from_display')) {
    function payment_bracket_aed_from_display($payment, float $displayAmount): float
    {
        $currency = payment_display_currency($payment);

        if ($currency === 'AED') {
            return round($displayAmount, 2);
        }

        return convert_to_aed($displayAmount, $currency);
    }
}

if (!function_exists('format_payment_aed_amount')) {
    function format_payment_aed_amount($payment, float $aedAmount): string
    {
        $currency = payment_display_currency($payment);
        $amount = payment_display_amount_from_aed($payment, $aedAmount);

        return $currency . ' ' . number_format($amount, 2);
    }
}

if (!function_exists('format_payment_amount_admin')) {
    function format_payment_amount_admin($payment): string
    {
        $info = format_payment_amount($payment);

        if ($info['currency'] === 'AED') {
            return e($info['display']);
        }

        return e('AED ' . number_format($info['settling_aed'], 2))
            . ' <span class="text-muted">(' . e($info['display']) . ')</span>';
    }
}

if (!function_exists('format_payment_aed_amount_admin')) {
    function format_payment_aed_amount_admin($payment, float $aedAmount): string
    {
        $currency = payment_display_currency($payment);
        $displayAmount = payment_display_amount_from_aed($payment, $aedAmount);
        $aedDisplay = 'AED ' . number_format($aedAmount, 2);

        if ($currency === 'AED') {
            return e($aedDisplay);
        }

        return e($aedDisplay)
            . ' <span class="text-muted">(' . e($currency . ' ' . number_format($displayAmount, 2)) . ')</span>';
    }
}

if (!function_exists('format_aed_price')) {
    function format_aed_price($amount, ?string $currency = 'AED'): string
    {
        return 'AED ' . number_format(convert_to_aed($amount, $currency), 2);
    }
}

if (!function_exists('package_currency_code')) {
    function package_currency_code(?string $currency): string
    {
        return strtoupper(trim($currency ?: 'AED'));
    }
}

if (!function_exists('package_settling_amount')) {
    function package_settling_amount($amount, ?string $currency): float
    {
        return convert_to_aed($amount, package_currency_code($currency));
    }
}

if (!function_exists('format_package_price')) {
    function format_package_price($amount, ?string $currency, int $quantity = 1): array
    {
        $currency = package_currency_code($currency);
        $amount = (float) $amount * $quantity;

        if ($currency === 'AED') {
            return [
                'display' => 'AED ' . number_format($amount, 2),
                'settling_aed' => round($amount, 2),
                'currency' => 'AED',
                'show_settling_note' => false,
            ];
        }

        $settling = convert_to_aed($amount, $currency);
        $display = $currency . ' ' . number_format($amount, 2);

        return [
            'display' => $display,
            'settling_aed' => $settling,
            'currency' => $currency,
            'show_settling_note' => false,
            'admin_display' => $display . ' <span class="text-muted">(AED ' . number_format($settling, 2) . ')</span>',
        ];
    }
}

if (!function_exists('payment_invoice_status')) {
    function payment_invoice_status($payment): string
    {
        if (! $payment) {
            return 'Pending';
        }

        $payment->loadMissing('installments');
        $totalPaid = (float) $payment->installments->sum('paid_amount');
        $price = (float) ($payment->price ?? 0);

        if ($price > 0 && $totalPaid >= $price) {
            return 'Paid';
        }

        if ($totalPaid > 0) {
            return 'Partial';
        }

        return 'Pending';
    }
}

if (!function_exists('format_payment_amount')) {
    function format_payment_amount($payment): array
    {
        $currency = payment_display_currency($payment);
        $settlingAed = (float) ($payment->price ?? 0);
        $displayAmount = payment_display_amount_from_aed($payment, $settlingAed);

        return [
            'display' => $currency . ' ' . number_format($displayAmount, 2),
            'settling_aed' => $settlingAed,
            'currency' => $currency,
            'show_settling_note' => false,
        ];
    }
}

if (!function_exists('normalize_payment_email_body')) {
    function normalize_payment_email_body(string $body, ?string $paidDisplay = null): string
    {
        if ($paidDisplay !== null && $paidDisplay !== '') {
            $replaced = preg_replace(
                '/Amount Paid:\s*(?:(?:AED|USD|GBP|EUR)\s+){0,2}(?:' . preg_quote($paidDisplay, '/') . '|[\d,]+\.?\d*)/i',
                'Amount Paid: ' . $paidDisplay,
                $body,
                1
            );

            if (is_string($replaced)) {
                $body = $replaced;
            }
        }

        $stripped = preg_replace(
            '/Amount Paid:\s*AED\s+(?=(USD|GBP|AED|EUR)\s)/i',
            'Amount Paid: ',
            $body
        );

        return is_string($stripped) ? $stripped : $body;
    }
}

if (!function_exists('analytics_hidden_url')) {
    function analytics_hidden_url(?string $url): bool
    {
        $url = trim((string) $url);
        if ($url === '') {
            return true;
        }
        $lower = strtolower($url);
        if (
            str_contains($lower, 'hostingersite.com')
            || str_contains($lower, 'hostingsite.com')
            || str_contains($lower, 'lscwp_ctrl')
            || str_contains($lower, 'preview-')
        ) {
            return true;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            $path = $url;
        }
        $path = '/' . ltrim(strtolower($path), '/');
        $path = rtrim($path, '/') ?: '/';

        $exact = ['/user-behavior', '/analytics/ping', '/login/captcha'];
        if (in_array($path, $exact, true)) {
            return true;
        }

        $prefixes = [
            '/admin', '/api', '/user', '/login', '/register', '/logout',
            '/password', '/email', '/sanctum', '/broadcasting', '/livewire',
            '/captcha', '/analytics', '/skin', '/room/webhook', '/verify-email',
        ];
        foreach ($prefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('analytics_time_spent_label')) {
    function analytics_time_spent_label($row): string
    {
        $seconds = (int) ($row->duration_seconds ?? 0);
        if ($seconds <= 0 && ! empty($row->created_at) && ! empty($row->updated_at)) {
            try {
                $from = $row->created_at instanceof \Carbon\Carbon ? $row->created_at : \Carbon\Carbon::parse($row->created_at);
                $to = $row->updated_at instanceof \Carbon\Carbon ? $row->updated_at : \Carbon\Carbon::parse($row->updated_at);
                $seconds = max(0, $from->diffInSeconds($to));
            } catch (\Throwable $e) {
                $seconds = 0;
            }
        }
        $seconds = min($seconds, 8 * 3600);
        if ($seconds < 5) {
            return '< 5s';
        }
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;
        if ($hours > 0) {
            return $hours . 'h ' . $minutes . 'm';
        }
        if ($minutes > 0) {
            return $minutes . 'm ' . $secs . 's';
        }

        return $secs . 's';
    }
}

if (!function_exists('analytics_channel')) {
    function analytics_channel(?string $referrer, ?string $url = null): string
    {
        if (empty($referrer)) {
            return 'Direct';
        }

        $ref = strtolower($referrer);
        $host = parse_url($ref, PHP_URL_HOST) ?? '';
        $appHost = strtolower(parse_url(config('app.url'), PHP_URL_HOST) ?? '');

        if ($host === '' || ($appHost && str_contains($host, $appHost))) {
            return 'Direct';
        }

        if (preg_match('/google\.|bing\.|yahoo\.|duckduckgo\.|baidu\./', $host)) {
            return 'Organic Search';
        }

        if (preg_match('/facebook\.|fb\.|twitter\.|t\.co|instagram\.|linkedin\.|tiktok\.|youtube\.|pinterest\./', $host)) {
            return 'Organic Social';
        }

        return 'Referral';
    }
}

if (!function_exists('visitor_client_ip')) {
    function visitor_client_ip($request = null): ?string
    {
        $request = $request ?? request();
        if (! $request) {
            return null;
        }

        foreach (['CF-Connecting-IP', 'True-Client-IP', 'X-Real-IP'] as $header) {
            $ip = trim((string) $request->header($header));
            if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        $ip = (string) $request->ip();

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
    }
}

if (!function_exists('visitor_country_header')) {
    function visitor_country_header($request = null): ?string
    {
        $request = $request ?? request();
        if (! $request) {
            return null;
        }

        $code = strtoupper(trim((string) ($request->header('CF-IPCountry') ?: $request->header('X-Country-Code') ?: '')));
        if ($code === '' || in_array($code, ['XX', 'T1', 'ZZ'], true)) {
            return null;
        }

        return preg_match('/^[A-Z]{2}$/', $code) ? $code : null;
    }
}

if (!function_exists('getUserLocation')) {
    function getUserLocation($ip = null)
    {
        $ip = $ip ?? visitor_client_ip();

        if (! $ip || in_array($ip, ['127.0.0.1', '::1'], true)) {
            $header = visitor_country_header();
            return $header ? ['country' => $header, 'city' => null, 'region' => null, 'postal' => null, 'loc' => null] : ['error' => 'Local IP'];
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            $header = visitor_country_header();
            return $header ? ['country' => $header, 'city' => null, 'region' => null, 'postal' => null, 'loc' => null] : ['error' => 'Private IP'];
        }

        $header = visitor_country_header();
        if ($header) {
            return ['country' => $header, 'city' => null, 'region' => null, 'postal' => null, 'loc' => null];
        }

        $mmdb = storage_path('app/geoip.mmdb');
        if (is_file($mmdb) && class_exists(\Torann\GeoIP\Facades\GeoIP::class)) {
            try {
                $loc = \Torann\GeoIP\Facades\GeoIP::getLocation($ip);
                $isDefault = is_object($loc) ? ! empty($loc->default) : ! empty($loc['default']);
                $iso = is_object($loc) ? ($loc->iso_code ?? null) : ($loc['iso_code'] ?? null);
                if (! $isDefault && $iso) {
                    return [
                        'country' => $iso,
                        'city' => is_object($loc) ? ($loc->city ?? null) : ($loc['city'] ?? null),
                        'region' => is_object($loc) ? ($loc->state_name ?? $loc->state ?? null) : ($loc['state_name'] ?? null),
                        'postal' => is_object($loc) ? ($loc->postal_code ?? null) : ($loc['postal_code'] ?? null),
                        'loc' => null,
                    ];
                }
            } catch (\Throwable $e) {
                // fall through to HTTP providers
            }
        }

        $providers = [];
        $token = env('IPINFO_TOKEN');
        if ($token) {
            $providers[] = "https://ipinfo.io/{$ip}?token={$token}";
        }
        $providers[] = "http://ip-api.com/json/{$ip}?fields=status,country,countryCode,regionName,city,zip,lat,lon";
        $providers[] = "https://ipwho.is/{$ip}";

        foreach ($providers as $url) {
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(2)->get($url);
                if (! $response->successful()) {
                    continue;
                }
                $data = $response->json();
                if (! is_array($data)) {
                    continue;
                }
                if (($data['status'] ?? null) === 'fail' || ($data['success'] ?? true) === false) {
                    continue;
                }

                $code = $data['countryCode'] ?? $data['country_code'] ?? $data['country'] ?? null;
                if (is_string($code) && strlen($code) > 2 && isset($data['countryCode'])) {
                    $code = $data['countryCode'];
                }
                if (empty($code)) {
                    continue;
                }

                return [
                    'country' => $code,
                    'city' => $data['city'] ?? null,
                    'region' => $data['regionName'] ?? $data['region'] ?? null,
                    'postal' => $data['zip'] ?? $data['postal'] ?? null,
                    'loc' => isset($data['lat'], $data['lon']) ? ($data['lat'] . ',' . $data['lon']) : ($data['loc'] ?? null),
                ];
            } catch (\Throwable $e) {
                continue;
            }
        }

        return ['error' => 'Failed to get location.'];
    }
}

if (!function_exists('resolve_ip_location')) {
    function resolve_ip_location(?string $ip): array
    {
        $empty = [
            'country' => null,
            'city' => null,
            'region' => null,
            'postal' => null,
            'location' => null,
        ];

        $header = visitor_country_header();
        if ($header) {
            $name = \App\Models\Country::where('iso_code', $header)->value('name') ?: $header;
            return [
                'country' => $name,
                'city' => null,
                'region' => null,
                'postal' => null,
                'location' => null,
            ];
        }

        if (! $ip) {
            return $empty;
        }

        if (in_array($ip, ['127.0.0.1', '::1'], true)) {
            return $empty;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return $empty;
        }

        $cached = \Illuminate\Support\Facades\Cache::get('ip_location:' . $ip);
        if (is_array($cached) && ! empty($cached['country'])) {
            return $cached;
        }

        $data = getUserLocation($ip);

        if (empty($data) || isset($data['error'])) {
            \Illuminate\Support\Facades\Cache::put('ip_location_miss:' . $ip, 1, now()->addMinutes(30));
            return $empty;
        }

        $countryCode = $data['country'] ?? null;
        $countryName = $countryCode;

        if ($countryCode && strlen((string) $countryCode) === 2) {
            $name = \App\Models\Country::where('iso_code', $countryCode)->value('name');
            if ($name) {
                $countryName = $name;
            }
        }

        $resolved = [
            'country' => $countryName ?: $countryCode,
            'city' => $data['city'] ?? null,
            'region' => $data['region'] ?? null,
            'postal' => $data['postal'] ?? null,
            'location' => $data['loc'] ?? null,
        ];

        if (! empty($resolved['country'])) {
            \Illuminate\Support\Facades\Cache::put('ip_location:' . $ip, $resolved, now()->addDays(30));
        }

        return $resolved;
    }
}

if (!function_exists('invoice_footer_settings')) {
    function invoice_footer_settings(): array
    {
        $defaults = [
            'usa' => '<strong>USA &amp; Canada:</strong> 2001 Addison Street, Suite 300, Berkeley, CA, 94704. &nbsp; | &nbsp; <strong>T:</strong> +1 (407) 371 9886',
            'uk' => '<strong>UK &amp; Europe:</strong> 124 City Road, London EC1V 2NX, United Kingdom. &nbsp; | &nbsp; <strong>T:</strong> +44 7 306 279 111',
            'middle_east' => '<strong>Middle East:</strong> Floor 25, Sheikh Rashid Tower, Dubai World Trade Centre, Dubai, UAE. &nbsp; | &nbsp; <strong>T:</strong> +971 585 55 56 57',
            'email' => 'finance@eduberkeley.com',
            'website' => 'www.eduberkeley.com',
            'presence' => 'USA &nbsp; | &nbsp; Canada &nbsp; | &nbsp; UK &nbsp; | &nbsp; UAE &nbsp; | &nbsp; KSA &nbsp; | &nbsp; China &nbsp; | &nbsp; Africa',
        ];

        try {
            $row = \Illuminate\Support\Facades\DB::table('site_settings')->first();
        } catch (\Throwable $e) {
            $row = null;
        }

        if (! $row) {
            return $defaults;
        }

        $field = static function (string $column, string $defaultKey) use ($row, $defaults) {
            if (! is_object($row) || ! property_exists($row, $column)) {
                return $defaults[$defaultKey];
            }

            $value = $row->{$column};

            return ($value !== null && $value !== '') ? $value : $defaults[$defaultKey];
        };

        return [
            'usa' => $field('invoice_footer_usa', 'usa'),
            'uk' => $field('invoice_footer_uk', 'uk'),
            'middle_east' => $field('invoice_footer_middle_east', 'middle_east'),
            'email' => $field('invoice_footer_email', 'email'),
            'website' => $field('invoice_footer_website', 'website'),
            'presence' => $field('invoice_footer_presence', 'presence'),
        ];
    }
}

if (!function_exists('invoice_header_email')) {
    function invoice_header_email(): string
    {
        $default = 'training@eduberkeley.com';

        try {
            $row = \Illuminate\Support\Facades\DB::table('site_settings')->first();
        } catch (\Throwable $e) {
            $row = null;
        }

        if ($row && !empty($row->invoice_header_email)) {
            return $row->invoice_header_email;
        }

        return $default;
    }
}

if (!function_exists('is_valid_timezone')) {
    function is_valid_timezone(?string $tz): bool
    {
        $tz = trim((string) $tz);

        return $tz !== '' && in_array($tz, \DateTimeZone::listIdentifiers(), true);
    }
}

if (!function_exists('viewer_timezone')) {
    /** Timezone for the person looking at the page (browser cookie, else GeoIP, else site default). */
    function viewer_timezone($request = null): string
    {
        $request = $request ?? request();
        $fallback = (string) config('app.timezone', 'Asia/Dubai');

        $cookie = $request ? trim((string) $request->cookie('viewer_tz', '')) : '';
        if (is_valid_timezone($cookie)) {
            return $cookie;
        }

        $ip = function_exists('visitor_client_ip') ? visitor_client_ip() : ($request?->ip());
        if ($ip) {
            $cached = \Illuminate\Support\Facades\Cache::get('ip_tz:' . $ip);
            if (is_valid_timezone(is_string($cached) ? $cached : null)) {
                return $cached;
            }
            $mmdb = storage_path('app/geoip.mmdb');
            if (is_file($mmdb) && class_exists(\Torann\GeoIP\Facades\GeoIP::class)
                && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
            ) {
                try {
                    $loc = \Torann\GeoIP\Facades\GeoIP::getLocation($ip);
                    $tz = is_object($loc) ? (string) ($loc->timezone ?? '') : (string) ($loc['timezone'] ?? '');
                    if (is_valid_timezone($tz)) {
                        \Illuminate\Support\Facades\Cache::put('ip_tz:' . $ip, $tz, now()->addDay());

                        return $tz;
                    }
                } catch (\Throwable $e) {
                    // keep fallback
                }
            }
        }

        return is_valid_timezone($fallback) ? $fallback : 'UTC';
    }
}

if (!function_exists('viewer_timezone_label')) {
    function viewer_timezone_label(?string $tz = null): string
    {
        $tz = $tz ?: viewer_timezone();
        try {
            return now()->timezone($tz)->format('T P');
        } catch (\Throwable $e) {
            return '';
        }
    }
}

if (!function_exists('format_in_viewer_tz')) {
    function format_in_viewer_tz($value, string $fmt = 'M j, Y g:i A T'): string
    {
        if (! $value) {
            return '—';
        }
        try {
            $dt = $value instanceof \Carbon\Carbon ? $value->copy() : \Carbon\Carbon::parse($value);
            $out = $dt->timezone(viewer_timezone())->format($fmt);
            $label = viewer_timezone_label();
            if ($label !== '' && preg_match('/[HghisAa]/', $fmt) && ! str_contains($out, $label) && ! str_contains($fmt, 'T') && ! str_contains($fmt, 'e')) {
                $out .= ' (' . $label . ')';
            }

            return $out;
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }
}

if (!function_exists('global_women_card')) {
    function global_women_card(): array
    {
        $description = '<p>A Social Enterprise aims at bringing change in women\'s development and future leaders as the ratio of women vs men is drastically getting higher and women have to prepare themselves for leading the world by empowering and inspiring future women leaders, fostering entrepreneurship, and championing their education and healthcare for all.</p>'
            . '<ul>'
            . '<li><a href="https://women.berkeleyme.com/" target="_blank" rel="noopener">Power to Women Entrepreneurs</a></li>'
            . '<li><a href="https://women.berkeleyme.com/" target="_blank" rel="noopener">Past Conferences</a></li>'
            . '<li><a href="https://women.berkeleyme.com/" target="_blank" rel="noopener">Be a Woman Supporter</a></li>'
            . '<li><a href="https://women.berkeleyme.com/" target="_blank" rel="noopener">Be a Supporter of Women Entrepreneurs Support Centre</a></li>'
            . '</ul>';

        $card = [
            'title' => 'BERKELEY & MS VOICE GLOBAL WOMEN',
            'description' => $description,
            'image' => null,
            'icon' => null,
            'url' => 'https://women.berkeleyme.com/',
            'url_text' => 'Learn More',
            'url_target' => '0',
            'image_placement' => 'left',
            'layout' => 'layout-1',
            'background_color' => 'transparent',
            'content_background' => 'transparent',
            'color' => '#000000',
        ];

        try {
            if (! class_exists(\App\Models\PageSection::class)
                || ! \Illuminate\Support\Facades\Schema::hasTable('page_sections')) {
                return $card;
            }
            $sections = \App\Models\PageSection::query()
                ->where('section_type', 'media-section')
                ->orderBy('id')
                ->get();
            $found = $sections->first(function ($section) {
                $data = is_string($section->data) ? json_decode($section->data, true) : $section->data;
                $title = strtolower((string) ($data['title'] ?? ''));

                return str_contains($title, 'global women')
                    || str_contains($title, 'ms voice')
                    || str_contains($title, 'voice of global');
            });
            if ($found) {
                $data = is_string($found->data) ? json_decode($found->data, true) : $found->data;
                if (is_array($data)) {
                    $card = array_merge($card, array_filter($data, fn ($v) => $v !== null && $v !== ''));
                }
            }
        } catch (\Throwable $e) {
        }

        return $card;
    }
}

if (!function_exists('should_show_global_women_card')) {
    function should_show_global_women_card($page = null): bool
    {
        if (! $page || empty($page->sections)) {
            return true;
        }
        foreach ($page->sections as $section) {
            if (($section->section_type ?? '') !== 'media-section') {
                continue;
            }
            $data = is_string($section->data) ? json_decode($section->data, true) : $section->data;
            $title = strtolower((string) ($data['title'] ?? ''));
            if (str_contains($title, 'global women')
                || str_contains($title, 'ms voice')
                || str_contains($title, 'voice of global')) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('ensure_image_text_2col_card_column')) {
    function ensure_image_text_2col_card_column(): bool
    {
        static $ready = null;

        if ($ready === true) {
            return true;
        }

        if ($ready === false) {
            return false;
        }

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('courses')
                && ! \Illuminate\Support\Facades\Schema::hasColumn('courses', 'image_text_2col_card')) {
                \Illuminate\Support\Facades\Schema::table('courses', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->json('image_text_2col_card')->nullable();
                });
            }

            $ready = \Illuminate\Support\Facades\Schema::hasTable('courses')
                && \Illuminate\Support\Facades\Schema::hasColumn('courses', 'image_text_2col_card');

            return $ready;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Could not ensure image_text_2col_card column: ' . $e->getMessage());
            $ready = false;

            return false;
        }
    }
}

if (!function_exists('course_image_text_2col_card')) {
    function course_image_text_2col_card($course): array
    {
        if (! $course || ! ensure_image_text_2col_card_column()) {
            return [];
        }

        $raw = $course->image_text_2col_card ?? null;
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }

        return is_array($raw) ? $raw : [];
    }
}

if (!function_exists('should_show_course_image_text_2col_card')) {
    function should_show_course_image_text_2col_card($course): bool
    {
        $card = course_image_text_2col_card($course);
        if ((int) ($card['status'] ?? 0) !== 1) {
            return false;
        }

        $title = trim((string) ($card['title'] ?? ''));
        $description = trim(strip_tags((string) ($card['description'] ?? '')));
        $image = trim((string) ($card['image'] ?? ''));

        return $title !== '' || $description !== '' || $image !== '';
    }
}

if (!function_exists('payment_type_options')) {
    function payment_type_options(): array
    {
        return [
            'bank' => 'Bank',
            'cash' => 'Cash',
            'card' => 'Card',
            'cheque' => 'Cheque',
        ];
    }
}
