<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\ActiveStoreScope;
use Illuminate\Database\Eloquent\Builder;

class Store extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
    protected static function booted(): void
    {
        static::addGlobalScope(new ActiveStoreScope);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function scopeOfType(
        Builder $query,
        string $type
    ): Builder {
        return $query->where('type', $type);
    }

    public function scopeForAdministration(
        Builder $query
    ): Builder {
        return $query->withoutGlobalScope(
            ActiveStoreScope::class
        );
    }
}
