<?php

namespace App\Livewire\Sales;

use App\Models\Store;
use App\Models\User;
use Illuminate\Validation\Rule;
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

    public ?int $assignedTo = null;

    public function mount(?Store $store = null): void
    {
        if (! $store) {
            return;
        }

        abort_unless($store->created_by === auth()->id() || auth()->user()->canAccessArea($store->area), 403);

        $this->editing = $store;
        $this->name = $store->name;
        $this->owner_name = $store->owner_name ?? '';
        $this->phone = $store->phone ?? '';
        $this->address = $store->address ?? '';
        $this->lat = $store->lat !== null ? (string) $store->lat : null;
        $this->lng = $store->lng !== null ? (string) $store->lng : null;
        $this->assignedTo = $store->creator?->role === 'sales' ? $store->created_by : null;
    }

    public function setLocation(float $lat, float $lng): void
    {
        $this->lat = (string) round($lat, 7);
        $this->lng = (string) round($lng, 7);
    }

    protected function rules(): array
    {
        $isAdmin = auth()->user()->isAdmin();
        $required = ($this->editing || $isAdmin) ? 'nullable' : 'required';

        $rules = [
            'name' => 'required|string|max:120',
            'owner_name' => 'nullable|string|max:120',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:500',
            'lat' => "{$required}|numeric|between:-90,90",
            'lng' => "{$required}|numeric|between:-180,180",
            'photo' => "{$required}|image|max:4096",
        ];

        if ($isAdmin) {
            $rules['assignedTo'] = ['required', Rule::exists('users', 'id')->where('role', 'sales')];
        }

        return $rules;
    }

    public function save()
    {
        $data = $this->validate();
        $user = auth()->user();
        $isAdmin = $user->isAdmin();

        $attributes = [
            'name' => $data['name'],
            'owner_name' => $data['owner_name'] ?: null,
            'phone' => $data['phone'] ?: null,
            'address' => $data['address'] ?: null,
            'lat' => $data['lat'] !== null && $data['lat'] !== '' ? $data['lat'] : null,
            'lng' => $data['lng'] !== null && $data['lng'] !== '' ? $data['lng'] : null,
        ];

        $assignee = null;

        if ($isAdmin) {
            $assignee = User::where('role', 'sales')->findOrFail($data['assignedTo']);

            // Koordinator hanya boleh menugaskan toko ke sales di areanya sendiri.
            abort_unless($user->canAccessArea($assignee->area), 403);

            $attributes['created_by'] = $assignee->id;
        }

        if ($this->photo) {
            $attributes['photo_path'] = $this->photo->store('stores', 'public');
        }

        if ($this->editing) {
            // Area toko ikut sales baru hanya saat toko benar-benar berpindah tangan;
            // mengubah area sales tidak menggeser riwayat toko lamanya.
            if ($assignee && $this->editing->created_by !== $assignee->id) {
                $attributes['area'] = $assignee->area;
            }

            $this->editing->update($attributes);

            session()->flash('status', "Toko “{$this->editing->name}” diperbarui.");

            return $isAdmin
                ? $this->redirectRoute('admin.stores.show', $this->editing, navigate: true)
                : $this->redirectRoute('visits.create', $this->editing, navigate: true);
        }

        $store = Store::create([
            ...$attributes,
            'created_by' => $attributes['created_by'] ?? auth()->id(),
            'area' => $assignee ? $assignee->area : $user->area,
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
        return view('livewire.sales.store-create', [
            'salesOptions' => auth()->user()->isAdmin()
                ? User::visibleTo(auth()->user())->where('role', 'sales')->orderBy('name')->get()
                : collect(),
        ]);
    }
}
