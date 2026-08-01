<?php

namespace App\Livewire\Sales;

use App\Actions\RecordVisit;
use App\Models\Product;
use App\Models\Store;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layout')]
#[Title('Kunjungan')]
class VisitForm extends Component
{
    public Store $store;

    /** product_id => qty_left kunjungan sebelumnya */
    public array $baseline = [];

    /** product_id => ['qty_found' => string, 'qty_added' => string] */
    public array $items = [];

    public string $note = '';

    public ?float $lat = null;

    public ?float $lng = null;

    /** product_id => bool, apakah checkbox "tambah titipan" dicentang */
    public array $restocking = [];

    public function mount(Store $store): void
    {
        $this->store = $store;
        $this->baseline = $store->currentStock()->map(fn ($qty) => (int) $qty)->all();

        foreach ($this->baseline as $productId => $qty) {
            if ($qty > 0) {
                $this->items[$productId] = ['qty_found' => '', 'qty_added' => ''];
            }
        }
    }

    public function setLocation(float $lat, float $lng): void
    {
        $this->lat = $lat;
        $this->lng = $lng;
    }

    public function toggleRestock(int $productId): void
    {
        $nowChecked = ! ($this->restocking[$productId] ?? false);
        $this->restocking[$productId] = $nowChecked;

        if ($nowChecked) {
            if (! isset($this->items[$productId])) {
                $this->items[$productId] = ['qty_found' => '', 'qty_added' => ''];
            }

            return;
        }

        if (isset($this->items[$productId])) {
            $this->items[$productId]['qty_added'] = '';
        }

        if (($this->baseline[$productId] ?? 0) === 0) {
            unset($this->items[$productId], $this->restocking[$productId]);
        }
    }

    public function save(RecordVisit $recordVisit)
    {
        $lines = [];

        foreach ($this->items as $productId => $row) {
            $lines[] = [
                'product_id' => $productId,
                'qty_found' => $row['qty_found'] === '' ? 0 : $row['qty_found'],
                'qty_added' => $row['qty_added'] === '' ? 0 : $row['qty_added'],
            ];
        }

        $visit = $recordVisit(
            $this->store,
            auth()->user(),
            $lines,
            ['lat' => $this->lat, 'lng' => $this->lng],
            $this->note ?: null,
        );

        $sold = $visit->items->sum('qty_sold');
        $added = $visit->items->sum('qty_added');

        session()->flash('status', "Kunjungan tersimpan. Terjual {$sold}, titipan baru {$added}.");

        return $this->redirectRoute('visits.index', navigate: true);
    }

    public function render()
    {
        $products = Product::active()->orderBy('name')->get()->keyBy('id');

        return view('livewire.sales.visit-form', [
            'products' => $products,
        ]);
    }
}
