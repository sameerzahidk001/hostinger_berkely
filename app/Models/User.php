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
        if ($frequency === 'daily') {
            $days = [];
        }
        $flexible = in_array($data['flexible'] ?? 'no', [true, 1, '1', 'yes', 'Yes'], true) ? 'yes' : 'no';

        return json_encode([
            'frequency' => $frequency,
            'days' => $days,
            'start_time' => (string) ($data['start_time'] ?? ''),
            'end_time' => (string) ($data['end_time'] ?? ''),
            'timezone' => (string) ($data['timezone'] ?? 'Asia/Dubai'),
            'flexible' => $flexible,
        ]);
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
