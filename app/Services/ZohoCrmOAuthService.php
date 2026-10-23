<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZohoCrmOAuthService
{
    public function __construct()
    {
        $this->overlayRuntime();
    }

    public function runtimePath(): string
    {
        return storage_path('app/zoho_crm_runtime.json');
    }

    /**
     * Hostinger often keeps config cached, so admin-saved CRM credentials
     * must also live outside bootstrap/cache/config.php.
     */
    public function persistRuntime(array $values): void
    {
        $current = $this->runtimeValues();
        $merged = array_merge($current, $values);
        foreach ($merged as $key => $value) {
            if ($value === null || $value === '') {
                unset($merged[$key]);
            }
        }
        $dir = dirname($this->runtimePath());
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents(
            $this->runtimePath(),
            json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
        $this->overlayRuntime();
    }

    /**
     * @return array<string, mixed>
     */
    public function runtimeValues(): array
    {
        $path = $this->runtimePath();
        if (! is_file($path)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) ? $data : [];
    }

    public function overlayRuntime(): void
    {
        $data = $this->runtimeValues();
        if ($data === []) {
            return;
        }

        $map = [
            'enabled' => 'zoho_crm.enabled',
            'client_id' => 'zoho_crm.client_id',
            'client_secret' => 'zoho_crm.client_secret',
            'refresh_token' => 'zoho_crm.refresh_token',
            'api_base' => 'zoho_crm.api_base',
            'accounts_url' => 'zoho_crm.accounts_url',
            'module_invoice' => 'zoho_crm.modules.invoice',
            'module_receipt' => 'zoho_crm.modules.receipt',
            'module_deal' => 'zoho_crm.modules.deal',
        ];

        foreach ($map as $key => $configKey) {
            if (! array_key_exists($key, $data) || $data[$key] === '' || $data[$key] === null) {
                continue;
            }
            $value = $data[$key];
            if ($key === 'enabled') {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }
            config([$configKey => $value]);
        }
    }

    public function isConfigured(): bool
    {
        $this->overlayRuntime();

        return filled(config('zoho_crm.client_id'))
            && filled(config('zoho_crm.client_secret'))
            && filled(config('zoho_crm.refresh_token'));
    }

    public function isEnabled(): bool
    {
        $this->overlayRuntime();

        return (bool) config('zoho_crm.enabled') && $this->isConfigured();
    }

    public function blockedReason(): ?string
    {
        if ($this->isEnabled()) {
            return null;
        }

        if (! $this->isConfigured()) {
            return 'Zoho CRM is not connected. Open Settings → Zoho CRM (Invoices), add Client ID, Client Secret, and Refresh Token, then set Enable CRM sync to Enabled.';
        }

        return 'Zoho CRM sync is disabled. Open Settings → Zoho CRM (Invoices) and set Enable CRM sync to Enabled.';
    }

    public function accessToken(): string
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Zoho CRM OAuth is not configured.');
        }

        return Cache::remember('zoho_crm_access_token', 50 * 60, function () {
            $response = Http::asForm()->timeout(30)->post(
                rtrim((string) config('zoho_crm.accounts_url'), '/') . '/oauth/v2/token',
                [
                    'refresh_token' => config('zoho_crm.refresh_token'),
                    'client_id' => config('zoho_crm.client_id'),
                    'client_secret' => config('zoho_crm.client_secret'),
                    'grant_type' => 'refresh_token',
                ]
            );

            if (! $response->successful() || empty($response->json('access_token'))) {
                Log::error('Zoho CRM token refresh failed', ['body' => $response->body()]);
                throw new \RuntimeException('Zoho CRM token refresh failed: ' . $response->body());
            }

            return (string) $response->json('access_token');
        });
    }

    public function clearTokenCache(): void
    {
        Cache::forget('zoho_crm_access_token');
    }

    public function exchangeGrantCode(string $code): array
    {
        $this->clearTokenCache();

        $response = Http::asForm()->timeout(30)->post(
            rtrim((string) config('zoho_crm.accounts_url'), '/') . '/oauth/v2/token',
            [
                'code' => $code,
                'client_id' => config('zoho_crm.client_id'),
                'client_secret' => config('zoho_crm.client_secret'),
                'grant_type' => 'authorization_code',
            ]
        );

        if (! $response->successful() || empty($response->json('refresh_token'))) {
            throw new \RuntimeException('Zoho CRM grant exchange failed: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * @return array{configured:bool,enabled:bool,connected_email:?string,error:?string}
     */
    public function connectionStatus(): array
    {
        $status = [
            'configured' => $this->isConfigured(),
            'enabled' => (bool) config('zoho_crm.enabled'),
            'connected_email' => null,
            'error' => null,
        ];

        if (! $status['configured']) {
            $status['error'] = 'Missing Zoho CRM client ID, secret, or refresh token.';

            return $status;
        }

        try {
            $response = Http::withToken($this->accessToken(), 'Zoho-oauthtoken')
                ->acceptJson()
                ->timeout(20)
                ->get(rtrim((string) config('zoho_crm.api_base'), '/') . '/users', [
                    'type' => 'CurrentUser',
                ]);

            if (! $response->successful()) {
                $status['error'] = 'CRM API check failed: ' . $response->body();

                return $status;
            }

            $status['connected_email'] = data_get($response->json(), 'users.0.email')
                ?: data_get($response->json(), 'users.0.full_name');
        } catch (\Throwable $e) {
            $status['error'] = $e->getMessage();
        }

        return $status;
    }

    public function http()
    {
        return Http::withToken($this->accessToken(), 'Zoho-oauthtoken')
            ->acceptJson()
            ->timeout(45);
    }
}
