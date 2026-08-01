<div>
    @push('head')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
        <script defer src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @endpush

    <a href="{{ route('stores.index') }}" class="text-sm text-tinta/70 hover:text-tinta">&larr; Kembali</a>
    <h1 class="mt-1 text-2xl font-bold tracking-tight">Toko Baru</h1>

    <form wire:submit="save" class="mt-4 space-y-3">
        <div class="kartu space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1.5">Nama toko <span class="text-bata">*</span></label>
                <input wire:model="name" type="text" required class="isian">
                @error('name') <p class="mt-1 text-sm text-bata">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1.5">Nama pemilik</label>
                <input wire:model="owner_name" type="text" class="isian">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1.5">No. HP</label>
                <input wire:model="phone" type="tel" inputmode="tel" class="isian">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1.5">Alamat</label>
                <textarea wire:model="address" rows="2" class="isian"></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1.5">Foto toko <span class="text-bata">*</span></label>
                <input wire:model="photo" type="file" accept="image/*" capture="environment"
                       class="w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-tinta/5 file:px-3 file:py-2 file:text-sm file:font-medium file:text-tinta">
                <div wire:loading wire:target="photo" class="mt-1 label-kecil">Mengunggah…</div>
                @error('photo') <p class="mt-1 text-sm text-bata">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="kartu">
            <div class="flex items-center justify-between">
                <label class="text-sm font-medium">Titik lokasi <span class="text-bata">*</span></label>
                <button type="button" x-data @click="$dispatch('locate-me')" class="text-sm text-daun font-medium underline">Ambil GPS</button>
            </div>

            <div wire:ignore
                 x-data="{
                     map: null,
                     marker: null,
                     push(lat, lng) { $wire.setLocation(lat, lng) },
                     place(lat, lng, zoom = 17) {
                         this.map.setView([lat, lng], zoom)
                         this.marker.setLatLng([lat, lng])
                         this.push(lat, lng)
                     },
                     locate() {
                         navigator.geolocation?.getCurrentPosition(
                             p => this.place(p.coords.latitude, p.coords.longitude),
                             () => {},
                             { enableHighAccuracy: true, timeout: 10000 }
                         )
                     },
                     start() {
                         const lat = Number(@js($lat)) || -6.2088
                         const lng = Number(@js($lng)) || 106.8456
                         this.map = L.map($refs.map).setView([lat, lng], @js($lat) ? 17 : 12)
                         L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                             maxZoom: 19,
                             attribution: '&copy; OpenStreetMap'
                         }).addTo(this.map)
                         this.marker = L.marker([lat, lng], { draggable: true }).addTo(this.map)
                         this.marker.on('dragend', e => this.push(e.target.getLatLng().lat, e.target.getLatLng().lng))
                         this.map.on('click', e => this.place(e.latlng.lat, e.latlng.lng, this.map.getZoom()))
                         if (!@js($lat)) this.locate()
                     }
                 }"
                 x-init="$nextTick(() => window.L ? start() : window.addEventListener('load', () => start()))"
                 @locate-me.window="locate()"
                 class="mt-3">
                <div x-ref="map" class="h-56 w-full rounded-xl border border-tinta/15 z-0"></div>
            </div>

            <p class="mt-2 label-kecil">Ketuk peta atau geser pin untuk memindahkan titik.</p>

            <div class="mt-3 grid grid-cols-2 gap-3">
                <div>
                    <label class="block label-kecil mb-1">Latitude</label>
                    <input wire:model="lat" type="text" inputmode="decimal" class="isian isian-kecil">
                </div>
                <div>
                    <label class="block label-kecil mb-1">Longitude</label>
                    <input wire:model="lng" type="text" inputmode="decimal" class="isian isian-kecil">
                </div>
            </div>
            @error('lat') <p class="mt-1 text-sm text-bata">{{ $message }}</p> @enderror
            @error('lng') <p class="mt-1 text-sm text-bata">{{ $message }}</p> @enderror
        </div>

        <button class="tombol tombol-utama w-full py-3.5 text-base" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="save">Simpan &amp; catat titipan</span>
            <span wire:loading wire:target="save">Menyimpan…</span>
        </button>
    </form>
</div>
