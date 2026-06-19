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
            'pages', 'seo', 'faq', 'analytics', 'clients', 'profile', 'logout',
        ];

        $accountantMenus = [
            'dashboard', 'courses', 'currency-rate-setup', 'currencies',
            'payments', 'profile', 'logout',
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
        ?\Illuminate\Http\Request $request = null
    ): void {
        $request = $request ?? request();

        app(\App\Services\UserActivityLogService::class)->log(
            $action,
            $audience ?? 'staff',
            $item,
            $url,
            $userId,
            $adminId,
            $request?->ip(),
            $request?->session()?->getId(),
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
        return package_currency_code(
            $payment->currency ?? optional($payment->courseFee)->currency ?? 'AED'
        );
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
