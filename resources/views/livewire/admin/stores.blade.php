<div class="space-y-4">
    <div class="flex items-baseline justify-between gap-3">
        <h1 class="text-2xl font-bold tracking-tight">Toko</h1>
        <div class="flex items-center gap-2">
            <input type="search" wire:model.live.debounce.400ms="search" placeholder="Cari toko"
                   class="isian isian-kecil w-auto">
            <button type="button" wire:click="toggleTrashed" class="tombol text-sm whitespace-nowrap">
                {{ $trashed ? 'Lihat aktif' : 'Lihat sampah' }}
            </button>
            <a href="{{ route('stores.create') }}" class="tombol tombol-utama text-sm whitespace-nowrap">+ Tambah Toko</a>
        </div>
    </div>

    @if (session('status'))
        <p class="rounded-xl bg-daun/10 border border-daun/25 text-daun text-sm px-3 py-2">{{ session('status') }}</p>
    @endif

    @if (session('error'))
        <p class="rounded-xl bg-bata/10 border border-bata/25 text-bata text-sm px-3 py-2">{{ session('error') }}</p>
    @endif

    @if ($editingId)
        <form wire:submit="save" wire:confirm="Simpan perubahan ini?" class="kartu border-daun/40 grid sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
            <div class="lg:col-span-4 font-display font-bold">Ubah toko</div>
            <div>
                <label class="block label-kecil mb-1">Nama</label>
                <input wire:model="name" type="text" class="isian isian-kecil">
                @error('name') <p class="mt-1 text-xs text-bata">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block label-kecil mb-1">Pemilik</label>
                <input wire:model="owner_name" type="text" class="isian isian-kecil">
            </div>
            <div>
                <label class="block label-kecil mb-1">No. HP</label>
                <input wire:model="phone" type="tel" class="isian isian-kecil">
            </div>
            <div>
                <label class="block label-kecil mb-1">Alamat</label>
                <input wire:model="address" type="text" class="isian isian-kecil">
            </div>
            <div>
                <label class="block label-kecil mb-1">Latitude</label>
                <input wire:model="lat" type="text" inputmode="decimal" class="isian isian-kecil">
                @error('lat') <p class="mt-1 text-xs text-bata">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block label-kecil mb-1">Longitude</label>
                <input wire:model="lng" type="text" inputmode="decimal" class="isian isian-kecil">
                @error('lng') <p class="mt-1 text-xs text-bata">{{ $message }}</p> @enderror
            </div>
            <label class="flex items-center gap-2.5 text-sm text-tinta/70">
                <input wire:model="active" type="checkbox" class="size-4 rounded border-tinta/30 accent-daun"> Aktif
            </label>
            <div class="flex gap-2">
                <button class="tombol tombol-utama flex-1">Simpan toko</button>
                <button type="button" wire:click="cancel" class="tombol">Batal</button>
            </div>
        </form>
    @endif

    <div class="kartu !p-0 overflow-x-auto">
        <table class="w-full text-sm min-w-[760px]">
            <thead class="text-xs text-tinta/70 border-b border-tinta/10">
                <tr>
                    <th class="text-left font-normal px-4 py-3">Toko</th>
                    <th class="text-left font-normal px-4 py-3">Pemilik</th>
                    <th class="text-left font-normal px-4 py-3">Titik lokasi</th>
                    <th class="text-left font-normal px-4 py-3">Kunjungan terakhir</th>
                    <th class="text-left font-normal px-4 py-3">Didaftarkan</th>
                    <th class="text-left font-normal px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-tinta/8">
                @forelse ($stores as $store)
                    <tr wire:key="store-{{ $store->id }}">
                        <td class="px-4 py-2.5">
                            <p class="font-medium">{{ $store->name }}</p>
                            <p class="label-kecil">{{ $store->address ?: '—' }}</p>
                        </td>
                        <td class="px-4 py-2.5 text-tinta/70">{{ $store->owner_name ?: '—' }}</td>
                        <td class="px-4 py-2.5 text-xs tabular-nums">
                            @if ($store->lat)
                                <span class="text-tinta/70">{{ number_format($store->lat, 5) }}, {{ number_format($store->lng, 5) }}</span>
                            @else
                                <span class="text-kunyit font-medium">belum ada</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-tinta/70">
                            @if ($store->latestVisit)
                                {{ $store->latestVisit->visited_at->translatedFormat('d M Y') }}
                                <span class="text-xs">· {{ $store->latestVisit->user->name }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-tinta/70">{{ $store->creator?->name ?? '—' }}</td>
                        <td class="px-4 py-2.5">
                            <span class="rounded-full px-2 py-0.5 text-xs {{ $store->active ? 'bg-daun/12 text-daun font-medium' : 'bg-tinta/8 text-tinta/70' }}">
                                {{ $store->active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-right whitespace-nowrap">
                            <a href="{{ route('admin.stores.show', $store) }}" class="text-sm text-tinta/70 font-medium hover:underline">Detail</a>
                            @if (! $trashed)
                                <button wire:click="edit({{ $store->id }})" class="ml-3 text-sm text-daun font-medium hover:underline">Ubah</button>
                                <button wire:click="startMove({{ $store->id }})" class="ml-3 text-sm text-kunyit font-medium hover:underline">Pindahkan</button>
                                <button wire:click="delete({{ $store->id }})"
                                        wire:confirm="Hapus toko ini? Riwayat kunjungan tetap tersimpan dan toko bisa dipulihkan nanti."
                                        class="ml-3 text-sm text-bata font-medium hover:underline">Hapus</button>
                            @else
                                <button wire:click="restore({{ $store->id }})" class="ml-3 text-sm text-daun font-medium hover:underline">Pulihkan</button>
                            @endif
                        </td>
                    </tr>
                    @if ($movingId === $store->id)
                        <tr wire:key="move-{{ $store->id }}">
                            <td colspan="7" class="px-4 py-3 bg-kunyit/5">
                                <form wire:submit="moveStore" class="flex flex-wrap items-end gap-3">
                                    <p class="w-full text-sm">
                                        Pindahkan <strong>{{ $store->name }}</strong> ke sales:
                                    </p>
                                    <div>
                                        <select wire:model="moveToId" class="isian isian-kecil">
                                            <option value="">Pilih sales tujuan</option>
                                            @foreach ($moveTargets as $target)
                                                <option value="{{ $target->id }}" @selected($store->created_by === $target->id)>{{ $target->name }}</option>
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
                    <tr><td colspan="7" class="px-4 py-8 text-center text-tinta/70">Belum ada toko. Sales menambahkannya dari aplikasi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $stores->links() }}</div>
</div>
