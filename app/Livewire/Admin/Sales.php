<?php

namespace App\Livewire\Admin;

use App\Models\Store;
use App\Models\User;
use App\Support\Areas;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layout')]
#[Title('Sales')]
class Sales extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $filterArea = '';

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $area = '';

    public bool $active = true;

    public ?int $movingFromId = null;

    public ?int $moveToId = null;

    public function updatedFilterArea(): void
    {
        $this->resetPage();
    }

    public function edit(int $id): void
    {
        $user = User::where('role', 'sales')->findOrFail($id);
        abort_unless(auth()->user()->canAccessArea($user->area), 403);

        $this->editingId = $user->id;
        $this->area = $user->area ?? '';
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone ?? '';
        $this->active = $user->active;
        $this->password = '';
    }

    public function cancel(): void
    {
        $this->reset('editingId', 'name', 'email', 'phone', 'password', 'area', 'active');
        $this->resetValidation();
    }

    public function save(): void
    {
        $viewer = auth()->user();

        // Koordinator tanpa area belum bisa mengelola sales siapa pun.
        abort_if(! $viewer->isSuperadmin() && $viewer->area === null, 403);

        // editingId adalah properti publik yang bisa dimanipulasi client: cek ulang di sini.
        if ($this->editingId) {
            abort_unless($viewer->canAccessArea(User::where('role', 'sales')->findOrFail($this->editingId)->area), 403);
        }

        $rules = [
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190|unique:users,email'.($this->editingId ? ",{$this->editingId}" : ''),
            'phone' => 'nullable|string|max:30',
            'active' => 'boolean',
            'password' => [$this->editingId ? 'nullable' : 'required', Password::min(8)],
        ];

        if ($viewer->isSuperadmin()) {
            $rules['area'] = ['nullable', Areas::rule()];
        }

        $data = $this->validate($rules);

        // Koordinator: area dipaksa area sendiri; input area dari client diabaikan.
        $data['area'] = $viewer->isSuperadmin() ? ($data['area'] ?: null) : $viewer->area;

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

        $viewer = auth()->user();
        $from = User::visibleTo($viewer)->where('role', 'sales')->findOrFail($this->movingFromId);
        $to = User::visibleTo($viewer)->where('role', 'sales')->findOrFail($data['moveToId']);

        // visibleTo pada toko: koordinator tidak bisa menarik toko berarea lain lewat pemindahan massal.
        $count = Store::visibleTo($viewer)->where('created_by', $from->id)
            ->update(['created_by' => $to->id, 'area' => $to->area]);

        session()->flash('status', $count > 0
            ? "{$count} toko dipindahkan dari {$from->name} ke {$to->name}."
            : "{$from->name} tidak punya toko untuk dipindahkan.");

        $this->cancelMove();
    }

    public function render()
    {
        $viewer = auth()->user();

        $storeCounts = Store::visibleTo($viewer)->whereNotNull('created_by')
            ->groupBy('created_by')
            ->pluck(DB::raw('COUNT(*)'), 'created_by');

        $sales = User::visibleTo($viewer)->where('role', 'sales')
            // Filter area hanya bermakna untuk superadmin; koordinator sudah terbatas oleh visibleTo.
            ->when($viewer->isSuperadmin() && $this->filterArea !== '', fn ($q) => $q->where('users.area', $this->filterArea))
            ->orderBy('name')->paginate(20);

        return view('livewire.admin.sales', [
            'sales' => $sales,
            'storeCounts' => $storeCounts,
            'moveTargets' => $this->movingFromId
                ? User::visibleTo($viewer)->where('role', 'sales')->where('id', '!=', $this->movingFromId)->orderBy('name')->get()
                : collect(),
        ]);
    }
}
