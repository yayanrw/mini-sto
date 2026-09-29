@props(['model' => 'area'])

{{-- Hanya superadmin: koordinator datanya sudah otomatis terbatas di areanya. --}}
@if (auth()->user()->isSuperadmin())
    <div>
        <label class="block label-kecil mb-1">Area</label>
        <select wire:model.live="{{ $model }}" class="isian isian-kecil w-auto">
            <option value="">Semua area</option>
            @foreach (\App\Support\Areas::all() as $area)
                <option value="{{ $area }}">{{ $area }}</option>
            @endforeach
        </select>
    </div>
@endif
