{{-- resources/views/components/bottom-nav.blade.php --}}
@php
    $links = [
        [
            'route' => 'dashboard',
            'label' => 'Inicio',
            'svg' =>
                'M3 9.75L12 3l9 6.75m-1.5 8.25h.75A2.25 2.25 0 0022.5 16.5V10.5L12 3 1.5 10.5V16.5A2.25 2.25 0 003.75 18.75h.75M7.5 21h9',
        ],
        [
            'route' => 'clientes.index',
            'label' => 'Clientes',
            'svg' => 'M15 7.5a3 3 0 11-6 0 3 3 0 016 0zM19.5 21a4.5 4.5 0 00-9 0',
        ],
        [
            'route' => 'productos.index',
            'label' => 'Productos',
            'svg' => 'M4.5 6h15M4.5 12h15m-15 6h15',
        ],
        [
            'route' => 'facturas.index',
            'label' => 'Facturas',
            'svg' => 'M17.25 6.75L6.75 17.25m0-10.5h10.5v10.5',
        ],
        [
            'route' => 'proveedores.index',
            'label' => 'Proveedores',
            'svg' => 'M3 7.5h18M3 12h18M3 16.5h18',
        ],
    ];
@endphp

<div
    class="fixed inset-x-0 bottom-0 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 flex justify-around py-2 md:hidden z-50">
    @foreach ($links as $l)
        @php $active = request()->routeIs($l['route']); @endphp
        <a href="{{ route($l['route']) }}"
            class="flex flex-col items-center justify-center text-xs {{ $active ? 'text-emerald-600' : 'text-gray-600 dark:text-gray-300' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 {{ $active ? 'stroke-emerald-600' : '' }}"
                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $l['svg'] }}" />
            </svg>
            <span>{{ $l['label'] }}</span>
        </a>
    @endforeach
</div>
