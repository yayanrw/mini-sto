<?php

namespace App\Livewire\Admin;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layout')]
#[Title('Produk')]
class Products extends Component
{
    use WithPagination;

    public ?int $editingId = null;

    public string $name = '';

    public string $sku = '';

    public string $unit = 'pcs';

    public bool $active = true;

    public bool $trashed = false;

    public function edit(int $id): void
    {
        $product = Product::findOrFail($id);

        $this->editingId = $product->id;
        $this->name = $product->name;
        $this->sku = $product->sku ?? '';
        $this->unit = $product->unit;
        $this->active = $product->active;
    }

    public function cancel(): void
    {
        $this->reset('editingId', 'name', 'sku', 'unit', 'active');
        $this->resetValidation();
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => 'required|string|max:120',
            'sku' => [
                'nullable', 'string', 'max:60',
                Rule::unique('products', 'sku')
                    ->ignore($this->editingId)
                    ->where(fn ($q) => $q->whereNull('deleted_at')),
            ],
            'unit' => 'required|string|max:20',
            'active' => 'boolean',
        ]);

        $data['sku'] = $data['sku'] ?: null;

        Product::updateOrCreate(['id' => $this->editingId], $data);

        $this->cancel();
        session()->flash('status', 'Produk tersimpan.');
    }

    public function toggleTrashed(): void
    {
        $this->trashed = ! $this->trashed;
        $this->resetPage();
    }

    /**
     * Produk hanya boleh dihapus kalau tidak ada sisa titipan di toko manapun —
     * kalau tidak, VisitForm gak akan merender baris hitungnya (produk hilang
     * dari $products), tapi RecordVisit::assertNothingLeftOut() tetap
     * mewajibkan produk itu dihitung ulang. Toko itu jadi buntu (gak bisa
     * submit kunjungan apa pun) sampai produk dipulihkan.
     */
    public function delete(int $id): void
    {
        $product = Product::findOrFail($id);

        $hasOutstandingStock = Store::with('latestVisit.items')->get()
            ->contains(fn (Store $store) => ($store->currentStock()[$product->id] ?? 0) > 0);

        if ($hasOutstandingStock) {
            session()->flash('error', "Produk \"{$product->name}\" masih punya sisa titipan di toko dan tidak bisa dihapus.");

            return;
        }

        $product->delete();

        session()->flash('status', "Produk \"{$product->name}\" dihapus.");
    }

    public function restore(int $id): void
    {
        $product = Product::onlyTrashed()->findOrFail($id);
        $product->restore();

        session()->flash('status', "Produk \"{$product->name}\" dipulihkan.");
    }

    public function render()
    {
        $products = Product::query()
            ->when($this->trashed, fn ($q) => $q->onlyTrashed())
            ->orderBy('name')
            ->paginate(20);

        return view('livewire.admin.products', [
            'products' => $products,
        ]);
    }
}
