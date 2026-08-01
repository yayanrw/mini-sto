<?php

namespace App\Livewire\Sales;

use App\Models\Store;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layout')]
#[Title('Toko')]
class StoreList extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public ?float $lat = null;

    public ?float $lng = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function setLocation(float $lat, float $lng): void
    {
        $this->lat = $lat;
        $this->lng = $lng;
    }

    public function render()
    {
        $query = Store::query()
            ->where('active', true)
            ->with(['latestVisit']);

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('owner_name', 'like', "%{$this->search}%")
                    ->orWhere('address', 'like', "%{$this->search}%");
            });
        }

        if ($this->lat !== null && $this->lng !== null) {
            $query->nearest($this->lat, $this->lng);
        } else {
            $query->orderBy('name');
        }

        return view('livewire.sales.store-list', [
            'stores' => $query->paginate(20),
        ]);
    }
}
