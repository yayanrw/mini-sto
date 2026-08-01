<?php

namespace App\Livewire\Admin;

use App\Models\Store;
use App\Models\Visit;
use App\Models\VisitItem;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layout')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render()
    {
        $monthStart = now()->startOfMonth();

        $monthly = VisitItem::query()
            ->join('visits', 'visits.id', '=', 'visit_items.visit_id')
            ->where('visits.visited_at', '>=', $monthStart)
            ->selectRaw('COALESCE(SUM(qty_sold), 0) as sold, COALESCE(SUM(qty_added), 0) as added')
            ->first();

        $topProducts = VisitItem::query()
            ->join('visits', 'visits.id', '=', 'visit_items.visit_id')
            ->join('products', 'products.id', '=', 'visit_items.product_id')
            ->where('visits.visited_at', '>=', $monthStart)
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('sold')
            ->limit(5)
            ->get(['products.name', DB::raw('SUM(qty_sold) as sold')]);

        $stale = Store::query()
            ->where('active', true)
            ->with('latestVisit')
            ->get()
            ->map(fn ($store) => [
                'store' => $store,
                'days' => $store->latestVisit?->visited_at->diffInDays(now()),
            ])
            ->filter(fn ($row) => $row['days'] === null || $row['days'] >= 30)
            ->sortByDesc(fn ($row) => $row['days'] ?? PHP_INT_MAX)
            ->take(10);

        return view('livewire.admin.dashboard', [
            'totalStores' => Store::where('active', true)->count(),
            'newStores' => Store::where('created_at', '>=', $monthStart)->count(),
            'visitCount' => Visit::where('visited_at', '>=', $monthStart)->count(),
            'sold' => (int) $monthly->sold,
            'added' => (int) $monthly->added,
            'topProducts' => $topProducts,
            'stale' => $stale,
        ]);
    }
}
