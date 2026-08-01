<?php

namespace App\Livewire\Admin;

use App\Models\Store;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layout')]
#[Title('Peta Toko')]
class MapView extends Component
{
    public function render()
    {
        $markers = Store::query()
            ->where('active', true)
            ->whereNotNull('lat')
            ->with(['latestVisit.items.product', 'latestVisit.user'])
            ->get()
            ->map(fn (Store $store) => [
                'id' => $store->id,
                'name' => $store->name,
                'owner' => $store->owner_name,
                'address' => $store->address,
                'lat' => $store->lat,
                'lng' => $store->lng,
                'last_visit' => $store->latestVisit?->visited_at->translatedFormat('d M Y'),
                'last_sales' => $store->latestVisit?->user->name,
                'stock' => $store->latestVisit
                    ? $store->latestVisit->items
                        ->filter(fn ($item) => $item->qty_left > 0)
                        ->map(fn ($item) => "{$item->product->name}: {$item->qty_left}")
                        ->values()
                    : [],
            ]);

        return view('livewire.admin.map-view', [
            'markers' => $markers,
            'missingCoords' => Store::where('active', true)->whereNull('lat')->count(),
        ]);
    }
}
