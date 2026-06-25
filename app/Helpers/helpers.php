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

        if (str_starts_with($path, 'admin/')) {
            return asset($path);
        }

        if (str_starts_with($path, 'images/') || str_starts_with($path, 'frontend/')) {
            return asset($path);
        }

        return asset('images/library/' . $path);
    }
}

function generateFileName($file, $prefix = '')
{
    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
    $extension = $file->getClientOriginalExtension();
    $slug = Str::slug($originalName);
    $unique = uniqid();
    return ($prefix ? $prefix . '_' : '') . $slug . '-' . $unique . '.' . $extension;
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
            return asset($path);
        }

        return asset('student/img/landing/avatar_all.png');
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

        return $role;
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
            'keywords' => 'nullable|string|max:' . $limits['priority_keywords_max_total'],
            'additional_keywords' => 'nullable|string|max:' . $limits['additional_keywords_max_total'],
        ];
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
        if (! empty($seo->course_id) && $seo->relationLoaded('course') && $seo->course) {
            return (string) ($seo->course->slug ?? '');
        }

        if (! empty($seo->page_id) && $seo->relationLoaded('page') && $seo->page) {
            $page = $seo->page;

            if ($page->parent_id && $page->relationLoaded('parent') && $page->parent) {
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
            'pages', 'faq', 'seo', 'analytics', 'clients', 'profile', 'logout',
        ];

        $accountantMenus = [
            'dashboard', 'courses', 'currency-rate-setup',
            'payments', 'payment-gateway', 'profile', 'logout',
        ];

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

if (!function_exists('audit_user_name')) {
    function audit_user_name($model, $fallbackId = null): string
    {
        if ($model) {
            return $model->name ?? $model->username ?? $model->email ?? '-';
        }

        if ($fallbackId) {
            $admin = \App\Models\Admin::find($fallbackId);
            if ($admin) {
                return $admin->name ?? $admin->email ?? '-';
            }

            $user = \App\Models\User::find($fallbackId);
            if ($user) {
                return $user->name ?? $user->email ?? '-';
            }
        }

        return '-';
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

                $file->move($destinationPath, $fileName);

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

        if (in_array($normalized, ['content_writer', 'accountant'], true)) {
            return 'staff';
        }

        if ($normalized === 'instructor') {
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

        return [];
    }
}

if (!function_exists('courses_for_instructor')) {
    function courses_for_instructor(int $instructorId): \Illuminate\Support\Collection
    {
        return \App\Models\Course::query()
            ->whereNotNull('instructor_id')
            ->where('instructor_id', '!=', '')
            ->orderBy('title')
            ->get(['id', 'title', 'slug', 'description', 'instructor_id'])
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

        return activity_audience_for_role($user->roles()->value('name'));
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
        static $loggedCourseIds = [];

        if (isset($loggedCourseIds[$course->id])) {
            return;
        }

        $loggedCourseIds[$course->id] = true;

        record_panel_activity(
            'Course Updated',
            $course->title ?: 'Course #' . $course->id,
            route('course.edit', $course->id)
        );
    }
}

if (!function_exists('cart_item_count')) {
    function cart_item_count(): int
    {
        if (!Auth::check()) {
            return 0;
        }

        return (int) \App\Models\CartItem::where('user_id', Auth::id())
            ->whereHas('courseFee')
            ->sum('quantity');
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

        return e($info['display']) . ' <span class="text-muted">(AED ' . number_format($info['settling_aed'], 2) . ')</span>';
    }
}

if (!function_exists('format_payment_aed_amount_admin')) {
    function format_payment_aed_amount_admin($payment, float $aedAmount): string
    {
        $currency = payment_display_currency($payment);
        $displayAmount = payment_display_amount_from_aed($payment, $aedAmount);
        $display = $currency . ' ' . number_format($displayAmount, 2);

        if ($currency === 'AED') {
            return $display;
        }

        $bracketAed = payment_bracket_aed_from_display($payment, $displayAmount);

        return $display . ' <span class="text-muted">(AED ' . number_format($bracketAed, 2) . ')</span>';
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
    function normalize_payment_email_body(string $body): string
    {
        return preg_replace(
            '/Amount Paid:\s*AED\s+(?=(USD|GBP|AED)\s)/i',
            'Amount Paid: ',
            $body
        );
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

if (!function_exists('getUserLocation')) {
    function getUserLocation($ip = null)
    {
        $ip = $ip ?? request()->ip();

        if ($ip === '127.0.0.1' || $ip === '::1') {
            $ip = '8.8.8.8';
        }

        try {
            $token = env('IPINFO_TOKEN');
            $url = $token
                ? "https://ipinfo.io/{$ip}?token={$token}"
                : "https://ipinfo.io/{$ip}/json";

            $response = Http::timeout(5)->get($url);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            return ['error' => 'Unable to fetch location.'];
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

        if (! $ip) {
            return $empty;
        }

        if (in_array($ip, ['127.0.0.1', '::1'], true)) {
            return $empty;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return $empty;
        }

        return \Illuminate\Support\Facades\Cache::remember(
            'ip_location:' . $ip,
            now()->addDays(7),
            function () use ($ip, $empty) {
                $data = getUserLocation($ip);

                if (empty($data) || isset($data['error'])) {
                    return $empty;
                }

                $countryCode = $data['country'] ?? null;
                $countryName = $countryCode;

                if ($countryCode) {
                    $name = \App\Models\Country::where('iso_code', $countryCode)->value('name');
                    if ($name) {
                        $countryName = $name;
                    }
                }

                return [
                    'country' => $countryName ?: $countryCode,
                    'city' => $data['city'] ?? null,
                    'region' => $data['region'] ?? null,
                    'postal' => $data['postal'] ?? null,
                    'location' => $data['loc'] ?? null,
                ];
            }
        );
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
