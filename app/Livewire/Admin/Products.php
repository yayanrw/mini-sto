<?php

namespace App\Livewire\Admin;

use App\Models\Product;
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
            'sku' => 'nullable|string|max:60|unique:products,sku'.($this->editingId ? ",{$this->editingId}" : ''),
            'unit' => 'required|string|max:20',
            'active' => 'boolean',
        ]);

        $data['sku'] = $data['sku'] ?: null;

        Product::updateOrCreate(['id' => $this->editingId], $data);

        $this->cancel();
        session()->flash('status', 'Produk tersimpan.');
    }

    public function render()
    {
        return view('livewire.admin.products', [
            'products' => Product::orderBy('name')->paginate(20),
        ]);
    }
}
