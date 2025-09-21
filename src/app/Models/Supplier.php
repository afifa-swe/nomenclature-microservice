<?php

namespace App\Models;

use App\Models\Traits\LogsChanges;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasUuid;

class Supplier extends Model
{
    use HasUuid,HasFactory,LogsChanges;

    // ensure UUID primary key handling
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'phone',
        'contact_name',
        'website',
        'description',
        'created_by',
        'updated_by',
    ];
}
