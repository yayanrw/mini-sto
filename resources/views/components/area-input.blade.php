@props(['model' => 'area'])

{{-- Datalist native: bisa dicari sambil mengetik. Server tetap memvalidasi nilainya (Areas::rule()). --}}
<input wire:model="{{ $model }}" list="daftar-area" type="text" placeholder="Pilih / ketik area" autocomplete="off"
       class="isian isian-kecil">
<datalist id="daftar-area">
    @foreach (\App\Support\Areas::all() as $area)
        <option value="{{ $area }}"></option>
    @endforeach
</datalist>
