<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'mobile_number',
        'password',
        'short_description',
        'image',
        'gender',
        'date_of_birth',
        'address',
        'post_code',
        'nationality',
        'city',
        'latitude',
        'longitude',
        'country',
        'experience',
        'education',
        'expertise',
        'teaching_methodology',
        'availability',
        'linkedin',
        'long_description',
        'ip_address',
        'approved',
        'is_on_web',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_has_roles');
    }

    public function hasPermission($permission)
    {
        return $this->roles()->whereHas('permissions', function ($query) use ($permission) {
            $query->where('name', $permission);
        })->exists();
    }

    public function countryarray()
    {
        return $this->belongsTo(Country::class, 'country', 'iso_code');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'user_id');
    }

    public function courses()
    {
        return $this->hasMany(Course::class, 'instructor_id');
    }

    public static function teachingMethodologyOptions(): array
    {
        return [
            'online_live_batch' => 'Online Live Classes for Batch',
            'on_campus' => 'On Campus (Face to Face)',
            'one_to_one_online' => 'One-to-one Individually Focused Online Live Classes',
        ];
    }

    public function expertiseList(): array
    {
        $raw = $this->expertise ?? null;
        if (is_array($raw)) {
            return array_values(array_filter(array_map('strval', $raw)));
        }
        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? array_values(array_filter(array_map('strval', $decoded))) : [];
    }

    public function educationList(): array
    {
        $raw = $this->education ?? null;
        if (is_array($raw)) {
            return array_values(array_filter(array_map(static fn ($v) => trim((string) $v), $raw), static fn ($v) => $v !== ''));
        }
        $decoded = json_decode((string) $raw, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map(static fn ($v) => trim((string) $v), $decoded), static fn ($v) => $v !== ''));
        }
        $plain = trim((string) $raw);

        return $plain !== '' ? [$plain] : [];
    }

    public function teachingMethodologyList(): array
    {
        $raw = $this->teaching_methodology ?? null;
        if (is_array($raw)) {
            return array_values(array_filter(array_map('strval', $raw)));
        }
        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? array_values(array_filter(array_map('strval', $decoded))) : [];
    }

    public function availabilityData(): array
    {
        $raw = $this->availability ?? null;
        if (is_array($raw)) {
            return $raw;
        }
        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    public static function ensureInstructorExtraColumns(): bool
    {
        if (! Schema::hasTable('users')) {
            return false;
        }

        $needed = ['expertise', 'teaching_methodology', 'availability', 'long_description'];
        $missing = array_values(array_filter($needed, fn ($col) => ! Schema::hasColumn('users', $col)));
        if ($missing === []) {
            return true;
        }

        Schema::table('users', function (Blueprint $table) use ($missing) {
            if (in_array('expertise', $missing, true)) {
                $table->text('expertise')->nullable();
            }
            if (in_array('teaching_methodology', $missing, true)) {
                $table->text('teaching_methodology')->nullable();
            }
            if (in_array('availability', $missing, true)) {
                $table->text('availability')->nullable();
            }
            if (in_array('long_description', $missing, true)) {
                $table->longText('long_description')->nullable();
            }
        });

        return true;
    }

    /**
     * Hostinger deploys often skip artisan migrate; ensure map coordinates exist.
     */
    public static function ensureLatLngColumns(): bool
    {
        if (! Schema::hasTable('users')) {
            return false;
        }

        $needLat = ! Schema::hasColumn('users', 'latitude');
        $needLng = ! Schema::hasColumn('users', 'longitude');
        if (! $needLat && ! $needLng) {
            return true;
        }

        Schema::table('users', function (Blueprint $table) use ($needLat, $needLng) {
            if ($needLat) {
                $table->decimal('latitude', 10, 7)->nullable()->after('city');
            }
            if ($needLng) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
        });

        return Schema::hasColumn('users', 'latitude') && Schema::hasColumn('users', 'longitude');
    }

    public function hasMapLocation(): bool
    {
        return $this->latitude !== null
            && $this->longitude !== null
            && is_numeric($this->latitude)
            && is_numeric($this->longitude);
    }

    public static function encodeListField(?array $items): string
    {
        $clean = array_values(array_filter(
            array_map(static fn ($v) => trim((string) $v), $items ?? []),
            static fn ($v) => $v !== ''
        ));

        return json_encode($clean);
    }

    public static function encodeAvailabilityField(?array $data): string
    {
        $data = $data ?? [];
        $frequency = ($data['frequency'] ?? 'particular') === 'daily' ? 'daily' : 'particular';
        $days = array_values(array_filter(array_map('strval', (array) ($data['days'] ?? []))));
        $allowedDays = ['MO', 'TU', 'WE', 'TH', 'FR', 'SA', 'SU'];
        $days = array_values(array_intersect($days, $allowedDays));
        if ($frequency === 'daily') {
            $days = [];
        }
        $flexible = in_array($data['flexible'] ?? 'no', [true, 1, '1', 'yes', 'Yes'], true) ? 'yes' : 'no';

        $daySlots = [];
        $rawSlots = is_array($data['day_slots'] ?? null) ? $data['day_slots'] : [];
        foreach ($days as $code) {
            $slot = is_array($rawSlots[$code] ?? null) ? $rawSlots[$code] : [];
            $start = trim((string) ($slot['start_time'] ?? ''));
            $end = trim((string) ($slot['end_time'] ?? ''));
            $tz = trim((string) ($slot['timezone'] ?? ($data['timezone'] ?? 'Asia/Dubai')));
            if ($tz === '') {
                $tz = 'Asia/Dubai';
            }
            if ($start === '' && $end === '') {
                continue;
            }
            $daySlots[$code] = [
                'start_time' => $start,
                'end_time' => $end,
                'timezone' => $tz,
            ];
        }

        return json_encode([
            'frequency' => $frequency,
            'days' => $days,
            'day_slots' => $daySlots,
            'start_time' => (string) ($data['start_time'] ?? ''),
            'end_time' => (string) ($data['end_time'] ?? ''),
            'timezone' => (string) ($data['timezone'] ?? 'Asia/Dubai'),
            'flexible' => $flexible,
        ]);
    }

    /**
     * Human-readable availability lines for public / view profile.
     *
     * @return list<string>
     */
    public function availabilityDisplayLines(): array
    {
        $availability = $this->availabilityData();
        if ($availability === []) {
            return [];
        }

        $dayCodes = ['MO' => 'Mon', 'TU' => 'Tue', 'WE' => 'Wed', 'TH' => 'Thu', 'FR' => 'Fri', 'SA' => 'Sat', 'SU' => 'Sun'];
        $flexible = in_array($availability['flexible'] ?? 'no', ['yes', true, 1, '1'], true);
        $lines = [];

        if (($availability['frequency'] ?? '') === 'daily') {
            $time = trim(($availability['start_time'] ?? '') . (($availability['start_time'] ?? '') && ($availability['end_time'] ?? '') ? ' – ' : '') . ($availability['end_time'] ?? ''));
            $tz = trim((string) ($availability['timezone'] ?? ''));
            $line = 'Daily';
            if ($time !== '') {
                $line .= ' · ' . $time;
            }
            if ($tz !== '') {
                $line .= ' (' . $tz . ')';
            }
            if ($flexible) {
                $line .= ' · Flexible';
            }
            if ($time !== '' || $tz !== '') {
                $lines[] = $line;
            }

            return $lines;
        }

        $days = $availability['days'] ?? [];
        $slots = is_array($availability['day_slots'] ?? null) ? $availability['day_slots'] : [];
        $legacyStart = trim((string) ($availability['start_time'] ?? ''));
        $legacyEnd = trim((string) ($availability['end_time'] ?? ''));
        $legacyTz = trim((string) ($availability['timezone'] ?? ''));

        foreach ((array) $days as $code) {
            $code = (string) $code;
            $label = $dayCodes[$code] ?? $code;
            $slot = is_array($slots[$code] ?? null) ? $slots[$code] : [];
            $start = trim((string) ($slot['start_time'] ?? $legacyStart));
            $end = trim((string) ($slot['end_time'] ?? $legacyEnd));
            $tz = trim((string) ($slot['timezone'] ?? $legacyTz));
            $time = trim($start . ($start && $end ? ' – ' : '') . $end);
            if ($time === '' && $tz === '') {
                continue;
            }
            $line = $label;
            if ($time !== '') {
                $line .= ' · ' . $time;
            }
            if ($tz !== '') {
                $line .= ' (' . $tz . ')';
            }
            $lines[] = $line;
        }

        if ($lines === [] && ($legacyStart !== '' || $legacyEnd !== '')) {
            $time = trim($legacyStart . ($legacyStart && $legacyEnd ? ' – ' : '') . $legacyEnd);
            $line = collect((array) $days)->map(fn ($d) => $dayCodes[$d] ?? $d)->filter()->implode(', ');
            $line = ($line !== '' ? $line : 'Particular days') . ($time !== '' ? ' · ' . $time : '');
            if ($legacyTz !== '') {
                $line .= ' (' . $legacyTz . ')';
            }
            $lines[] = $line;
        }

        if ($flexible && $lines !== []) {
            $lines[count($lines) - 1] .= ' · Flexible';
        }

        return $lines;
    }

    public function applyInstructorExtraFields(\Illuminate\Http\Request $request): void
    {
        self::ensureInstructorExtraColumns();
        $this->education = self::encodeListField($request->input('education'));
        $this->expertise = self::encodeListField($request->input('expertise'));
        $allowedMethods = array_keys(self::teachingMethodologyOptions());
        $methods = array_values(array_intersect(
            array_map('strval', (array) $request->input('teaching_methodology', [])),
            $allowedMethods
        ));
        $this->teaching_methodology = json_encode($methods);
        $this->availability = self::encodeAvailabilityField($request->input('availability'));
    }
}
