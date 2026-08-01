<div class="space-y-4">
    <h1 class="text-2xl font-bold tracking-tight">Pengguna</h1>

    @if (session('status'))
        <p class="rounded-xl bg-daun/10 border border-daun/25 text-daun text-sm px-3 py-2">{{ session('status') }}</p>
    @endif

    <form wire:submit="save" class="kartu grid sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
        <div>
            <label class="block label-kecil mb-1">Nama</label>
            <input wire:model="name" type="text" class="isian isian-kecil">
            @error('name') <p class="mt-1 text-xs text-bata">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block label-kecil mb-1">Email</label>
            <input wire:model="email" type="email" class="isian isian-kecil">
            @error('email') <p class="mt-1 text-xs text-bata">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block label-kecil mb-1">No. HP</label>
            <input wire:model="phone" type="tel" class="isian isian-kecil">
        </div>
        <div>
            <label class="block label-kecil mb-1">Role</label>
            <select wire:model="role" class="isian isian-kecil">
                <option value="sales">Sales</option>
                <option value="admin">Admin</option>
                @if (auth()->user()->isSuperadmin())
                    <option value="superadmin">Superadmin</option>
                @endif
            </select>
        </div>
        <div>
            <label class="block label-kecil mb-1">{{ $editingId ? 'Kata sandi baru' : 'Kata sandi' }}</label>
            <input wire:model="password" type="text" placeholder="{{ $editingId ? 'kosongkan = tetap' : 'min. 8 karakter' }}"
                   class="isian isian-kecil">
            @error('password') <p class="mt-1 text-xs text-bata">{{ $message }}</p> @enderror
        </div>
        <div class="flex gap-2">
            <button class="tombol tombol-utama flex-1">{{ $editingId ? 'Perbarui' : 'Tambah' }}</button>
            @if ($editingId)
                <button type="button" wire:click="cancel" class="tombol">Batal</button>
            @endif
        </div>
        <label class="flex items-center gap-2.5 text-sm text-tinta/70 lg:col-span-6">
            <input wire:model="active" type="checkbox" class="size-4 rounded border-tinta/30 accent-daun">
            Aktif — boleh masuk ke aplikasi
        </label>
    </form>

    <div class="kartu !p-0 overflow-x-auto">
        <table class="w-full text-sm min-w-[640px]">
            <thead class="text-xs text-tinta/70 border-b border-tinta/10">
                <tr>
                    <th class="text-left font-normal px-4 py-3">Nama</th>
                    <th class="text-left font-normal px-4 py-3">Email</th>
                    <th class="text-left font-normal px-4 py-3">HP</th>
                    <th class="text-left font-normal px-4 py-3">Role</th>
                    <th class="text-left font-normal px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-tinta/8">
                @foreach ($users as $user)
                    <tr wire:key="user-{{ $user->id }}">
                        <td class="px-4 py-2.5 font-medium">{{ $user->name }}</td>
                        <td class="px-4 py-2.5 text-tinta/70">{{ $user->email }}</td>
                        <td class="px-4 py-2.5 text-tinta/70">{{ $user->phone ?: '—' }}</td>
                        <td class="px-4 py-2.5">{{ ucfirst($user->role) }}</td>
                        <td class="px-4 py-2.5">
                            <span class="rounded-full px-2 py-0.5 text-xs {{ $user->active ? 'bg-daun/12 text-daun font-medium' : 'bg-tinta/8 text-tinta/70' }}">
                                {{ $user->active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-right">
                            @if (! $user->isSuperadmin() || auth()->user()->isSuperadmin())
                                <button wire:click="edit({{ $user->id }})" class="text-sm text-daun font-medium hover:underline">Ubah</button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div>{{ $users->links() }}</div>
</div>
