<div>
    <h1 class="text-2xl font-bold tracking-tight">Riwayat Kunjungan</h1>

    @if (session('status'))
        <p class="mt-3 rounded-xl bg-daun/10 border border-daun/25 text-daun text-sm px-3 py-2">{{ session('status') }}</p>
    @endif

    <ul class="mt-4">
        @forelse ($visits as $visit)
            <li class="nota mb-4">
                <div class="flex items-baseline justify-between gap-3">
                    <p class="font-display font-bold truncate">{{ $visit->store->name }}</p>
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
            </li>
        @empty
            <li class="kartu-kosong text-sm text-tinta/70">
                Belum ada kunjungan tercatat. Kunjungan pertama muncul di sini setelah kamu menyimpannya.
            </li>
        @endforelse
    </ul>

    <div class="mt-4">{{ $visits->links() }}</div>
</div>
