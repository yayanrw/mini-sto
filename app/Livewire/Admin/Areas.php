<?php

namespace App\Livewire\Admin;

use App\Reports\AreaRollup;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layout')]
#[Title('Rollup Area')]
class Areas extends Component
{
    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    public function mount(): void
    {
        $this->from = $this->from ?: now()->startOfMonth()->toDateString();
        $this->to = $this->to ?: now()->toDateString();
    }

    public function render()
    {
        return view('livewire.admin.areas', [
            'rows' => AreaRollup::rows([
                Carbon::parse($this->from)->startOfDay(),
                Carbon::parse($this->to)->endOfDay(),
            ]),
        ]);
    }
}
