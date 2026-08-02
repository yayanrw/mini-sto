<div>
    @php($isAdmin = auth()->user()->isAdmin())
    @php($wajib = ! $editing && ! $isAdmin)

    @push('head')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
        <script defer src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @endpush

    @php($kembali = $editing
        ? ($isAdmin ? route('admin.stores.show', $editing) : route('visits.create', $editing))
        : ($isAdmin ? route('admin.stores') : route('stores.index')))

    <a href="{{ $kembali }}" class="text-sm text-tinta/70 hover:text-tinta">&larr; Kembali</a>
    <h1 class="mt-1 text-2xl font-bold tracking-tight">{{ $editing ? 'Ubah Toko' : 'Toko Baru' }}</h1>

    <form wire:submit="save" class="mt-4 space-y-3">
        <div class="kartu space-y-4">
            @if ($isAdmin)
                <div>
                    <label class="block text-sm font-medium mb-1.5">Ditugaskan ke sales <span class="text-bata">*</span></label>
                    <select wire:model="assignedTo" class="isian">
                        <option value="">Pilih sales</option>
                        @foreach ($salesOptions as $salesUser)
                            <option value="{{ $salesUser->id }}">{{ $salesUser->name }}</option>
                        @endforeach
                    </select>
                    @error('assignedTo') <p class="mt-1 text-sm text-bata">{{ $message }}</p> @enderror
                </div>
            @endif

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

            <div x-data="{ preview: @js($editing?->photo_path ? \Illuminate\Support\Facades\Storage::url($editing->photo_path) : null) }">
                <label class="block text-sm font-medium mb-1.5">
                    Foto toko @if ($wajib) <span class="text-bata">*</span> @endif
                </label>
                <label
                    class="kartu-kosong flex flex-col items-center justify-center gap-1.5 cursor-pointer overflow-hidden"
                    :class="preview && 'p-0 border-solid'">
                    <template x-if="!preview">
                        <div class="flex flex-col items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-7 opacity-60">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9m0 0-3 3m3-3 3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3.75 3.75 0 0 1 4.132 6.144 4.5 4.5 0 0 1-.917 8.79H6.75Z" />
                            </svg>
                            <span class="text-sm font-medium">Ketuk untuk pilih foto</span>
                            <span class="label-kecil">atau ambil dari kamera</span>
                        </div>
                    </template>
                    <img x-show="preview" :src="preview" class="w-full aspect-video object-cover rounded-[inherit]">
                    <input wire:model="photo" type="file" accept="image/*" capture="environment" class="sr-only"
                           @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null">
                </label>
                <div wire:loading wire:target="photo" class="mt-1 label-kecil">Mengunggah…</div>
                @error('photo') <p class="mt-1 text-sm text-bata">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="kartu">
            <div class="flex items-center justify-between">
                <label class="text-sm font-medium">
                    Titik lokasi @if ($wajib) <span class="text-bata">*</span> @endif
                </label>
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
                         this.marker = L.marker([lat, lng], { draggable: @js($isAdmin) }).addTo(this.map)
                         if (@js($isAdmin)) {
                             this.marker.on('dragend', e => this.push(e.target.getLatLng().lat, e.target.getLatLng().lng))
                             this.map.on('click', e => this.place(e.latlng.lat, e.latlng.lng, this.map.getZoom()))
                         }
                         if (!@js($lat)) this.locate()
                     }
                 }"
                 x-init="$nextTick(() => window.L ? start() : window.addEventListener('load', () => start()))"
                 @locate-me.window="locate()"
                 class="mt-3">
                <div x-ref="map" class="h-56 w-full rounded-xl border border-tinta/15 z-0"></div>
            </div>

            @if ($isAdmin)
                <p class="mt-2 label-kecil">Ketuk peta atau geser pin untuk memindahkan titik.</p>
            @else
                <p class="mt-2 label-kecil">Titik diambil dari lokasi GPS — ketuk "Ambil GPS" untuk memperbarui.</p>
            @endif

            <div class="mt-3 grid grid-cols-2 gap-3">
                <div>
                    <label class="block label-kecil mb-1">Latitude</label>
                    <input wire:model="lat" type="text" inputmode="decimal" @unless($isAdmin) readonly tabindex="-1" @endunless class="isian isian-kecil">
                </div>
                <div>
                    <label class="block label-kecil mb-1">Longitude</label>
                    <input wire:model="lng" type="text" inputmode="decimal" @unless($isAdmin) readonly tabindex="-1" @endunless class="isian isian-kecil">
                </div>
            </div>
            @error('lat') <p class="mt-1 text-sm text-bata">{{ $message }}</p> @enderror
            @error('lng') <p class="mt-1 text-sm text-bata">{{ $message }}</p> @enderror
        </div>

        <button class="tombol tombol-utama w-full py-3.5 text-base" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="save">Simpan</span>
            <span wire:loading wire:target="save">Menyimpan…</span>
        </button>
    </form>
</div>
