<?php

namespace App\Livewire\Admin;

use App\Models\Store;
use App\Models\User;
use Illuminate\Validation\Rule;
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

    public ?int $movingId = null;

    public ?int $moveToId = null;

    public bool $trashed = false;

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

    public function startMove(int $id): void
    {
        $this->movingId = $id;
        $this->moveToId = null;
        $this->resetValidation();
    }

    public function cancelMove(): void
    {
        $this->movingId = null;
        $this->moveToId = null;
        $this->resetValidation();
    }

    public function moveStore(): void
    {
        $data = $this->validate([
            'moveToId' => ['required', Rule::exists('users', 'id')->where('role', 'sales')],
        ]);

        $store = Store::findOrFail($this->movingId);
        $to = User::where('role', 'sales')->findOrFail($data['moveToId']);

        $store->update(['created_by' => $to->id]);

        session()->flash('status', "Toko “{$store->name}” dipindahkan ke {$to->name}.");

        $this->cancelMove();
    }

    public function toggleTrashed(): void
    {
        $this->trashed = ! $this->trashed;
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $store = Store::findOrFail($id);
        $store->delete();

        session()->flash('status', "Toko \"{$store->name}\" dihapus.");
    }

    public function restore(int $id): void
    {
        $store = Store::onlyTrashed()->findOrFail($id);
        $store->restore();

        session()->flash('status', "Toko \"{$store->name}\" dipulihkan.");
    }

    public function render()
    {
        $stores = Store::query()
            ->when($this->trashed, fn ($q) => $q->onlyTrashed())
            ->with(['latestVisit.user', 'creator'])
            ->when($this->search !== '', fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('owner_name', 'like', "%{$this->search}%")
                    ->orWhere('address', 'like', "%{$this->search}%");
            }))
            ->orderBy('name')
            ->paginate(20);

        return view('livewire.admin.stores', [
            'stores' => $stores,
            'moveTargets' => $this->movingId
                ? User::where('role', 'sales')->orderBy('name')->get()
                : collect(),
        ]);
    }
}
