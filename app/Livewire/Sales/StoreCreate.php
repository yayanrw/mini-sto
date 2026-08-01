<?php

namespace App\Livewire\Sales;

use App\Models\Store;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layout')]
#[Title('Toko Baru')]
class StoreCreate extends Component
{
    use WithFileUploads;

    #[Validate('required|string|max:120')]
    public string $name = '';

    #[Validate('nullable|string|max:120')]
    public string $owner_name = '';

    #[Validate('nullable|string|max:30')]
    public string $phone = '';

    #[Validate('nullable|string|max:500')]
    public string $address = '';

    #[Validate('required|numeric|between:-90,90')]
    public ?string $lat = null;

    #[Validate('required|numeric|between:-180,180')]
    public ?string $lng = null;

    #[Validate('required|image|max:4096')]
    public $photo = null;

    public function setLocation(float $lat, float $lng): void
    {
        $this->lat = (string) round($lat, 7);
        $this->lng = (string) round($lng, 7);
    }

    public function save()
    {
        $data = $this->validate();

        $store = Store::create([
            'name' => $data['name'],
            'owner_name' => $data['owner_name'] ?: null,
            'phone' => $data['phone'] ?: null,
            'address' => $data['address'] ?: null,
            'lat' => $data['lat'] !== null && $data['lat'] !== '' ? $data['lat'] : null,
            'lng' => $data['lng'] !== null && $data['lng'] !== '' ? $data['lng'] : null,
            'photo_path' => $this->photo?->store('stores', 'public'),
            'created_by' => auth()->id(),
        ]);

        session()->flash('status', "Toko “{$store->name}” tersimpan. Lanjut catat titipan produk.");

        return $this->redirectRoute('visits.create', $store, navigate: true);
    }

    public function render()
    {
        return view('livewire.sales.store-create');
    }
}
