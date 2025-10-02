{{-- resources/views/pages/payments/show.blade.php --}}
<x-layout>
    @php
        $fmt = fn($n) => number_format((float) ($n ?? 0), 2, ',', '.');
        $fmt4 = fn($n) => number_format((float) ($n ?? 0), 4, ',', '.');
        $method = strtoupper($payment['method'] ?? '—');
        $date = \Carbon\Carbon::parse($payment['date'] ?? now())->format('d/m/Y');
        $rate = $fmt4($payment['rateUsed'] ?? 0);
        $ref = $payment['reference'] ?? '—';
        $items = $payment['items'] ?? [];
    @endphp

    @if ($justCreated)
        <div data-landing class="fixed inset-0 bg-white/95 dark:bg-gray-900/95 z-50 grid place-items-center">
            <div class="text-center">
                <img src="{{ asset('logo.png') }}" class="h-24 w-24 mx-auto animate-bounce" alt="logo">
                <h2 class="mt-4 text-2xl font-semibold text-emerald-700">¡Pago registrado!</h2>
                <p class="text-gray-600 dark:text-gray-300 mt-1">Listo para imprimir tu recibo.</p>
            </div>
            <script>
                setTimeout(() => document.querySelector('[data-landing]')?.remove(), 1100)
            </script>
        </div>
    @endif

    <div x-data="{
        copied: false,
        copy(t) { navigator.clipboard?.writeText(t).then(() => { this.copied = true;
                setTimeout(() => this.copied = false, 1200) }) }
    }"
        class="max-w-5xl mx-auto bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
        {{-- Header --}}
        <div class="flex items-center justify-between gap-4 p-5 border-b border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <img src="{{ asset('logo.png') }}" class="h-9 w-9 rounded" alt="logo">
                <div>
                    <h1 class="text-xl font-semibold">Detalle de pago</h1>
                    <p class="text-xs text-gray-500">ID: {{ $payment['id'] ?? '' }}</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                {{-- Método --}}
                <span
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium
                            bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5h18M3 12h18M3 16.5h18" />
                    </svg>
                    {{ $method }}
                </span>
                {{-- Fecha --}}
                <span
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium
                            bg-gray-50 text-gray-700 border border-gray-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M6 4.5v3m12-3v3M3.75 9h16.5M5.25 7.5h13.5a1.5 1.5 0 0 1 1.5 1.5v9a1.5 1.5 0 0 1-1.5 1.5H5.25A1.5 1.5 0 0 1 3.75 18V9a1.5 1.5 0 0 1 1.5-1.5z" />
                    </svg>
                    {{ $date }}
                </span>
                {{-- Referencia (copiar) --}}
                <button type="button" @click="copy('{{ $ref }}')"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium
                               bg-indigo-50 text-indigo-700 border border-indigo-200 hover:bg-indigo-100"
                    title="Copiar referencia">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2">
                        <rect x="9" y="9" width="11" height="11" rx="2" />
                        <path d="M5 15V5a2 2 0 0 1 2-2h10" />
                    </svg>
                    Ref: {{ $ref }}
                </button>
            </div>
        </div>

        {{-- Mini stats --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 p-5">
            <div class="rounded-xl border border-gray-100 dark:border-gray-700 p-3">
                <div class="text-xs text-gray-500">Subtotal</div>
                <div class="text-base font-semibold">$ {{ $fmt($payment['subtotalUSD'] ?? 0) }}</div>
            </div>
            <div class="rounded-xl border border-gray-100 dark:border-gray-700 p-3">
                <div class="text-xs text-gray-500">Descuento</div>
                <div class="text-base font-semibold">$ {{ $fmt($payment['discountUSD'] ?? 0) }}</div>
            </div>
            <div class="rounded-xl border border-gray-100 dark:border-gray-700 p-3">
                <div class="text-xs text-gray-500">Impuesto</div>
                <div class="text-base font-semibold">$ {{ $fmt($payment['taxUSD'] ?? 0) }}</div>
            </div>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-emerald-800">
                <div class="text-xs opacity-80">Total USD</div>
                <div class="text-base font-semibold">$ {{ $fmt($payment['amountUSD'] ?? 0) }}</div>
            </div>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-emerald-800">
                <div class="text-xs opacity-80">Total Bs</div>
                <div class="text-base font-semibold">Bs {{ $fmt($payment['amountVES'] ?? 0) }}</div>
            </div>
        </div>

        {{-- Dos columnas: Cliente / Pago --}}
        <div class="grid md:grid-cols-2 gap-4 px-5">
            <div class="rounded-xl border border-gray-100 dark:border-gray-700 p-4">
                <div class="flex items-center gap-2 font-semibold mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.75 7.5a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0zM4.5 20.25a8.25 8.25 0 1 1 15 0" />
                    </svg>
                    Cliente
                </div>
                <dl class="text-sm space-y-1">
                    <div>
                        <dt class="inline text-gray-500">Nombre:</dt>
                        <dd class="inline">{{ $client['name'] ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="inline text-gray-500">Email:</dt>
                        <dd class="inline">{{ $client['email'] ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="inline text-gray-500">Teléfono:</dt>
                        <dd class="inline">{{ $client['phone'] ?? '—' }}</dd>
                    </div>
                    @if (!empty($client['address']))
                        <div>
                            <dt class="inline text-gray-500">Dirección:</dt>
                            <dd class="inline">{{ $client['address'] }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <div class="rounded-xl border border-gray-100 dark:border-gray-700 p-4">
                <div class="flex items-center gap-2 font-semibold mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5h18M3 12h18M3 16.5h18" />
                    </svg>
                    Pago
                </div>
                <dl class="text-sm space-y-1">
                    <div>
                        <dt class="inline text-gray-500">Fecha:</dt>
                        <dd class="inline">{{ $date }}</dd>
                    </div>
                    <div>
                        <dt class="inline text-gray-500">Método:</dt>
                        <dd class="inline">{{ $method }}</dd>
                    </div>
                    <div>
                        <dt class="inline text-gray-500">Referencia:</dt>
                        <dd class="inline">{{ $ref }}</dd>
                    </div>
                    <div>
                        <dt class="inline text-gray-500">Moneda de registro:</dt>
                        <dd class="inline">{{ $payment['currency'] ?? 'USD' }}</dd>
                    </div>
                    <div>
                        <dt class="inline text-gray-500">Tasa BCV:</dt>
                        <dd class="inline">{{ $rate }} Bs/USD</dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Tabla de productos --}}
        <div class="px-5 mt-5">
            <div class="overflow-x-auto rounded-xl border border-gray-100 dark:border-gray-700">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-200">
                            <th class="px-3 py-2 text-left">Producto</th>
                            <th class="px-3 py-2">SKU</th>
                            <th class="px-3 py-2">Precio USD</th>
                            <th class="px-3 py-2">Cant.</th>
                            <th class="px-3 py-2">Total USD</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $it)
                            <tr class="border-t border-gray-100 dark:border-gray-700">
                                <td class="px-3 py-2">{{ $it['name'] ?? '—' }}</td>
                                <td class="px-3 py-2">{{ $it['sku'] ?? '—' }}</td>
                                <td class="px-3 py-2">$ {{ $fmt($it['priceUSD'] ?? 0) }}</td>
                                <td class="px-3 py-2">{{ $it['qty'] ?? 0 }}</td>
                                <td class="px-3 py-2">$ {{ $fmt($it['totalUSD'] ?? 0) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-4 text-center text-gray-500">Sin items…</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if (count($items))
                        <tfoot>
                            <tr class="bg-gray-50/60 dark:bg-gray-700/40 font-medium">
                                <td colspan="4" class="px-3 py-2 text-right">Total</td>
                                <td class="px-3 py-2">$ {{ $fmt($payment['amountUSD'] ?? 0) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        {{-- Notas --}}
        <div class="px-5 mt-5 mb-2">
            <div class="rounded-xl border border-gray-100 dark:border-gray-700 p-4">
                <div class="flex items-center gap-2 font-semibold mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 6h15M4.5 12h15M4.5 18h15" />
                    </svg>
                    Notas
                </div>
                <div class="text-gray-700 dark:text-gray-300 text-sm">
                    {{ $payment['notes'] ?? '—' }}
                </div>
            </div>
        </div>

        {{-- Barra de acciones --}}
        <div class="flex flex-wrap items-center gap-3 px-5 pb-5">
            <a href="{{ route('payments.receipt.pdf', $payment['id']) }}"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-md bg-emerald-600 hover:bg-emerald-700 text-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M19.5 14.25v3.75a1.5 1.5 0 0 1-1.5 1.5h-12a1.5 1.5 0 0 1-1.5-1.5v-3.75M7.5 10.5 12 15l4.5-4.5M12 3v12" />
                </svg>
                Imprimir PDF
            </a>
            <a href="{{ route('payments.index') }}"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12l7.5-7.5M3 12h18" />
                </svg>
                Volver
            </a>

            {{-- Toast copiar referencia --}}
            <div x-show="copied" x-transition
                class="ml-auto text-xs px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                ¡Referencia copiada!
            </div>
        </div>
    </div>
</x-layout>
