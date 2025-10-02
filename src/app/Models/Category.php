<?php

namespace App\Models;

use App\Models\Traits\LogsChanges;
use App\Models\Traits\AutoOwners;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Traits\HasUuid;

class Category extends Model
{
    use HasUuid, HasFactory,LogsChanges,AutoOwners;

    // ensure UUID primary key handling
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'parent_id',
        'created_by',
        'updated_by',
    ];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }
}
