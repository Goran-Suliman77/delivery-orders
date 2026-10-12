<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    //
    use HasFactory;
    protected $fillable = [
        'store_id',
        'name',
        'price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }


    public function scopeActive(Builder $query): Builder
{
    return $query->where('is_active', true);
}

public function scopeForStore(
    Builder $query,
    int $storeId
): Builder {
    return $query->where('store_id', $storeId);
}

public function scopeAvailable(Builder $query): Builder
{
    return $query
        ->active()
        ->whereHas('store');
}
}
