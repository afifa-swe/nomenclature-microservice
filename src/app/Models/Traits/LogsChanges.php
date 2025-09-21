<?php

namespace App\Models\Traits;

use App\Models\ChangeLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

trait LogsChanges
{
    public static function bootLogsChanges(): void
    {
        $isUuid = function ($value) {
            return is_string($value) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value);
        };

        static::created(function ($model) use ($isUuid) {
            $userId = auth()->id() ?? null;
            if (! $isUuid($userId)) {
                $userId = null;
            }

            $entityId = (string) $model->getKey();
            if (! $isUuid($entityId)) {
                // ensure entity id is string; if not UUID, try to cast to string
                $entityId = (string) $entityId;
            }

            ChangeLog::create([
                'user_id' => $userId,
                'entity_type' => $model->getTable(),
                'entity_id' => $entityId,
                'action' => 'created',
                'changes' => $model->getAttributes(),
            ]);
        });

        static::updated(function ($model) use ($isUuid) {
            $userId = auth()->id() ?? null;
            if (! $isUuid($userId)) {
                $userId = null;
            }

            $entityId = (string) $model->getKey();
            if (! $isUuid($entityId)) {
                $entityId = (string) $entityId;
            }

            ChangeLog::create([
                'user_id' => $userId,
                'entity_type' => $model->getTable(),
                'entity_id' => $entityId,
                'action' => 'updated',
                'changes' => $model->getChanges(),
            ]);
        });

        static::deleted(function ($model) use ($isUuid) {
            $userId = auth()->id() ?? null;
            if (! $isUuid($userId)) {
                $userId = null;
            }

            $entityId = (string) $model->getKey();
            if (! $isUuid($entityId)) {
                $entityId = (string) $entityId;
            }

            ChangeLog::create([
                'user_id' => $userId,
                'entity_type' => $model->getTable(),
                'entity_id' => $entityId,
                'action' => 'deleted',
                'changes' => [],
            ]);
        });
    }
}
