<?php

namespace App\Models\Traits;

use App\Models\ChangeLog;
use Illuminate\Support\Facades\Auth;

trait LogsChanges
{
    public static function bootLogsChanges(): void
    {
        static::created(function ($model) {
            ChangeLog::create([
                'user_id' => Auth::id() ?? null,
                'entity_type' => $model->getTable(),
                'entity_id' => $model->id,
                'action' => 'created',
                'changes' => $model->getAttributes(),
            ]);
        });

        static::updated(function ($model) {
            ChangeLog::create([
                'user_id' => Auth::id() ?? null,
                'entity_type' => $model->getTable(),
                'entity_id' => $model->id,
                'action' => 'updated',
                'changes' => $model->getChanges(),
            ]);
        });

        static::deleted(function ($model) {
            ChangeLog::create([
                'user_id' => Auth::id() ?? null,
                'entity_type' => $model->getTable(),
                'entity_id' => $model->id,
                'action' => 'deleted',
                'changes' => [],
            ]);
        });
    }
}
