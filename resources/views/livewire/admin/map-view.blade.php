<div>
    @push('head')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    @endpush

    <div class="flex items-baseline justify-between gap-3">
        <h1 class="text-2xl font-bold tracking-tight">Peta Toko</h1>
        <p class="text-sm text-tinta/70">
            {{ count($markers) }} toko bertitik lokasi
            @if ($missingCoords) · <span class="text-kunyit font-medium">{{ $missingCoords }} belum</span> @endif
        </p>
    </div>

    <div wire:ignore class="mt-4">
        <div id="store-map" class="h-[70vh] w-full rounded-2xl border border-tinta/12 z-0"></div>
    </div>

    @push('scripts')
        <script type="application/json" id="store-markers">@json($markers)</script>
        <script>
            (function () {
                const el = document.getElementById('store-map');
                if (!el || el.dataset.ready) return;
                el.dataset.ready = '1';

                const markers = JSON.parse(document.getElementById('store-markers').textContent);
                const map = L.map(el).setView([-2.5, 118], 5);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap',
                }).addTo(map);

                const escape = (value) => {
                    const node = document.createElement('span');
                    node.textContent = value ?? '';
                    return node.innerHTML;
                };

                const group = L.markerClusterGroup();

                markers.forEach((store) => {
                    const lines = [`<strong>${escape(store.name)}</strong>`];

                    if (store.owner) lines.push(escape(store.owner));
                    if (store.address) lines.push(`<span style="color:#2a2318a0">${escape(store.address)}</span>`);

                    lines.push(store.last_visit
                        ? `Kunjungan terakhir: ${escape(store.last_visit)} (${escape(store.last_sales)})`
                        : '<span style="color:#b45309">Belum pernah dikunjungi</span>');

                    lines.push(store.stock.length
                        ? '<ul style="margin:4px 0 0;padding-left:16px">' + store.stock.map((s) => `<li>${escape(s)}</li>`).join('') + '</ul>'
                        : '<span style="color:#64748b">Tidak ada stok titipan</span>');

                    group.addLayer(L.marker([store.lat, store.lng]).bindPopup(lines.join('<br>')));
                });

                map.addLayer(group);

                if (markers.length) {
                    map.fitBounds(group.getBounds().pad(0.2));
                }
            })();
        </script>
    @endpush
</div>
