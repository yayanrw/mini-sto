<?php

namespace App\Livewire\Admin;

use App\Models\Store;
use App\Models\User;
use App\Models\Visit;
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

    public function mount(): void
    {
        $this->from = $this->from ?: now()->startOfMonth()->toDateString();
        $this->to = $this->to ?: now()->toDateString();
    }

    public function render()
    {
        $range = [
            \Carbon\Carbon::parse($this->from)->startOfDay(),
            \Carbon\Carbon::parse($this->to)->endOfDay(),
        ];

        $visitStats = Visit::query()
            ->leftJoin('visit_items', 'visit_items.visit_id', '=', 'visits.id')
            ->whereBetween('visits.visited_at', $range)
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
            ->whereBetween('created_at', $range)
            ->whereNotNull('created_by')
            ->groupBy('created_by')
            ->pluck(DB::raw('COUNT(*)'), 'created_by');

        $rows = User::query()
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
