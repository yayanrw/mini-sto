@props(['title' => null])

@php($user = auth()->user())

<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#fbf3e3">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
    @livewireStyles
</head>

<body class="h-full bg-kertas text-tinta antialiased">

    @if ($user?->isAdmin())
    <header class="sticky top-0 z-40 bg-kartu border-b border-tinta/10">
        <div class="mx-auto max-w-6xl px-4 h-14 flex items-center justify-between gap-4">
            <a href="{{ route('admin.dashboard') }}" class="font-display font-bold tracking-tight text-lg">MINI SALES TAKING ORDER</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="text-sm text-tinta/70 hover:text-tinta">Keluar</button>
            </form>
        </div>
        <nav class="mx-auto max-w-6xl px-4 flex gap-1 overflow-x-auto text-sm">
            @foreach ([
            'admin.dashboard' => 'Dashboard',
            'admin.map' => 'Peta',
            'admin.performance' => 'Performa',
            'admin.reports' => 'Report',
            'admin.stores' => 'Toko',
            'admin.products' => 'Produk',
            'admin.sales' => 'Sales',
            'admin.users' => 'Pengguna',
            ] as $route => $label)
            @continue($route === 'admin.users' && ! $user->isSuperadmin())
            <a href="{{ route($route) }}"
                class="shrink-0 px-3 py-2.5 border-b-2 {{ request()->routeIs($route) ? 'border-daun text-daun font-semibold' : 'border-transparent text-tinta/70 hover:text-tinta' }}">
                {{ $label }}
            </a>
            @endforeach
        </nav>
    </header>

    <main class="mx-auto max-w-6xl p-4 pb-16">
        {{ $slot }}
    </main>
    @elseif ($user)
    <main class="mx-auto max-w-lg p-4 pb-28">
        {{ $slot }}
    </main>

    <nav class="fixed bottom-0 inset-x-0 z-40 bg-kartu border-t border-tinta/10 pb-[env(safe-area-inset-bottom)]">
        <div class="mx-auto max-w-lg grid grid-cols-3 p-1.5 gap-1">
            @foreach ([
            'stores.index' => ['Toko', 'M3 10.5 12 3l9 7.5M5 9.5V21h14V9.5'],
            'visits.index' => ['Kunjungan', 'M8 7V3m8 4V3M4 11h16M5 21h14a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v13a1 1 0 0 0 1 1Z'],
            'profile' => ['Profil', 'M5 21a7 7 0 0 1 14 0M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z'],
            ] as $route => [$label, $path])
            <a href="{{ route($route) }}"
                @class([ 'flex flex-col items-center gap-1 py-2 rounded-xl text-xs' , 'bg-daun text-white font-semibold'=> request()->routeIs($route),
                'text-tinta/70' => ! request()->routeIs($route),
                ])>
                <svg class="size-6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="{{ $path }}" />
                </svg>
                {{ $label }}
            </a>
            @endforeach
        </div>
    </nav>
    @else
    <main class="min-h-full grid place-items-center p-4">
        {{ $slot }}
    </main>
    @endif

    @livewireScripts
    @stack('scripts')
</body>

</html>