<div class="space-y-4">
    <div class="flex items-baseline justify-between gap-3">
        <h1 class="text-2xl font-bold tracking-tight">Report</h1>
        <a href="{{ route('admin.reports.csv', $this->filters()) }}" class="tombol text-sm">Export CSV</a>
    </div>

    <div class="kartu grid sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <div>
            <label class="block label-kecil mb-1">Dari</label>
            <input wire:model.live="from" type="date" class="isian isian-kecil">
        </div>
        <div>
            <label class="block label-kecil mb-1">Sampai</label>
            <input wire:model.live="to" type="date" class="isian isian-kecil">
        </div>
        <div>
            <label class="block label-kecil mb-1">Sales</label>
            <select wire:model.live="user_id" class="isian isian-kecil">
                <option value="">Semua</option>
                @foreach ($salesUsers as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block label-kecil mb-1">Produk</label>
            <select wire:model.live="product_id" class="isian isian-kecil">
                <option value="">Semua</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block label-kecil mb-1">Toko</label>
            <select wire:model.live="store_id" class="isian isian-kecil">
                <option value="">Semua</option>
                @foreach ($stores as $store)
                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-3">
        @foreach ([
            ['Unit dititip', $totals->added, 'text-kunyit'],
            ['Unit terjual', $totals->sold, 'text-daun'],
            ['Kunjungan', $totals->visit_count, 'text-tinta'],
        ] as [$label, $value, $color])
            <div class="kartu">
                <p class="label-kecil">{{ $label }}</p>
                <p class="mt-1 angka-besar text-3xl {{ $color }}">{{ number_format($value) }}</p>
            </div>
        @endforeach
    </div>

    <div class="kartu !p-0 overflow-x-auto">
        <table class="w-full text-sm min-w-[760px]">
            <thead class="text-xs text-tinta/70 border-b border-tinta/10">
                <tr>
                    <th class="text-left font-normal px-4 py-3">Tanggal</th>
                    <th class="text-left font-normal px-4 py-3">Toko</th>
                    <th class="text-left font-normal px-4 py-3">Sales</th>
                    <th class="text-left font-normal px-4 py-3">Produk</th>
                    <th class="text-right font-normal px-4 py-3">Titipan awal</th>
                    <th class="text-right font-normal px-4 py-3">Sisa</th>
                    <th class="text-right font-normal px-4 py-3">Terjual</th>
                    <th class="text-right font-normal px-4 py-3">Tambah</th>
                    <th class="text-right font-normal px-4 py-3">Stok akhir</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-tinta/8 tabular-nums">
                @forelse ($rows as $row)
                    <tr>
                        <td class="px-4 py-2.5 whitespace-nowrap">{{ $row->visited_at->translatedFormat('d M Y H:i') }}</td>
                        <td class="px-4 py-2.5">{{ $row->store_name }}</td>
                        <td class="px-4 py-2.5">{{ $row->sales_name }}</td>
                        <td class="px-4 py-2.5">{{ $row->product_name }}</td>
                        <td class="px-4 py-2.5 text-right">{{ $row->qty_before }}</td>
                        <td class="px-4 py-2.5 text-right">{{ $row->qty_found }}</td>
                        <td class="px-4 py-2.5 text-right font-semibold text-daun">{{ $row->qty_sold }}</td>
                        <td class="px-4 py-2.5 text-right text-kunyit">{{ $row->qty_added }}</td>
                        <td class="px-4 py-2.5 text-right">{{ $row->qty_left }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-4 py-8 text-center text-tinta/70">Tidak ada data pada filter ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $rows->links() }}</div>
</div>
