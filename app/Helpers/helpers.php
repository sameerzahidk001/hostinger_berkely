<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Category;
use App\Models\Page;

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

if (!function_exists('role_display_name')) {
    function role_display_name(?string $role): string
    {
        return match ($role) {
            'librarian' => 'Content Writer',
            'accountant' => 'Accountant',
            default => ucfirst($role ?? ''),
        };
    }
}

if (!function_exists('admin_can_delete')) {
    function admin_can_delete(): bool
    {
        return !in_array(panel_role_name(), ['librarian', 'accountant'], true);
    }
}

if (!function_exists('admin_menu_allowed')) {
    function admin_menu_allowed(string $menu): bool
    {
        $role = panel_role_name();

        if ($role === null || $role === 'admin') {
            return true;
        }

        $contentWriterMenus = [
            'dashboard', 'courses', 'training-calendar', 'school', 'categories',
            'pages', 'seo', 'faq', 'clients', 'profile', 'logout',
        ];

        $accountantMenus = [
            'dashboard', 'courses', 'currency-rate-setup', 'currencies',
            'payments', 'profile', 'logout',
        ];

        if ($role === 'librarian') {
            return in_array($menu, $contentWriterMenus, true);
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
        $packagePrice = (float) (optional($payment->courseFee)->price ?? 0);

        if ($settlingTotal > 0 && $packagePrice > 0) {
            return round(($aedAmount / $settlingTotal) * $packagePrice, 2);
        }

        return convert_from_aed($aedAmount, $currency);
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
        $display = format_payment_aed_amount($payment, $aedAmount);

        if (payment_display_currency($payment) === 'AED') {
            return $display;
        }

        return $display . ' <span class="text-muted">(AED ' . number_format($aedAmount, 2) . ')</span>';
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

        if ($currency === 'AED') {
            return [
                'display' => 'AED ' . number_format($settlingAed, 2),
                'settling_aed' => $settlingAed,
                'currency' => 'AED',
                'show_settling_note' => false,
            ];
        }

        $displayAmount = (float) (optional($payment->courseFee)->price ?? convert_from_aed($settlingAed, $currency));

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
