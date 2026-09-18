<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'sku', 'unit', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function visitItems(): HasMany
    {
        return $this->hasMany(VisitItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
