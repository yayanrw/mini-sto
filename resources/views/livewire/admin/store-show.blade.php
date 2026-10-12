<div class="space-y-4">
    <a href="{{ route('admin.stores') }}" class="text-sm text-tinta/70 hover:text-tinta">&larr; Kembali</a>

    <div class="flex items-baseline justify-between gap-3">
        <h1 class="text-2xl font-bold tracking-tight">{{ $store->name }}</h1>
        <span class="rounded-full px-2 py-0.5 text-xs {{ $store->active ? 'bg-daun/12 text-daun font-medium' : 'bg-tinta/8 text-tinta/70' }}">
            {{ $store->active ? 'Aktif' : 'Nonaktif' }}
        </span>
    </div>

    <div class="kartu space-y-3">
        @if ($store->photo_path)
            <img src="{{ \Illuminate\Support\Facades\Storage::url($store->photo_path) }}" alt="Foto {{ $store->name }}"
                 class="w-full aspect-video object-cover rounded-xl">
        @else
            {{-- Placeholder: toko belum punya foto --}}
            <div class="w-full aspect-video rounded-xl bg-tinta/5 flex items-center justify-center text-tinta/30" role="img" aria-label="Belum ada foto">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-12">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z" />
                </svg>
            </div>
        @endif

        <div class="nota-baris">
            <span class="text-tinta/70">Pemilik</span>
            <span class="nota-angka">{{ $store->owner_name ?: '—' }}</span>
        </div>
        <div class="nota-baris">
            <span class="text-tinta/70">No. HP</span>
            <span class="nota-angka">{{ $store->phone ?: '—' }}</span>
        </div>
        <div class="nota-baris items-start">
            <span class="text-tinta/70">Alamat</span>
            <span class="text-right">{{ $store->address ?: '—' }}</span>
        </div>
        <div class="nota-baris">
            <span class="text-tinta/70">Dibuat oleh</span>
            <span class="nota-angka">{{ $store->creator?->name ?? '—' }}</span>
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
            <p class="text-xs text-kunyit">Toko ini belum punya titik lokasi.</p>
        @endif

        <a href="{{ route('admin.stores') }}" class="tombol w-full text-sm">
            <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>
            </svg>
            Ubah Toko
        </a>
    </div>

    <p class="nota-total">Riwayat kunjungan</p>

    @forelse ($history as $visit)
        <div class="nota mb-4" wire:key="riwayat-{{ $visit->id }}">
            <div class="flex items-baseline justify-between gap-3">
                <p class="font-display font-bold">{{ $visit->user->name }}</p>
                <span class="shrink-0 label-kecil">{{ $visit->visited_at->translatedFormat('d M Y · H:i') }}</span>
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

    <div>{{ $history->links() }}</div>
</div>
