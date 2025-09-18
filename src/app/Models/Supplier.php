<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasUuid;

class Supplier extends Model
{
    use HasUuid;

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
