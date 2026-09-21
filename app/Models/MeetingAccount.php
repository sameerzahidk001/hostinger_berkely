<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

class MeetingAccount extends Model
{
    public const PROVIDER_ZOHO = 'zoho';
    public const PROVIDER_ZOOM = 'zoom';

    protected $fillable = [
        'provider',
        'label',
        'host_email',
        'is_default',
        'is_active',
        'credentials_json',
        'timezone',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function schedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class, 'meeting_account_id');
    }

    public function isZoho(): bool
    {
        return $this->provider === self::PROVIDER_ZOHO;
    }

    public function isZoom(): bool
    {
        return $this->provider === self::PROVIDER_ZOOM;
    }

    public function dropdownLabel(): string
    {
        $provider = $this->isZoom() ? 'Zoom' : 'Zoho';
        $tz = $this->timezone ?: 'Asia/Dubai';

        return $provider . ' — ' . $this->label . ' (' . $tz . ')';
    }

    public function timezoneLabel(): string
    {
        $tz = $this->timezone ?: config('app.timezone', 'Asia/Dubai');
        try {
            $abbr = now($tz)->format('T');
        } catch (\Throwable $e) {
            $abbr = '';
        }

        return trim($tz . ($abbr !== '' && $abbr !== $tz ? ' · ' . $abbr : ''));
    }

    public function credentials(): array
    {
        $raw = $this->credentials_json;
        if ($raw === null || $raw === '') {
            return [];
        }

        try {
            $decoded = Crypt::decryptString($raw);
            $data = json_decode($decoded, true);

            return is_array($data) ? $data : [];
        } catch (\Throwable $e) {
            // Allow plain JSON during early bootstrap / one-time seed.
            $data = json_decode((string) $raw, true);

            return is_array($data) ? $data : [];
        }
    }

    public function setCredentials(array $credentials): void
    {
        $this->credentials_json = Crypt::encryptString(json_encode($credentials));
    }

    public function credential(string $key, $default = null)
    {
        $creds = $this->credentials();

        return $creds[$key] ?? $default;
    }

    public function hasRequiredCredentials(): bool
    {
        if ($this->isZoom()) {
            // Prefer Server-to-Server OAuth for auto Join links; label-only still allowed for manual paste.
            return filled($this->credential('account_id'))
                && filled($this->credential('client_id'))
                && filled($this->credential('client_secret'));
        }

        return filled($this->credential('client_id'))
            && filled($this->credential('client_secret'))
            && filled($this->credential('refresh_token'));
    }

    /** Zoom accounts without API keys are still usable if the Join link is pasted on the schedule. */
    public function allowsManualJoinLink(): bool
    {
        return $this->isZoom();
    }

    public static function activeForDropdown()
    {
        if (! Schema::hasTable('meeting_accounts')) {
            return collect();
        }

        return static::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('provider')
            ->orderBy('label')
            ->get();
    }

    public static function defaultId(): ?int
    {
        if (! Schema::hasTable('meeting_accounts')) {
            return null;
        }

        $id = static::query()->where('is_active', true)->where('is_default', true)->value('id');
        if ($id) {
            return (int) $id;
        }

        $id = static::query()->where('is_active', true)->orderBy('id')->value('id');

        return $id ? (int) $id : null;
    }

    public function makeDefault(): void
    {
        // Only one global default across providers for schedule prefill.
        static::query()->update(['is_default' => false]);
        $this->forceFill(['is_default' => true])->save();
        $this->refresh();
    }

    /**
     * If no account is marked default, promote the first active one.
     */
    public static function ensureOneDefault(): void
    {
        if (! Schema::hasTable('meeting_accounts')) {
            return;
        }

        if (static::query()->where('is_default', true)->exists()) {
            return;
        }

        $first = static::query()->where('is_active', true)->orderBy('id')->first()
            ?: static::query()->orderBy('id')->first();

        if ($first) {
            $first->makeDefault();
        }
    }

    /**
     * Seed the current .env Zoho connection as the first default account.
     */
    public static function ensureDefaultZohoFromEnv(): void
    {
        if (! Schema::hasTable('meeting_accounts')) {
            return;
        }

        if (static::query()->where('provider', self::PROVIDER_ZOHO)->exists()) {
            static::ensureOneDefault();

            return;
        }

        $clientId = config('zoho.client_id');
        $clientSecret = config('zoho.client_secret');
        $refreshToken = config('zoho.refresh_token');
        if (! filled($clientId) || ! filled($clientSecret) || ! filled($refreshToken)) {
            return;
        }

        $account = new static();
        $account->provider = self::PROVIDER_ZOHO;
        $account->label = 'Berkeley Zoho (default)';
        $account->host_email = config('zoho.account_email', 'bdm@berkeleyme.com');
        $account->is_default = true;
        $account->is_active = true;
        $account->timezone = config('zoho.timezone', 'Asia/Dubai');
        $account->setCredentials([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
            'org_id' => config('zoho.org_id'),
            'presenter_zuid' => config('zoho.presenter_zuid'),
            'calendar_uid' => config('zoho.calendar_uid'),
            'workdrive_folder_id' => config('zoho.workdrive_folder_id'),
            'accounts_url' => config('zoho.accounts_url'),
            'meeting_url' => config('zoho.meeting_url'),
        ]);
        $account->save();
    }
}
