<div class="space-y-4">
    <h1 class="text-2xl font-bold tracking-tight">Rollup Area</h1>

    <div class="kartu flex flex-wrap gap-3 items-end">
        <div>
            <label class="block label-kecil mb-1">Dari</label>
            <input wire:model.live="from" type="date" class="isian isian-kecil w-auto">
        </div>
        <div>
            <label class="block label-kecil mb-1">Sampai</label>
            <input wire:model.live="to" type="date" class="isian isian-kecil w-auto">
        </div>
        <p class="label-kecil">Kunjungan dan terjual mengikuti rentang tanggal. Sisa stok = kondisi saat ini.</p>
    </div>

    @forelse ($rows as $row)
        <details class="kartu !p-0" wire:key="area-{{ $row['area'] ?? 'none' }}">
            <summary class="cursor-pointer px-4 py-3 flex flex-wrap items-center justify-between gap-3">
                <span class="font-display font-bold">{{ $row['area'] ?? 'Tanpa area' }}</span>
                <span class="text-sm tabular-nums text-tinta/70">
                    {{ $row['stores'] }} toko · {{ $row['visits'] }} kunjungan ·
                    <span class="text-daun font-semibold">{{ number_format($row['sold']) }} terjual</span> ·
                    {{ number_format($row['remaining']) }} tersisa
                </span>
            </summary>

            <div class="px-4 pb-4 space-y-2 border-t border-tinta/10 overflow-x-auto">
                <p class="pt-3 label-kecil">
                    Koordinator:
                    {{ $row['koordinators']->pluck('name')->join(', ') ?: '—' }}
                </p>

                <table class="w-full text-sm min-w-[520px]">
                    <thead class="text-xs text-tinta/70 border-b border-tinta/10">
                        <tr>
                            <th class="text-left font-normal py-2">Sales</th>
                            <th class="text-right font-normal py-2">Toko</th>
                            <th class="text-right font-normal py-2">Kunjungan</th>
                            <th class="text-right font-normal py-2">Terjual</th>
                            <th class="text-right font-normal py-2">Tersisa</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-tinta/8 tabular-nums">
                        @forelse ($row['sales'] as $s)
                            <tr>
                                <td class="py-2 font-medium">{{ $s['user']->name }}</td>
                                <td class="py-2 text-right">{{ $s['stores'] }}</td>
                                <td class="py-2 text-right">{{ $s['visits'] }}</td>
                                <td class="py-2 text-right text-daun font-semibold">{{ number_format($s['sold']) }}</td>
                                <td class="py-2 text-right">{{ number_format($s['remaining']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-4 text-center text-tinta/70">Belum ada sales di area ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </details>
    @empty
        <div class="kartu text-center text-tinta/70">Belum ada data area.</div>
    @endforelse
</div>
