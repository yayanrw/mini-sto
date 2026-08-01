<?php

namespace App\Livewire\Sales;

use App\Models\Visit;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layout')]
#[Title('Kunjungan')]
class VisitHistory extends Component
{
    use WithPagination;

    public function render()
    {
        return view('livewire.sales.visit-history', [
            'visits' => Visit::query()
                ->where('user_id', auth()->id())
                ->with(['store', 'items.product'])
                ->latest('visited_at')
                ->paginate(15),
        ]);
    }
}
