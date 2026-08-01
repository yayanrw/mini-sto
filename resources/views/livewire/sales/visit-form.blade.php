<div x-data x-init="
    navigator.geolocation?.getCurrentPosition(
        p => $wire.setLocation(p.coords.latitude, p.coords.longitude),
        () => {},
        { enableHighAccuracy: true, timeout: 8000 }
    )
">
    <a href="{{ route('stores.index') }}" class="text-sm text-tinta/70 hover:text-tinta">&larr; Kembali</a>
    <h1 class="mt-1 text-2xl font-bold tracking-tight">{{ $store->name }}</h1>
    <p class="text-sm text-tinta/70">{{ $store->owner_name ?: '—' }}{{ $store->address ? ' · '.$store->address : '' }}</p>

    @if ($store->lat && $store->lng)
        <a href="https://www.google.com/maps/dir/?api=1&destination={{ $store->lat }},{{ $store->lng }}"
           target="_blank" rel="noopener" class="tombol mt-3 text-sm">
            <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M12 21s7-6.4 7-11a7 7 0 1 0-14 0c0 4.6 7 11 7 11Z"/>
                <circle cx="12" cy="10" r="2.5"/>
            </svg>
            Buka di Google Maps
        </a>
    @else
        <p class="mt-3 text-xs text-kunyit">Toko ini belum punya titik lokasi. Admin bisa mengisinya dari menu Toko.</p>
    @endif

    @if (session('status'))
        <p class="mt-3 rounded-xl bg-daun/10 border border-daun/25 text-daun text-sm px-3 py-2">{{ session('status') }}</p>
    @endif

    @error('items')
        <p class="mt-3 rounded-xl bg-bata/10 border border-bata/25 text-bata text-sm px-3 py-2">{{ $message }}</p>
    @enderror

    <form wire:submit="save" class="mt-4">
        <p class="nota-total mb-2">Hitung stok</p>

        @php($countable = collect($items)->filter(fn ($row, $id) => ($baseline[$id] ?? 0) > 0))

        @forelse ($countable as $productId => $row)
            @php($product = $products[$productId] ?? null)
            @continue(! $product)
            @php($before = $baseline[$productId])
            @php($found = (int) ($row['qty_found'] ?: 0))
            @php($added = (int) ($row['qty_added'] ?: 0))

            {{-- Disusun seperti baris struk: garis putus memisahkan yang diketik
                 sales dari yang dihitung aplikasi. --}}
            <div class="nota mb-4" wire:key="item-{{ $productId }}">
                <p class="font-display font-bold">{{ $product->name }}</p>

                <div class="nota-baris">
                    <span class="text-tinta/70">Titipan lalu</span>
                    <span class="nota-angka">{{ $before }} <span class="label-kecil">{{ $product->unit }}</span></span>
                </div>

                <label class="nota-baris">
                    <span class="text-tinta/70">Sisa dihitung</span>
                    <input wire:model.live="items.{{ $productId }}.qty_found" type="number" min="0" max="{{ $before }}"
                           inputmode="numeric" placeholder="0"
                           class="isian isian-angka w-28 text-right">
                </label>

                <hr class="nota-pisah">

                <div class="nota-baris">
                    <span class="nota-total text-tinta/70">Terjual</span>
                    <span class="nota-angka text-xl text-daun">{{ max($before - $found, 0) }}</span>
                </div>

                <hr class="nota-pisah">

                <div class="nota-baris">
                    <span class="nota-total text-tinta/70">Stok ditinggal</span>
                    <span class="nota-angka text-xl">{{ $found + $added }}</span>
                </div>

                @error("items.{$productId}.qty_found") <p class="mt-1 text-sm text-bata">{{ $message }}</p> @enderror
                @error("items.{$productId}") <p class="mt-1 text-sm text-bata">{{ $message }}</p> @enderror
            </div>
        @empty
            <p class="kartu-kosong text-sm text-tinta/70">
                Tidak ada produk dengan sisa titipan untuk dihitung kunjungan ini.
            </p>
        @endforelse

        <div class="kartu mt-3">
            <p class="nota-total mb-2">Tambah titipan (opsional)</p>

            @foreach ($products as $product)
                @php($checked = $restocking[$product->id] ?? false)
                <div class="flex items-center gap-3 py-2 border-b border-tinta/10 last:border-b-0"
                     wire:key="restok-{{ $product->id }}">
                    <label class="flex flex-1 items-center gap-3 min-h-11 cursor-pointer">
                        <input type="checkbox" wire:click="toggleRestock({{ $product->id }})"
                               @checked($checked) class="size-6 shrink-0 accent-daun">
                        <span>{{ $product->name }}</span>
                    </label>

                    @if ($checked)
                        <input wire:model.live="items.{{ $product->id }}.qty_added" type="number" min="0"
                               inputmode="numeric" placeholder="0"
                               class="isian isian-angka isian-kecil w-24 text-right">
                    @endif
                </div>
                @error("items.{$product->id}") <p class="mt-1 text-sm text-bata">{{ $message }}</p> @enderror
            @endforeach
        </div>

        <div class="kartu mt-3">
            <label class="block text-sm font-medium mb-1.5">Catatan</label>
            <textarea wire:model="note" rows="2" placeholder="Opsional — misal: toko minta rasa balado"
                      class="isian"></textarea>
        </div>

        <button class="tombol tombol-utama w-full mt-4 py-3.5 text-base" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="save">Simpan kunjungan</span>
            <span wire:loading wire:target="save">Menyimpan…</span>
        </button>
    </form>
</div>
