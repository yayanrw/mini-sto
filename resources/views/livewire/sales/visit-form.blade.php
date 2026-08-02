<div x-data="{ tab: 'kunjungan' }" x-init="
    navigator.geolocation?.getCurrentPosition(
        p => $wire.setLocation(p.coords.latitude, p.coords.longitude),
        () => {},
        { enableHighAccuracy: true, timeout: 8000 }
    )
">
    <a href="{{ route('stores.index') }}" class="text-sm text-tinta/70 hover:text-tinta">&larr; Kembali</a>
    <h1 class="mt-1 text-2xl font-bold tracking-tight">{{ $store->name }}</h1>
    <p class="text-sm text-tinta/70">{{ $store->owner_name ?: '—' }}{{ $store->address ? ' · '.$store->address : '' }}</p>

    @if (session('status'))
        <p class="mt-3 rounded-xl bg-daun/10 border border-daun/25 text-daun text-sm px-3 py-2">{{ session('status') }}</p>
    @endif

    <div class="mt-4 grid grid-cols-3 gap-2">
        <button type="button" @click="tab = 'kunjungan'"
                class="tombol justify-center text-sm"
                :class="tab === 'kunjungan' && 'tombol-utama'">
            Kunjungan
        </button>
        <button type="button" @click="tab = 'riwayat'"
                class="tombol justify-center text-sm"
                :class="tab === 'riwayat' && 'tombol-utama'">
            Riwayat Kunjungan
        </button>
        <button type="button" @click="tab = 'detail'"
                class="tombol justify-center text-sm"
                :class="tab === 'detail' && 'tombol-utama'">
            Detail
        </button>
    </div>

    <div x-show="tab === 'kunjungan'">
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

                <div class="nota-baris">
                    <span class="text-tinta/70">Sisa sekarang</span>
                    <span class="flex items-center gap-1.5">
                        <button type="button" wire:click="$set('items.{{ $productId }}.qty_found', {{ max($found - 1, 0) }})"
                                class="tombol size-9 !min-h-9 shrink-0 !p-0 text-lg leading-none">&minus;</button>
                        <input wire:model.live="items.{{ $productId }}.qty_found" type="number" min="0" max="{{ $before }}"
                               inputmode="numeric" placeholder="0"
                               class="isian isian-angka w-20 text-right">
                        <button type="button" wire:click="$set('items.{{ $productId }}.qty_found', {{ min($found + 1, $before) }})"
                                class="tombol size-9 !min-h-9 shrink-0 !p-0 text-lg leading-none">&plus;</button>
                    </span>
                </div>

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
                        @php($currentAdded = (int) ($items[$product->id]['qty_added'] ?? 0))
                        <span class="flex items-center gap-1">
                            <button type="button" wire:click="$set('items.{{ $product->id }}.qty_added', {{ max($currentAdded - 1, 0) }})"
                                    class="tombol size-8 !min-h-8 shrink-0 !p-0 text-base leading-none">&minus;</button>
                            <input wire:model.live="items.{{ $product->id }}.qty_added" type="number" min="0"
                                   inputmode="numeric" placeholder="0"
                                   class="isian isian-angka isian-kecil w-16 text-right">
                            <button type="button" wire:click="$set('items.{{ $product->id }}.qty_added', {{ $currentAdded + 1 }})"
                                    class="tombol size-8 !min-h-8 shrink-0 !p-0 text-base leading-none">&plus;</button>
                        </span>
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

    <div x-show="tab === 'riwayat'" class="mt-4 space-y-3">
        <p class="nota-total">Riwayat kunjungan</p>

        @forelse ($history as $visit)
            <div class="nota mb-4" wire:key="riwayat-{{ $visit->id }}">
                <div class="flex items-baseline justify-between gap-3">
                    <span class="label-kecil">{{ $visit->visited_at->translatedFormat('d M Y · H:i') }}</span>
                </div>

                <hr class="nota-pisah">

                @foreach ($visit->items as $item)
                    <div class="nota-baris items-start">
                        <span class="text-tinta/70 truncate pr-2">{{ $item->product->name }}</span>
                        <span class="shrink-0 text-right">
                            <span class="nota-angka text-daun">{{ $item->qty_sold }}</span>
                            <span class="label-kecil">terjual</span>
                        </span>
                    </div>
                    <div class="label-kecil -mt-1 pb-1">
                        sisa {{ $item->qty_found }} · tambah {{ $item->qty_added }} · ditinggal {{ $item->qty_left }}
                    </div>
                @endforeach

                @if ($visit->note)
                    <hr class="nota-pisah">
                    <p class="text-sm text-tinta/70 italic">{{ $visit->note }}</p>
                @endif
            </div>
        @empty
            <p class="kartu-kosong text-sm text-tinta/70">Belum ada kunjungan tercatat untuk toko ini.</p>
        @endforelse
    </div>

    <div x-show="tab === 'detail'" class="mt-4 space-y-3">
        <div class="kartu space-y-3">
            @if ($store->photo_path)
                <a href="{{ \Illuminate\Support\Facades\Storage::url($store->photo_path) }}" target="_blank" rel="noopener">
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($store->photo_path) }}" alt="Foto {{ $store->name }}"
                         class="w-full aspect-video object-cover rounded-xl">
                </a>
            @endif

            <div class="nota-baris">
                <span class="text-tinta/70">Pemilik</span>
                <span class="nota-angka">{{ $store->owner_name ?: '—' }}</span>
            </div>
            <div class="nota-baris">
                <span class="text-tinta/70">No. HP</span>
                @if ($store->phone)
                    <span class="flex items-center gap-2" x-data="{ copied: false }">
                        <span class="nota-angka">{{ $store->phone }}</span>
                        <button type="button"
                                @click="navigator.clipboard.writeText(@js($store->phone)); copied = true; setTimeout(() => copied = false, 1500)"
                                class="tombol size-8 !min-h-8 shrink-0 !p-0">
                            <svg x-show="!copied" class="size-3.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <rect x="9" y="9" width="11" height="11" rx="2"/>
                                <path d="M5 15V5a2 2 0 0 1 2-2h10"/>
                            </svg>
                            <svg x-show="copied" class="size-3.5 text-daun" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <path d="M20 6 9 17l-5-5"/>
                            </svg>
                        </button>
                    </span>
                @else
                    <span class="nota-angka">—</span>
                @endif
            </div>
            <div class="nota-baris items-start">
                <span class="text-tinta/70">Alamat</span>
                <span class="text-right">{{ $store->address ?: '—' }}</span>
            </div>

            @if ($store->lat && $store->lng)
                <a href="https://www.google.com/maps/dir/?api=1&destination={{ $store->lat }},{{ $store->lng }}"
                   target="_blank" rel="noopener" class="tombol w-full text-sm">
                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M12 21s7-6.4 7-11a7 7 0 1 0-14 0c0 4.6 7 11 7 11Z"/>
                        <circle cx="12" cy="10" r="2.5"/>
                    </svg>
                    Buka di Google Maps
                </a>
            @else
                <p class="text-xs text-kunyit">Toko ini belum punya titik lokasi. Admin bisa mengisinya dari menu Toko.</p>
            @endif

            <a href="{{ route('stores.edit', $store) }}" class="tombol w-full text-sm">
                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>
                </svg>
                Ubah Toko
            </a>
        </div>
    </div>
</div>
