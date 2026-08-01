<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_id', 'product_id', 'qty_before', 'qty_found', 'qty_sold', 'qty_added', 'qty_left',
    ];

    protected function casts(): array
    {
        // visited_at bukan kolom tabel ini, tapi ikut ter-select di query report
        // (VisitReport). Cast di sini supaya tetap jadi Carbon, bukan string.
        return ['visited_at' => 'datetime'];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
