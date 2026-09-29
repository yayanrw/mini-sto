<?php

namespace App\Reports;

use App\Models\Store;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Rollup superadmin: area -> koordinator -> sales. Tiga query agregat kecil
 * digabung di PHP; semua kolom select ada di GROUP BY (ONLY_FULL_GROUP_BY).
 */
class AreaRollup
{
    /** @param array{0: Carbon, 1: Carbon} $range */
    public static function rows(array $range): Collection
    {
        // Kunjungan dikelompokkan per area toko dan per sales pencatat.
        $visits = Visit::query()
            ->join('stores', 'stores.id', '=', 'visits.store_id')
            ->leftJoin('visit_items', 'visit_items.visit_id', '=', 'visits.id')
            ->whereBetween('visits.visited_at', $range)
            ->groupBy('stores.area', 'visits.user_id')
            ->get([
                'stores.area', 'visits.user_id',
                DB::raw('COUNT(DISTINCT visits.id) as visit_count'),
                DB::raw('COALESCE(SUM(visit_items.qty_sold), 0) as sold'),
            ]);

        $stores = Store::query()
            ->groupBy('area', 'created_by')
            ->get(['area', 'created_by', DB::raw('COUNT(*) as total')]);

        // Stok saat ini = qty_left dari kunjungan TERAKHIR tiap toko. Urut visited_at lalu id
        // supaya dua kunjungan dengan visited_at kembar tidak terhitung dua kali.
        // Alias `remaining`, bukan `left` (reserved word MySQL).
        $stock = DB::table('visit_items')
            ->join('visits', 'visits.id', '=', 'visit_items.visit_id')
            ->join('stores', 'stores.id', '=', 'visits.store_id')
            ->whereNull('stores.deleted_at')
            ->whereRaw('visits.id = (
                select v2.id from visits v2 where v2.store_id = visits.store_id
                order by v2.visited_at desc, v2.id desc limit 1)')
            ->groupBy('stores.area', 'stores.created_by')
            ->get(['stores.area', 'stores.created_by', DB::raw('SUM(visit_items.qty_left) as remaining')]);

        $users = User::whereIn('role', ['admin', 'sales'])->orderBy('name')->get();

        return $users->pluck('area')
            ->merge($stores->pluck('area'))->merge($visits->pluck('area'))
            ->unique()
            // Area null (tanpa area) selalu di akhir.
            ->sortBy(fn (?string $area) => $area === null ? "\u{10FFFF}" : $area)
            ->values()
            ->map(function (?string $area) use ($users, $visits, $stores, $stock) {
                $v = $visits->where('area', $area);
                $s = $stores->where('area', $area);
                $k = $stock->where('area', $area);

                return [
                    'area' => $area,
                    'koordinators' => $users->where('role', 'admin')->where('area', $area)->values(),
                    'stores' => (int) $s->sum('total'),
                    'visits' => (int) $v->sum('visit_count'),
                    'sold' => (int) $v->sum('sold'),
                    'remaining' => (int) $k->sum('remaining'),
                    'sales' => $users->where('role', 'sales')->where('area', $area)->values()
                        ->map(fn (User $u) => [
                            'user' => $u,
                            'stores' => (int) $s->where('created_by', $u->id)->sum('total'),
                            'visits' => (int) $v->where('user_id', $u->id)->sum('visit_count'),
                            'sold' => (int) $v->where('user_id', $u->id)->sum('sold'),
                            'remaining' => (int) $k->where('created_by', $u->id)->sum('remaining'),
                        ]),
                ];
            });
    }
}
