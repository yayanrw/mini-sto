<div class="space-y-4">
    <div class="flex items-baseline justify-between gap-3">
        <h1 class="text-2xl font-bold tracking-tight">Produk</h1>
        <button type="button" wire:click="toggleTrashed" class="tombol text-sm whitespace-nowrap">
            {{ $trashed ? 'Lihat aktif' : 'Lihat sampah' }}
        </button>
    </div>

    @if (session('status'))
        <p class="rounded-xl bg-daun/10 border border-daun/25 text-daun text-sm px-3 py-2">{{ session('status') }}</p>
    @endif

    @if (session('error'))
        <p class="rounded-xl bg-bata/10 border border-bata/25 text-bata text-sm px-3 py-2">{{ session('error') }}</p>
    @endif

    <form wire:submit="save" wire:confirm="Simpan perubahan ini?" class="kartu grid sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
        <div class="lg:col-span-2">
            <label class="block label-kecil mb-1">Nama produk</label>
            <input wire:model="name" type="text" class="isian isian-kecil">
            @error('name') <p class="mt-1 text-xs text-bata">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block label-kecil mb-1">SKU</label>
            <input wire:model="sku" type="text" class="isian isian-kecil">
            @error('sku') <p class="mt-1 text-xs text-bata">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block label-kecil mb-1">Satuan</label>
            <input wire:model="unit" type="text" class="isian isian-kecil">
        </div>
        <div class="flex gap-2">
            <button class="tombol tombol-utama flex-1">{{ $editingId ? 'Perbarui' : 'Tambah' }}</button>
            @if ($editingId)
                <button type="button" wire:click="cancel" class="tombol">Batal</button>
            @endif
        </div>
        <label class="flex items-center gap-2.5 text-sm text-tinta/70 lg:col-span-5">
            <input wire:model="active" type="checkbox" class="size-4 rounded border-tinta/30 accent-daun">
            Aktif — tampil di form kunjungan
        </label>
    </form>

    <div class="kartu !p-0 overflow-x-auto">
        <table class="w-full text-sm min-w-[560px]">
            <thead class="text-xs text-tinta/70 border-b border-tinta/10">
                <tr>
                    <th class="text-left font-normal px-4 py-3">Nama</th>
                    <th class="text-left font-normal px-4 py-3">SKU</th>
                    <th class="text-left font-normal px-4 py-3">Satuan</th>
                    <th class="text-left font-normal px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-tinta/8">
                @foreach ($products as $product)
                    <tr wire:key="product-{{ $product->id }}">
                        <td class="px-4 py-2.5 font-medium">{{ $product->name }}</td>
                        <td class="px-4 py-2.5 text-tinta/70">{{ $product->sku ?: '—' }}</td>
                        <td class="px-4 py-2.5 text-tinta/70">{{ $product->unit }}</td>
                        <td class="px-4 py-2.5">
                            <span class="rounded-full px-2 py-0.5 text-xs {{ $product->active ? 'bg-daun/12 text-daun font-medium' : 'bg-tinta/8 text-tinta/70' }}">
                                {{ $product->active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-right">
                            @if (! $trashed)
                                <button wire:click="edit({{ $product->id }})" class="text-sm text-daun font-medium hover:underline">Ubah</button>
                                <button wire:click="delete({{ $product->id }})"
                                        wire:confirm="Hapus produk ini? Produk hanya bisa dihapus jika tidak ada sisa titipan di toko manapun."
                                        class="ml-3 text-sm text-bata font-medium hover:underline">Hapus</button>
                            @else
                                <button wire:click="restore({{ $product->id }})" class="text-sm text-daun font-medium hover:underline">Pulihkan</button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div>{{ $products->links() }}</div>
</div>
