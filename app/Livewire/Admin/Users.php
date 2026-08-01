<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layout')]
#[Title('Pengguna')]
class Users extends Component
{
    use WithPagination;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $role = 'sales';

    public string $password = '';

    public bool $active = true;

    public function edit(int $id): void
    {
        $user = User::findOrFail($id);

        // Admin biasa tidak boleh mengubah akun superadmin.
        if ($user->isSuperadmin() && ! auth()->user()->isSuperadmin()) {
            session()->flash('status', 'Tidak boleh mengubah akun superadmin.');

            return;
        }

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone ?? '';
        $this->role = $user->role;
        $this->active = $user->active;
        $this->password = '';
    }

    /** Role yang boleh diberikan pengguna saat ini. Admin biasa tidak boleh membuat/menaikkan siapa pun jadi superadmin. */
    protected function assignableRoles(): array
    {
        return auth()->user()->isSuperadmin() ? ['sales', 'admin', 'superadmin'] : ['sales', 'admin'];
    }

    public function cancel(): void
    {
        $this->reset('editingId', 'name', 'email', 'phone', 'role', 'password', 'active');
        $this->resetValidation();
    }

    public function save(): void
    {
        // Jaga-jaga kalau editingId diarahkan ke superadmin oleh admin biasa (defense in depth,
        // di luar guard yang sudah ada di edit()).
        if ($this->editingId) {
            $target = User::find($this->editingId);

            if ($target && $target->isSuperadmin() && ! auth()->user()->isSuperadmin()) {
                abort(403);
            }
        }

        $data = $this->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190|unique:users,email'.($this->editingId ? ",{$this->editingId}" : ''),
            'phone' => 'nullable|string|max:30',
            'role' => ['required', Rule::in($this->assignableRoles())],
            'active' => 'boolean',
            'password' => [$this->editingId ? 'nullable' : 'required', Password::min(8)],
        ]);

        if ($data['password'] === '' || $data['password'] === null) {
            unset($data['password']);
        }

        $user = User::updateOrCreate(['id' => $this->editingId], $data);

        // Jangan biarkan superadmin terakhir mengunci dirinya sendiri.
        if ($user->is(auth()->user()) && (! $user->isSuperadmin() || ! $user->active)) {
            $user->update(['role' => 'superadmin', 'active' => true]);
            session()->flash('status', 'Akun sendiri tidak bisa diturunkan atau dinonaktifkan.');
        } else {
            session()->flash('status', 'Pengguna tersimpan.');
        }

        $this->cancel();
    }

    public function render()
    {
        return view('livewire.admin.users', [
            'users' => User::orderBy('name')->paginate(20),
        ]);
    }
}
