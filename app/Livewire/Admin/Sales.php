<?php

namespace App\Livewire\Admin;

use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layout')]
#[Title('Sales')]
class Sales extends Component
{
    use WithPagination;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public bool $active = true;

    public ?int $movingFromId = null;

    public ?int $moveToId = null;

    public function edit(int $id): void
    {
        $user = User::where('role', 'sales')->findOrFail($id);

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone ?? '';
        $this->active = $user->active;
        $this->password = '';
    }

    public function cancel(): void
    {
        $this->reset('editingId', 'name', 'email', 'phone', 'password', 'active');
        $this->resetValidation();
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190|unique:users,email'.($this->editingId ? ",{$this->editingId}" : ''),
            'phone' => 'nullable|string|max:30',
            'active' => 'boolean',
            'password' => [$this->editingId ? 'nullable' : 'required', Password::min(8)],
        ]);

        if ($data['password'] === '' || $data['password'] === null) {
            unset($data['password']);
        }

        User::updateOrCreate(['id' => $this->editingId], [...$data, 'role' => 'sales']);

        session()->flash('status', 'Sales tersimpan.');

        $this->cancel();
    }

    public function startMove(int $id): void
    {
        $this->movingFromId = $id;
        $this->moveToId = null;
        $this->resetValidation();
    }

    public function cancelMove(): void
    {
        $this->movingFromId = null;
        $this->moveToId = null;
        $this->resetValidation();
    }

    public function moveStores(): void
    {
        $data = $this->validate([
            'moveToId' => [
                'required',
                'different:movingFromId',
                Rule::exists('users', 'id')->where('role', 'sales'),
            ],
        ]);

        $from = User::where('role', 'sales')->findOrFail($this->movingFromId);
        $to = User::where('role', 'sales')->findOrFail($data['moveToId']);

        $count = Store::where('created_by', $from->id)->update(['created_by' => $to->id]);

        session()->flash('status', $count > 0
            ? "{$count} toko dipindahkan dari {$from->name} ke {$to->name}."
            : "{$from->name} tidak punya toko untuk dipindahkan.");

        $this->cancelMove();
    }

    public function render()
    {
        $storeCounts = Store::whereNotNull('created_by')
            ->groupBy('created_by')
            ->pluck(DB::raw('COUNT(*)'), 'created_by');

        $sales = User::where('role', 'sales')->orderBy('name')->paginate(20);

        return view('livewire.admin.sales', [
            'sales' => $sales,
            'storeCounts' => $storeCounts,
            'moveTargets' => $this->movingFromId
                ? User::where('role', 'sales')->where('id', '!=', $this->movingFromId)->orderBy('name')->get()
                : collect(),
        ]);
    }
}
