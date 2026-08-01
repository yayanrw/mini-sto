<div class="space-y-4">
    <h1 class="text-2xl font-bold tracking-tight">Performa Sales</h1>

    <div class="kartu flex flex-wrap gap-3 items-end">
        <div>
            <label class="block label-kecil mb-1">Dari</label>
            <input wire:model.live="from" type="date" class="isian isian-kecil w-auto">
        </div>
        <div>
            <label class="block label-kecil mb-1">Sampai</label>
            <input wire:model.live="to" type="date" class="isian isian-kecil w-auto">
        </div>
    </div>

    <div class="kartu !p-0 overflow-x-auto">
        <table class="w-full text-sm min-w-[640px]">
            <thead class="text-xs text-tinta/70 border-b border-tinta/10">
                <tr>
                    <th class="text-left font-normal px-4 py-3">Sales</th>
                    <th class="text-right font-normal px-4 py-3">Kunjungan</th>
                    <th class="text-right font-normal px-4 py-3">Toko dikunjungi</th>
                    <th class="text-right font-normal px-4 py-3">Toko baru</th>
                    <th class="text-right font-normal px-4 py-3">Unit dititip</th>
                    <th class="text-right font-normal px-4 py-3">Unit terjual</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-tinta/8 tabular-nums">
                @forelse ($rows as $row)
                    <tr>
                        <td class="px-4 py-3 font-medium">
                            {{ $row['user']->name }}
                            @if ($row['user']->role !== 'sales')
                                <span class="ml-1 label-kecil">({{ $row['user']->role }})</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">{{ $row['visits'] }}</td>
                        <td class="px-4 py-3 text-right">{{ $row['stores'] }}</td>
                        <td class="px-4 py-3 text-right">{{ $row['new_stores'] }}</td>
                        <td class="px-4 py-3 text-right text-kunyit">{{ number_format($row['added']) }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-daun">{{ number_format($row['sold']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-tinta/70">Tidak ada data pada rentang ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
