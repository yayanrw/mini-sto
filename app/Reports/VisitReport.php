<?php

namespace App\Reports;

use App\Models\VisitItem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Query rekap kunjungan. Dipakai bersama oleh halaman report dan export CSV
 * supaya angka di layar dan di file tidak pernah beda.
 */
class VisitReport
{
    /** @param array{from?:string, to?:string, user_id?:string|int, product_id?:string|int, store_id?:string|int} $filters */
    public static function query(array $filters): Builder
    {
        return static::base($filters)
            ->orderByDesc('visits.visited_at')
            ->select([
                'visit_items.*',
                'visits.visited_at',
                'stores.name as store_name',
                'products.name as product_name',
                'products.unit as product_unit',
                'users.name as sales_name',
            ]);
    }

    /**
     * Total agregat dengan filter yang sama. Query terpisah, bukan clone dari
     * query(): daftar kolom mentahnya melanggar ONLY_FULL_GROUP_BY di MySQL.
     */
    public static function totals(array $filters): object
    {
        // Sengaja tanpa total qty_left: menjumlahkannya lintas kunjungan tidak
        // berarti apa-apa (stok yang sama dihitung berkali-kali).
        return static::base($filters)->selectRaw(
            'COALESCE(SUM(qty_sold), 0) as sold,
             COALESCE(SUM(qty_added), 0) as added,
             COUNT(DISTINCT visits.id) as visit_count'
        )->first();
    }

    private static function base(array $filters): Builder
    {
        $from = Carbon::parse($filters['from'] ?? now()->startOfMonth())->startOfDay();
        $to = Carbon::parse($filters['to'] ?? now())->endOfDay();

        return VisitItem::query()
            ->join('visits', 'visits.id', '=', 'visit_items.visit_id')
            ->join('stores', 'stores.id', '=', 'visits.store_id')
            ->join('products', 'products.id', '=', 'visit_items.product_id')
            ->join('users', 'users.id', '=', 'visits.user_id')
            ->whereBetween('visits.visited_at', [$from, $to])
            ->when($filters['user_id'] ?? null, fn ($q, $id) => $q->where('visits.user_id', $id))
            ->when($filters['product_id'] ?? null, fn ($q, $id) => $q->where('visit_items.product_id', $id))
            ->when($filters['store_id'] ?? null, fn ($q, $id) => $q->where('visits.store_id', $id));
    }
}
