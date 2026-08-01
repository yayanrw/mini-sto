<?php

namespace App\Livewire\Admin;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Reports\VisitReport;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layout')]
#[Title('Report')]
class Reports extends Component
{
    use WithPagination;

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    #[Url]
    public string $user_id = '';

    #[Url]
    public string $product_id = '';

    #[Url]
    public string $store_id = '';

    public function mount(): void
    {
        $this->from = $this->from ?: now()->startOfMonth()->toDateString();
        $this->to = $this->to ?: now()->toDateString();
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function filters(): array
    {
        return [
            'from' => $this->from,
            'to' => $this->to,
            'user_id' => $this->user_id,
            'product_id' => $this->product_id,
            'store_id' => $this->store_id,
        ];
    }

    public function render()
    {
        return view('livewire.admin.reports', [
            'rows' => VisitReport::query($this->filters())->paginate(30),
            'totals' => VisitReport::totals($this->filters()),
            'salesUsers' => User::orderBy('name')->get(['id', 'name']),
            'products' => Product::orderBy('name')->get(['id', 'name']),
            'stores' => Store::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
