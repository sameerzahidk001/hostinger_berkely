<?php

namespace App\Traits;

trait TracksAudit
{
    protected static function bootTracksAudit(): void
    {
        static::creating(function ($model) {
            $userId = audit_user_id();
            if ($userId) {
                $model->created_by = $userId;
                $model->updated_by = $userId;
            }
        });

        static::updating(function ($model) {
            $userId = audit_user_id();
            if ($userId) {
                $model->updated_by = $userId;
            }
        });
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
