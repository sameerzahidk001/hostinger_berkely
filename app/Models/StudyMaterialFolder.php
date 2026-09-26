<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyMaterialFolder extends Model
{
    protected $fillable = [
        'code',
        'name',
        'course_id',
        'fee_package_id',
        'validity_months',
        'status',
        'owner_type',
        'owner_id',
        'created_by_admin_id',
        'created_by_user_id',
    ];

    protected static function booted(): void
    {
        static::created(function (self $folder) {
            $folder->ensureCode();
        });
    }

    public static function codeForId(int $id): string
    {
        return 'SM-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    }

    public function ensureCode(): void
    {
        if (filled($this->code) || ! $this->id) {
            return;
        }

        $this->code = static::codeForId((int) $this->id);
        $this->saveQuietly();
    }

    public function displayName(): string
    {
        $code = $this->code ?: ($this->id ? static::codeForId((int) $this->id) : '');

        return trim($code . ' — ' . $this->name, ' —');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function feePackage(): BelongsTo
    {
        return $this->belongsTo(CourseFee::class, 'fee_package_id');
    }

    public function feePackages(): BelongsToMany
    {
        return $this->belongsToMany(
            CourseFee::class,
            'study_material_folder_packages',
            'folder_id',
            'course_fee_id'
        )->withTimestamps();
    }

    public function hasUnlimitedValidity(): bool
    {
        return $this->validity_months === null || (int) $this->validity_months <= 0;
    }

    public function validityLabel(): string
    {
        if ($this->hasUnlimitedValidity()) {
            return 'None';
        }

        $months = (int) $this->validity_months;

        return $months . ' Month' . ($months === 1 ? '' : 's');
    }

    public function packageNames(): string
    {
        $names = $this->relationLoaded('feePackages')
            ? $this->feePackages->pluck('package_name')
            : $this->feePackages()->pluck('package_name');

        $names = $names->filter()->values();

        if ($names->isEmpty() && $this->feePackage) {
            return (string) $this->feePackage->package_name;
        }

        return $names->isEmpty() ? '—' : $names->implode(', ');
    }

    public function selectedPackageIds(): array
    {
        $ids = $this->relationLoaded('feePackages')
            ? $this->feePackages->pluck('id')
            : $this->feePackages()->pluck('course_fees.id');

        $ids = $ids->map(fn ($id) => (int) $id)->filter()->values();

        if ($ids->isEmpty() && $this->fee_package_id) {
            return [(int) $this->fee_package_id];
        }

        return $ids->all();
    }

    public function items(): HasMany
    {
        return $this->hasMany(StudyMaterialItem::class, 'folder_id')->orderBy('sort_order')->orderBy('name');
    }

    public function rootItems(): HasMany
    {
        return $this->hasMany(StudyMaterialItem::class, 'folder_id')
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function folderTreeOptions(): array
    {
        $folders = $this->items
            ->where('type', 'folder')
            ->values();

        $options = [
            ['id' => '', 'label' => $this->name . ' (Main folder)'],
        ];

        $walk = function ($parentId, int $depth) use (&$walk, $folders, &$options) {
            $children = StudyMaterialItem::naturalSort($folders->filter(function ($item) use ($parentId) {
                return (int) $item->parent_id === (int) $parentId
                    || ($parentId === null && empty($item->parent_id));
            }));

            foreach ($children as $item) {
                $options[] = [
                    'id' => $item->id,
                    'label' => str_repeat('— ', $depth + 1) . $item->name,
                ];
                $walk($item->id, $depth + 1);
            }
        };

        $walk(null, 0);

        return $options;
    }

    public function instructorAccess(): HasMany
    {
        return $this->hasMany(StudyMaterialInstructorAccess::class, 'folder_id');
    }

    public function studentAccess(): HasMany
    {
        return $this->hasMany(StudyMaterialStudentAccess::class, 'folder_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function ownerName(): string
    {
        if ($this->owner_type === 'instructor' && $this->owner_id) {
            return User::query()->whereKey($this->owner_id)->value('name') ?? 'Instructor';
        }

        return 'Admin';
    }

    public function instructorNames(): string
    {
        $names = $this->displayInstructors()->pluck('name')->filter()->values();

        return $names->isEmpty() ? '—' : $names->implode(', ');
    }

    public function displayInstructors()
    {
        $fromFolder = ($this->relationLoaded('instructorAccess')
            ? $this->instructorAccess
            : $this->instructorAccess()->with('instructor')->get()
        )
            ->map(fn ($row) => $row->instructor)
            ->filter()
            ->unique('id')
            ->values();

        // Prefer course order: [0] Head of Faculty, [1] Instructor — then any extras from folder access.
        $orderedIds = course_instructor_ids($this->course);
        foreach ($fromFolder as $user) {
            $id = (int) $user->id;
            if ($id && ! in_array($id, $orderedIds, true)) {
                $orderedIds[] = $id;
            }
        }

        if ($orderedIds === []) {
            return $fromFolder;
        }

        $users = User::query()->whereIn('id', $orderedIds)->get()->keyBy('id');

        return collect($orderedIds)
            ->map(fn ($id) => $users->get($id))
            ->filter()
            ->values();
    }
}
