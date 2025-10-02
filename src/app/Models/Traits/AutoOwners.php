<?php

namespace App\Models\Traits;

trait AutoOwners
{
    protected static function bootAutoOwners(): void
    {
        static::creating(function ($model) {
            if (in_array('created_by', $model->getFillable(), true)) {
                $model->created_by = auth()->id() ?? $model->created_by ?? null;
            }
            if (in_array('updated_by', $model->getFillable(), true)) {
                $model->updated_by = auth()->id() ?? $model->updated_by ?? null;
            }
        });

        static::updating(function ($model) {
            if (in_array('updated_by', $model->getFillable(), true)) {
                $model->updated_by = auth()->id() ?? $model->updated_by ?? null;
            }
        });
    }
}
