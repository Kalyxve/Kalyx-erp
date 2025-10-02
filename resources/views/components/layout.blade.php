{{-- resources/views/components/layout.blade.php --}}
@props([
    // No referenciar $settings aquí; define defaults directos
    'settings' => [
        'currencyDefault' => 'USD',
        'exchangeRate' => 40,
    ],
])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ sidebarOpen: false }" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Kalyx ERP') }}</title>

    {{-- CSRF en <head> para Axios/Fetch --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- SOLO Vite. Nada de CDN de Alpine. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] {
            display: none !important
        }
    </style>
</head>

<body class="h-full bg-emerald-50/30 dark:bg-gray-900">
    <x-bottom-nav />

    <div class="flex flex-col min-h-screen">
        {{-- HEADER --}}
        <header class="sticky top-0 z-30 bg-emerald-600 text-white shadow-sm">
            <div class="flex items-center justify-between px-4 py-3">
                <div class="flex items-center gap-3">
                    <button class="md:hidden text-white/90" @click="sidebarOpen = !sidebarOpen"
                        aria-label="Toggle Menu">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>

                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                        <img src="{{ asset('logo.png') }}" alt="Logo"
                            class="h-8 w-8 rounded bg-white/10 p-1 ring-1 ring-white/20">
                        <span class="font-semibold text-lg">{{ config('app.name', 'Kalyx ERP') }}</span>
                    </a>
                </div>

                {{-- Acciones md+ --}}
                <div class="hidden md:flex items-center gap-3" x-data="headerBcvRate()" x-init="init()">
                    @auth
                        <span class="rounded-md bg-white/15 px-3 py-1 text-sm">{{ auth()->user()->email }}</span>
                    @endauth

                    <div class="flex items-center gap-2">
                        <span class="text-sm bg-white/15 px-3 py-1 rounded-md">
                            BCV:
                            <template x-if="loading">
                                <svg class="inline w-3 h-3 animate-spin" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"
                                        fill="none" />
                                </svg>
                            </template>
                            <span
                                x-text="rate ? (Number(rate).toLocaleString('es-VE',{minimumFractionDigits:2}) + ' Bs/USD') : '–'"></span>
                        </span>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            class="h-9 px-3 rounded-md bg-red-600 hover:bg-red-700 text-white text-sm font-semibold flex items-center gap-1 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l3 3m0 0l-3 3m3-3H3" />
                            </svg>
                            Salir
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <div class="flex flex-1">
            {{-- SIDEBAR DESKTOP --}}
            <nav
                class="hidden md:block w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 p-4">
                @php
                    $items = [
                        ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'M3 3l9 7.5L21 3v18H3z'],
                        [
                            'label' => 'Clientes',
                            'route' => 'clientes.index',
                            'icon' => 'M15 7.5a3 3 0 11-6 0 3 3 0 016 0z M19.5 21a4.5 4.5 0 00-9 0',
                        ],
                        [
                            'label' => 'Productos',
                            'route' => 'productos.index',
                            'icon' => 'M4.5 6h15M4.5 12h15m-15 6h15',
                        ],
                        [
                            'label' => 'Proveedores',
                            'route' => 'proveedores.index',
                            'icon' => 'M3 7.5h18M3 12h18M3 16.5h18',
                        ],
                        ['label' => 'Facturas', 'route' => 'facturas.index', 'icon' => 'M3 12l6 6 12-12'],
                        ['label' => 'Compras', 'route' => 'compras.index', 'icon' => 'M3 7.5h18M3 12h18M3 16.5h18'],
                    ];
                @endphp
                <ul class="space-y-1">
                    @foreach ($items as $it)
                        @php $active = request()->routeIs($it['route']); @endphp
                        <li>
                            <a href="{{ route($it['route']) }}"
                                class="flex items-center gap-3 px-3 py-2 rounded-lg transition
                               {{ $active ? 'bg-emerald-600 text-white shadow-sm' : 'text-gray-700 dark:text-gray-200 hover:bg-emerald-50 dark:hover:bg-gray-700' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path d="{{ $it['icon'] }}" />
                                </svg>
                                <span>{{ __($it['label']) }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            {{-- OVERLAY SIDEBAR MÓVIL --}}
            <div x-show="sidebarOpen" class="fixed inset-0 z-40 md:hidden" @click="sidebarOpen=false">
                <div class="absolute inset-0 bg-black/40"></div>
            </div>

            {{-- SIDEBAR MÓVIL --}}
            <nav x-show="sidebarOpen" class="fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-gray-800 p-4 md:hidden"
                @click.away="sidebarOpen=false">
                <ul class="space-y-1 mb-4">
                    @foreach ($items as $it)
                        <li>
                            <a href="{{ route($it['route']) }}"
                                class="block px-3 py-2 rounded-lg hover:bg-emerald-50 dark:hover:bg-gray-700">
                                {{ __($it['label']) }}
                            </a>
                        </li>
                    @endforeach
                </ul>

                <div class="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-3">
                    @auth
                        <div class="text-sm text-gray-600 dark:text-gray-300">
                            <div class="font-semibold">Cuenta</div>
                            <div>{{ auth()->user()->email }}</div>
                        </div>
                    @endauth

                    <div x-data="headerBcvRate()" x-init="init()" class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">BCV (Bs/USD)</label>
                        <div class="flex items-center gap-2">
                            <span
                                class="flex-1 h-9 px-2 flex items-center rounded-md bg-gray-50 dark:bg-gray-700 dark:text-gray-100 border border-gray-300 dark:border-gray-600 text-sm">
                                <template x-if="loading">
                                    <svg class="inline w-4 h-4 animate-spin" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="3" fill="none" />
                                    </svg>
                                </template>
                                <span x-show="!loading"
                                    x-text="rate ? (Number(rate).toLocaleString('es-VE',{minimumFractionDigits:2}) + ' Bs/USD') : '–'"></span>
                            </span>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            class="w-full h-9 px-3 rounded-md bg-red-600 hover:bg-red-700 text-white text-sm font-semibold transition">
                            Salir
                        </button>
                    </form>
                </div>
            </nav>

            {{-- CONTENIDO --}}
            <main class="flex-1 p-4 md:p-6 overflow-y-auto">
                {{ $slot }}
            </main>
        </div>

        <footer class="bg-white dark:bg-gray-800 text-center p-4 border-t border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-600 dark:text-gray-400">&copy; {{ date('Y') }}
                {{ config('app.name', 'Kalyx ERP') }}. Todos los derechos reservados.</p>
        </footer>
    </div>
</body>

</html>
