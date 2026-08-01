<?php

namespace App\Livewire\Sales;

use App\Models\Store;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layout')]
#[Title('Toko')]
class StoreCreate extends Component
{
    use WithFileUploads;

    public ?Store $editing = null;

    public string $name = '';

    public string $owner_name = '';

    public string $phone = '';

    public string $address = '';

    public ?string $lat = null;

    public ?string $lng = null;

    public $photo = null;

    public function mount(?Store $store = null): void
    {
        if (! $store) {
            return;
        }

        abort_unless($store->created_by === auth()->id() || auth()->user()->isAdmin(), 403);

        $this->editing = $store;
        $this->name = $store->name;
        $this->owner_name = $store->owner_name ?? '';
        $this->phone = $store->phone ?? '';
        $this->address = $store->address ?? '';
        $this->lat = $store->lat !== null ? (string) $store->lat : null;
        $this->lng = $store->lng !== null ? (string) $store->lng : null;
    }

    public function setLocation(float $lat, float $lng): void
    {
        $this->lat = (string) round($lat, 7);
        $this->lng = (string) round($lng, 7);
    }

    protected function rules(): array
    {
        $required = ($this->editing || auth()->user()->isAdmin()) ? 'nullable' : 'required';

        return [
            'name' => 'required|string|max:120',
            'owner_name' => 'nullable|string|max:120',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:500',
            'lat' => "{$required}|numeric|between:-90,90",
            'lng' => "{$required}|numeric|between:-180,180",
            'photo' => "{$required}|image|max:4096",
        ];
    }

    public function save()
    {
        $data = $this->validate();

        $attributes = [
            'name' => $data['name'],
            'owner_name' => $data['owner_name'] ?: null,
            'phone' => $data['phone'] ?: null,
            'address' => $data['address'] ?: null,
            'lat' => $data['lat'] !== null && $data['lat'] !== '' ? $data['lat'] : null,
            'lng' => $data['lng'] !== null && $data['lng'] !== '' ? $data['lng'] : null,
        ];

        if ($this->photo) {
            $attributes['photo_path'] = $this->photo->store('stores', 'public');
        }

        $isAdmin = auth()->user()->isAdmin();

        if ($this->editing) {
            $this->editing->update($attributes);

            session()->flash('status', "Toko “{$this->editing->name}” diperbarui.");

            return $isAdmin
                ? $this->redirectRoute('admin.stores.show', $this->editing, navigate: true)
                : $this->redirectRoute('visits.create', $this->editing, navigate: true);
        }

        $store = Store::create([
            ...$attributes,
            'created_by' => auth()->id(),
        ]);

        if ($isAdmin) {
            session()->flash('status', "Toko “{$store->name}” tersimpan.");

            return $this->redirectRoute('admin.stores.show', $store, navigate: true);
        }

        session()->flash('status', "Toko “{$store->name}” tersimpan. Lanjut catat titipan produk.");

        return $this->redirectRoute('visits.create', $store, navigate: true);
    }

    public function render()
    {
        return view('livewire.sales.store-create');
    }
}
