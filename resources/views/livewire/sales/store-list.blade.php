<div x-data x-init="
    navigator.geolocation?.getCurrentPosition(
        p => $wire.setLocation(p.coords.latitude, p.coords.longitude),
        () => {},
        { enableHighAccuracy: true, timeout: 8000 }
    )
">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold tracking-tight">Toko</h1>
        <a href="{{ route('stores.create') }}" class="tombol tombol-utama text-sm">+ Toko Baru</a>
    </div>

    <input type="search" wire:model.live.debounce.400ms="search" placeholder="Cari nama toko, pemilik, atau alamat"
           class="isian mt-4">

    @if ($lat === null)
        <p class="mt-2 label-kecil">Izinkan akses lokasi supaya toko terdekat naik ke atas.</p>
    @endif

    <ul class="mt-4 space-y-2.5">
        @forelse ($stores as $store)
            <li>
                <a href="{{ route('visits.create', $store) }}" class="kartu block active:bg-tinta/5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-display font-bold truncate">{{ $store->name }}</p>
                            <p class="text-sm text-tinta/70 truncate">{{ $store->owner_name ?: '—' }}{{ $store->address ? ' · '.$store->address : '' }}</p>
                        </div>
                        @isset($store->distance_km)
                            <span class="shrink-0 rounded-full bg-tinta/5 px-2 py-0.5 label-kecil tabular-nums">{{ number_format($store->distance_km, 1) }} km</span>
                        @endisset
                    </div>

                    <p class="mt-2 text-xs {{ $store->latestVisit ? 'text-tinta/70' : 'text-kunyit font-medium' }}">
                        @if ($store->latestVisit)
                            Dikunjungi {{ $store->latestVisit->visited_at->diffForHumans() }}
                        @else
                            Belum pernah dikunjungi
                        @endif
                    </p>
                </a>
            </li>
        @empty
            <li class="kartu-kosong">
                <p class="text-sm text-tinta/70">Belum ada toko di daftar.</p>
                <a href="{{ route('stores.create') }}" class="tombol tombol-utama mt-4">Catat toko pertama</a>
            </li>
        @endforelse
    </ul>

    <div class="mt-4">{{ $stores->links() }}</div>
</div>
