<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold tracking-tight">Dashboard</h1>
        <p class="text-sm text-tinta/70">Periode {{ now()->translatedFormat('F Y') }}</p>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
        @foreach ([
            ['Toko aktif', $totalStores, 'text-tinta'],
            ['Toko baru', $newStores, 'text-tinta'],
            ['Kunjungan', $visitCount, 'text-tinta'],
            ['Unit dititip', $added, 'text-kunyit'],
            ['Unit terjual', $sold, 'text-daun'],
        ] as [$label, $value, $color])
            <div class="kartu">
                <p class="label-kecil">{{ $label }}</p>
                <p class="mt-1 angka-besar text-3xl {{ $color }}">{{ number_format($value) }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid lg:grid-cols-2 gap-4">
        <div class="kartu">
            <p class="font-display font-bold">Produk terlaris bulan ini</p>
            @php($max = $topProducts->max('sold') ?: 1)
            <ul class="mt-3 space-y-2.5">
                @forelse ($topProducts as $product)
                    <li>
                        <div class="flex justify-between text-sm">
                            <span class="truncate pr-2">{{ $product->name }}</span>
                            <span class="tabular-nums font-semibold">{{ number_format($product->sold) }}</span>
                        </div>
                        <div class="mt-1 h-2 rounded-full bg-tinta/8">
                            <div class="h-2 rounded-full bg-daun" style="width: {{ round($product->sold / $max * 100) }}%"></div>
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-tinta/70">Belum ada penjualan tercatat bulan ini.</li>
                @endforelse
            </ul>
        </div>

        <div class="kartu">
            <p class="font-display font-bold">Toko perlu dikunjungi</p>
            <p class="label-kecil">Belum dikunjungi 30 hari atau lebih</p>
            <ul class="mt-3 divide-y divide-tinta/8">
                @forelse ($stale as $row)
                    <li class="py-2 flex justify-between gap-3 text-sm">
                        <span class="truncate">{{ $row['store']->name }}</span>
                        <span class="shrink-0 {{ $row['days'] === null ? 'text-kunyit font-medium' : 'text-tinta/70' }}">
                            {{ $row['days'] === null ? 'belum pernah' : $row['days'].' hari' }}
                        </span>
                    </li>
                @empty
                    <li class="py-2 text-sm text-tinta/70">Semua toko terkunjungi dalam 30 hari terakhir.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
