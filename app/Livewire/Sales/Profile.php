<?php

namespace App\Livewire\Sales;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layout')]
#[Title('Profil')]
class Profile extends Component
{
    public string $name = '';

    public string $phone = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $this->name = auth()->user()->name;
        $this->phone = auth()->user()->phone ?? '';
    }

    public function saveProfile(): void
    {
        $data = $this->validate([
            'name' => 'required|string|max:120',
            'phone' => 'nullable|string|max:30',
        ]);

        auth()->user()->update($data);

        session()->flash('status', 'Profil diperbarui.');
    }

    public function changePassword(): void
    {
        $this->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.current_password' => 'Kata sandi lama salah.',
        ]);

        auth()->user()->update(['password' => Hash::make($this->password)]);

        $this->reset('current_password', 'password', 'password_confirmation');
        session()->flash('status', 'Kata sandi diganti.');
    }

    public function render()
    {
        return view('livewire.sales.profile');
    }
}
