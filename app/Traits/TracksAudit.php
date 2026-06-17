<?php

namespace App\Traits;

use Illuminate\Support\Facades\Schema;

trait TracksAudit
{
    /** @var array<string, bool> */
    private static array $auditColumnCache = [];

    protected static function bootTracksAudit(): void
    {
        static::creating(function ($model) {
            if (! $model->auditColumnsExist()) {
                return;
            }

            $userId = audit_user_id();
            if ($userId) {
                $model->created_by = $userId;
                $model->updated_by = $userId;
            }
        });

        static::updating(function ($model) {
            if (! $model->auditColumnsExist()) {
                return;
            }

            $userId = audit_user_id();
            if ($userId) {
                $model->updated_by = $userId;
            }
        });
    }

    protected function auditColumnsExist(): bool
    {
        $table = $this->getTable();

        if (! array_key_exists($table, self::$auditColumnCache)) {
            self::$auditColumnCache[$table] = Schema::hasTable($table)
                && Schema::hasColumn($table, 'created_by')
                && Schema::hasColumn($table, 'updated_by');
        }

        return self::$auditColumnCache[$table];
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\Admin::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(\App\Models\Admin::class, 'updated_by');
    }
}
