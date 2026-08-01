<?php

namespace App\Livewire\Admin;

use App\Models\Store;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layout')]
#[Title('Toko')]
class Stores extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public ?int $editingId = null;

    public string $name = '';

    public string $owner_name = '';

    public string $phone = '';

    public string $address = '';

    public string $lat = '';

    public string $lng = '';

    public bool $active = true;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function edit(int $id): void
    {
        $store = Store::findOrFail($id);

        $this->editingId = $store->id;
        $this->name = $store->name;
        $this->owner_name = $store->owner_name ?? '';
        $this->phone = $store->phone ?? '';
        $this->address = $store->address ?? '';
        $this->lat = (string) ($store->lat ?? '');
        $this->lng = (string) ($store->lng ?? '');
        $this->active = $store->active;
    }

    public function cancel(): void
    {
        $this->reset('editingId', 'name', 'owner_name', 'phone', 'address', 'lat', 'lng', 'active');
        $this->resetValidation();
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => 'required|string|max:120',
            'owner_name' => 'nullable|string|max:120',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:500',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'active' => 'boolean',
        ]);

        Store::findOrFail($this->editingId)->update([
            ...$data,
            'owner_name' => $data['owner_name'] ?: null,
            'phone' => $data['phone'] ?: null,
            'address' => $data['address'] ?: null,
            'lat' => $data['lat'] === '' ? null : $data['lat'],
            'lng' => $data['lng'] === '' ? null : $data['lng'],
        ]);

        $this->cancel();
        session()->flash('status', 'Toko diperbarui.');
    }

    public function render()
    {
        $stores = Store::query()
            ->with(['latestVisit.user', 'creator'])
            ->when($this->search !== '', fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('owner_name', 'like', "%{$this->search}%")
                    ->orWhere('address', 'like', "%{$this->search}%");
            }))
            ->orderBy('name')
            ->paginate(20);

        return view('livewire.admin.stores', ['stores' => $stores]);
    }
}
