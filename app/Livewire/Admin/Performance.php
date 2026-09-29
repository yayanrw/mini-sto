<?php

namespace App\Livewire\Admin;

use App\Models\Store;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layout')]
#[Title('Performa Sales')]
class Performance extends Component
{
    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    #[Url]
    public string $area = '';

    public function mount(): void
    {
        $this->from = $this->from ?: now()->startOfMonth()->toDateString();
        $this->to = $this->to ?: now()->toDateString();
    }

    public function render()
    {
        $range = [
            Carbon::parse($this->from)->startOfDay(),
            Carbon::parse($this->to)->endOfDay(),
        ];

        $viewer = auth()->user();
        // Filter area hanya bermakna untuk superadmin; koordinator sudah terbatas oleh visibleTo.
        $area = $viewer->isSuperadmin() ? $this->area : '';

        $visitStats = Visit::query()
            ->join('stores', 'stores.id', '=', 'visits.store_id')
            ->leftJoin('visit_items', 'visit_items.visit_id', '=', 'visits.id')
            ->whereBetween('visits.visited_at', $range)
            ->whereIn('visits.store_id', Store::visibleIds($viewer))
            ->when($area !== '', fn ($q) => $q->where('stores.area', $area))
            ->groupBy('visits.user_id')
            ->get([
                'visits.user_id',
                DB::raw('COUNT(DISTINCT visits.id) as visit_count'),
                DB::raw('COUNT(DISTINCT visits.store_id) as store_count'),
                DB::raw('COALESCE(SUM(visit_items.qty_sold), 0) as sold'),
                DB::raw('COALESCE(SUM(visit_items.qty_added), 0) as added'),
            ])
            ->keyBy('user_id');

        $newStores = Store::query()
            ->visibleTo($viewer)
            ->when($area !== '', fn ($q) => $q->where('stores.area', $area))
            ->whereBetween('created_at', $range)
            ->whereNotNull('created_by')
            ->groupBy('created_by')
            ->pluck(DB::raw('COUNT(*)'), 'created_by');

        $rows = User::query()
            ->visibleTo($viewer)
            ->when($area !== '', fn ($q) => $q->where('users.area', $area))
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'user' => $user,
                'visits' => (int) ($visitStats[$user->id]->visit_count ?? 0),
                'stores' => (int) ($visitStats[$user->id]->store_count ?? 0),
                'sold' => (int) ($visitStats[$user->id]->sold ?? 0),
                'added' => (int) ($visitStats[$user->id]->added ?? 0),
                'new_stores' => (int) ($newStores[$user->id] ?? 0),
            ])
            ->filter(fn ($row) => $row['user']->role === 'sales' || $row['visits'] > 0)
            ->sortByDesc('sold');

        return view('livewire.admin.performance', ['rows' => $rows]);
    }
}
