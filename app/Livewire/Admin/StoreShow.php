<?php

namespace App\Livewire\Admin;

use App\Models\Store;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layout')]
#[Title('Detail Toko')]
class StoreShow extends Component
{
    use WithPagination;

    public Store $store;

    public function mount(Store $store): void
    {
        $this->store = $store;
    }

    public function render()
    {
        return view('livewire.admin.store-show', [
            'history' => $this->store->visits()
                ->with(['items.product', 'user'])
                ->latest('visited_at')
                ->paginate(15),
        ]);
    }
}
