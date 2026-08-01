<x-layout title="Masuk">
    <div class="w-full max-w-sm">
        <h1 class="text-3xl font-bold tracking-tight text-center">mini-sto</h1>
        <p class="mt-1 text-center text-sm text-tinta/70">Buku titipan produk, versi saku</p>

        <form method="POST" action="{{ route('login') }}" class="kartu mt-8 space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium mb-1.5">Email</label>
                <input id="email" name="email" type="email" required autofocus autocomplete="username"
                       value="{{ old('email') }}" class="isian">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium mb-1.5">Kata sandi</label>
                <input id="password" name="password" type="password" required autocomplete="current-password" class="isian">
            </div>

            @error('email')
                <p class="text-sm text-bata">{{ $message }}</p>
            @enderror

            <label class="flex items-center gap-2.5 text-sm text-tinta/70">
                <input type="checkbox" name="remember" value="1" class="size-4 rounded border-tinta/30 accent-daun">
                Ingat saya di perangkat ini
            </label>

            <button class="tombol tombol-utama w-full">Masuk</button>
        </form>
    </div>
</x-layout>
