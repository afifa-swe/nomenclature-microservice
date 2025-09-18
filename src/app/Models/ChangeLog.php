<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasUuid;

class ChangeLog extends Model
{
    use HasUuid;

    protected $fillable = [
        'user_id',
        'entity_type',
        'entity_id',
        'action',
        'changes',
    ];

    protected $casts = [
        'changes' => 'array',
        'created_at' => 'datetime',
    ];

    public $timestamps = false;
}
