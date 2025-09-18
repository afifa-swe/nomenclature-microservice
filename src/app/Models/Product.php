<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasUuid;

class Product extends Model
{
    use HasUuid;

    protected $fillable = [
        'name',
        'description',
        'category_id',
        'supplier_id',
        'price',
        'file_url',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected static function booted()
    {
        static::addGlobalScope('active', function ($query) {
            $query->where('is_active', true);
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
