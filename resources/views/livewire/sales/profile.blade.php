<div class="space-y-3">
    <h1 class="text-2xl font-bold tracking-tight">Profil</h1>

    @if (session('status'))
        <p class="rounded-xl bg-daun/10 border border-daun/25 text-daun text-sm px-3 py-2">{{ session('status') }}</p>
    @endif

    <form wire:submit="saveProfile" class="kartu space-y-4">
        <div>
            <label class="block text-sm font-medium mb-1.5">Nama</label>
            <input wire:model="name" type="text" class="isian">
            @error('name') <p class="mt-1 text-sm text-bata">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium mb-1.5">No. HP</label>
            <input wire:model="phone" type="tel" class="isian">
        </div>
        <p class="label-kecil">{{ auth()->user()->email }} · {{ ucfirst(auth()->user()->role) }}</p>
        <button class="tombol tombol-utama w-full">Simpan profil</button>
    </form>

    <form wire:submit="changePassword" class="kartu space-y-4">
        <p class="font-display font-bold">Ganti kata sandi</p>
        <div>
            <label class="block text-sm font-medium mb-1.5">Kata sandi lama</label>
            <input wire:model="current_password" type="password" autocomplete="current-password" class="isian">
            @error('current_password') <p class="mt-1 text-sm text-bata">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium mb-1.5">Kata sandi baru</label>
            <input wire:model="password" type="password" autocomplete="new-password" class="isian">
            @error('password') <p class="mt-1 text-sm text-bata">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium mb-1.5">Ulangi kata sandi baru</label>
            <input wire:model="password_confirmation" type="password" autocomplete="new-password" class="isian">
        </div>
        <button class="tombol w-full">Ganti kata sandi</button>
    </form>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="tombol w-full border-bata/30 text-bata">Keluar</button>
    </form>
</div>
