{{-- resources/views/pages/dashboard.blade.php --}}
<x-layout :settings="$settings ?? []">
    {{-- Header --}}
    <section class="card section mb-6">
        <h1 class="text-3xl font-bold text-emerald-700">Panel principal</h1>
        <p class="text-gray-500">Resumen general del sistema</p>
    </section>

    @php
        $exchange = (float) ($settings['exchangeRate'] ?? 40.0);
        $k = $kpis ?? [
            'salesMonthUSD' => 0,
            'avgTicketUSD' => 0,
            'activeClients' => 0,
            'lowStockCount' => 0,
        ];
    @endphp

    {{-- KPIs --}}
    <section class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="card p-4">
            <div class="text-sm text-gray-500">Ventas del mes (USD)</div>
            <div class="mt-2 text-2xl font-semibold text-emerald-600">
                ${{ number_format($k['salesMonthUSD'], 2) }}
            </div>
            <div class="text-xs text-gray-500">
                Bs {{ number_format($k['salesMonthUSD'] * $exchange, 2, ',', '.') }}
            </div>
        </div>

        <div class="card p-4">
            <div class="text-sm text-gray-500">Ticket promedio (USD)</div>
            <div class="mt-2 text-2xl font-semibold text-emerald-600">
                ${{ number_format($k['avgTicketUSD'], 2) }}
            </div>
            <div class="text-xs text-gray-500">
                Bs {{ number_format($k['avgTicketUSD'] * $exchange, 2, ',', '.') }}
            </div>
        </div>

        <div class="card p-4">
            <div class="text-sm text-gray-500">Clientes activos (90 días)</div>
            <div class="mt-2 text-2xl font-semibold text-emerald-600">
                {{ $k['activeClients'] }}
            </div>
        </div>

        <div class="card p-4">
            <div class="text-sm text-gray-500">Productos con bajo stock</div>
            <div class="mt-2 text-2xl font-semibold text-emerald-600">
                {{ $k['lowStockCount'] }}
            </div>
        </div>
    </section>

    {{-- Gráficos --}}
    <section class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="card p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-lg">Ventas por semana</h3>
            </div>
            <div id="chart-weekly-sales" class="h-64"></div>
        </div>

        <div class="card p-5">
            <h3 class="font-semibold text-lg mb-3">Top 5 productos (unidades)</h3>
            <div id="chart-top-products" class="h-64"></div>
        </div>
    </section>

    {{-- Data para JS --}}
    <script>
        window.kalyxDashboardData = {
            semanal: {
                labels: @json($chartWeekly['labels'] ?? []),
                data: @json($chartWeekly['data'] ?? []),
            },
            topProductos: {
                labels: @json($chartTop['labels'] ?? []),
                data: @json($chartTop['data'] ?? []),
            }
        };
    </script>

    @vite('resources/js/pages/dashboard.js')
</x-layout>
