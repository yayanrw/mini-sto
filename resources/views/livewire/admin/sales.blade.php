<div class="space-y-4">
    <h1 class="text-2xl font-bold tracking-tight">Sales</h1>

    @if (session('status'))
        <p class="rounded-xl bg-daun/10 border border-daun/25 text-daun text-sm px-3 py-2">{{ session('status') }}</p>
    @endif

    <form wire:submit="save" class="kartu grid sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
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
        <label class="flex items-center gap-2.5 text-sm text-tinta/70 lg:col-span-5">
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
                    <th class="text-right font-normal px-4 py-3">Toko</th>
                    <th class="text-left font-normal px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-tinta/8">
                @forelse ($sales as $user)
                    @php($storeCount = (int) ($storeCounts[$user->id] ?? 0))
                    <tr wire:key="sales-{{ $user->id }}">
                        <td class="px-4 py-2.5 font-medium">{{ $user->name }}</td>
                        <td class="px-4 py-2.5 text-tinta/70">{{ $user->email }}</td>
                        <td class="px-4 py-2.5 text-tinta/70">{{ $user->phone ?: '—' }}</td>
                        <td class="px-4 py-2.5 text-right tabular-nums">{{ $storeCount }}</td>
                        <td class="px-4 py-2.5">
                            <span class="rounded-full px-2 py-0.5 text-xs {{ $user->active ? 'bg-daun/12 text-daun font-medium' : 'bg-tinta/8 text-tinta/70' }}">
                                {{ $user->active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-right whitespace-nowrap">
                            <button wire:click="edit({{ $user->id }})" class="text-sm text-daun font-medium hover:underline">Ubah</button>
                            @if ($storeCount > 0)
                                <button wire:click="startMove({{ $user->id }})" class="ml-3 text-sm text-kunyit font-medium hover:underline">Pindahkan toko</button>
                            @endif
                        </td>
                    </tr>
                    @if ($movingFromId === $user->id)
                        <tr wire:key="move-{{ $user->id }}">
                            <td colspan="6" class="px-4 py-3 bg-kunyit/5">
                                <form wire:submit="moveStores" class="flex flex-wrap items-end gap-3">
                                    <p class="w-full text-sm">
                                        Pindahkan <strong>{{ $storeCount }}</strong> toko milik <strong>{{ $user->name }}</strong> ke:
                                    </p>
                                    <div>
                                        <select wire:model="moveToId" class="isian isian-kecil">
                                            <option value="">Pilih sales tujuan</option>
                                            @foreach ($moveTargets as $target)
                                                <option value="{{ $target->id }}">{{ $target->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('moveToId') <p class="mt-1 text-xs text-bata">{{ $message }}</p> @enderror
                                    </div>
                                    <button class="tombol tombol-utama text-sm">Pindahkan</button>
                                    <button type="button" wire:click="cancelMove" class="tombol text-sm">Batal</button>
                                </form>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-tinta/70">Belum ada sales.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $sales->links() }}</div>
</div>
