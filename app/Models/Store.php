<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Store extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'owner_name', 'phone', 'address',
        'lat', 'lng', 'photo_path', 'created_by', 'active',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lng' => 'float',
            'active' => 'boolean',
        ];
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function latestVisit(): HasOne
    {
        return $this->hasOne(Visit::class)->latestOfMany('visited_at');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Stok titipan saat ini: product_id => qty.
     *
     * Kunjungan terakhir selalu merupakan snapshot lengkap — RecordVisit
     * mewajibkan setiap produk dengan sisa > 0 ikut tercatat ulang.
     */
    public function currentStock(): Collection
    {
        $visit = $this->relationLoaded('latestVisit') ? $this->latestVisit : $this->latestVisit()->with('items')->first();

        return $visit
            ? $visit->items->pluck('qty_left', 'product_id')
            : collect();
    }

    /** Urutkan berdasar jarak dari titik GPS (haversine, km). */
    public function scopeNearest(Builder $query, float $lat, float $lng): Builder
    {
        return $query
            ->selectRaw(
                '*, (6371 * acos(least(1, cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) + sin(radians(?)) * sin(radians(lat))))) as distance_km',
                [$lat, $lng, $lat]
            )
            ->whereNotNull('lat')
            ->orderBy('distance_km');
    }
}
