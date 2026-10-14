<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZohoCrmOAuthService
{
    public function isConfigured(): bool
    {
        return filled(config('zoho_crm.client_id'))
            && filled(config('zoho_crm.client_secret'))
            && filled(config('zoho_crm.refresh_token'));
    }

    public function isEnabled(): bool
    {
        return (bool) config('zoho_crm.enabled') && $this->isConfigured();
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
