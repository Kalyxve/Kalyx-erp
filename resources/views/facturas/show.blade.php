{{-- resources/views/facturas/show.blade.php --}}
<x-layout>
    @php
        $f = $factura->loadMissing(['cliente', 'vendedor', 'detalles.producto', 'pagos']);
        $cliente = $f->cliente;
        $vendedor = $f->vendedor;
        $pagadoUsd = (float) $f->pagos->reduce(function ($acc, $p) {
            $t = (float) ($p->tasa_usd ?? ($f->tasa_usd ?? 0));
            $usd = (float) ($p->monto_usd ?? 0);
            $bs = (float) ($p->monto_bs ?? 0);
            return $acc + $usd + ($t > 0 ? $bs / $t : 0);
        }, 0);
    @endphp

    <div class="max-w-5xl mx-auto bg-white dark:bg-gray-900 rounded-2xl shadow p-6 md:p-8">
        <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
                <img src="{{ asset('logo.png') }}" class="w-12 h-12 rounded bg-emerald-600/10 p-2" alt="Logo">
                <div>
                    <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Factura #{{ $f->id }}</h1>
                    <div class="text-sm text-gray-500">Emitida: {{ optional($f->fecha_emision)->format('Y-m-d') }}</div>
                </div>
            </div>
            <div class="text-right">
                <div>
                    @if ($f->estado === 'pagada')
                        <span class="px-3 py-1 rounded-full text-xs bg-emerald-100 text-emerald-800">PAGADA</span>
                    @elseif($f->estado === 'anulada')
                        <span class="px-3 py-1 rounded-full text-xs bg-rose-100 text-rose-800">ANULADA</span>
                    @else
                        <span class="px-3 py-1 rounded-full text-xs bg-yellow-100 text-yellow-800">PENDIENTE</span>
                    @endif
                    <span
                        class="ml-2 px-2 py-0.5 rounded text-xs bg-sky-100 text-sky-800">{{ strtoupper($f->tipo_documento ?? 'venta') }}</span>
                </div>
                <div class="mt-2 text-sm text-gray-500">Tasa: {{ number_format((float) $f->tasa_usd, 4) }} Bs/USD</div>
                <button onclick="window.print()"
                    class="mt-3 inline-flex items-center px-3 py-1.5 rounded-md border text-sm">
                    Imprimir
                </button>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2 rounded-xl border dark:border-gray-700 p-4">
                <h3 class="text-sm font-semibold mb-2">Cliente</h3>
                <div class="text-sm">
                    <div><b>{{ trim(($cliente->nombre ?? '') . ' ' . ($cliente->apellido ?? '')) }}</b></div>
                    <div class="text-gray-500">RIF/CI: {{ $cliente->rif ?? '—' }}</div>
                    <div class="text-gray-500">Email: {{ $cliente->email ?? '—' }}</div>
                    <div class="text-gray-500">Tel.: {{ $cliente->telefono ?? '—' }}</div>
                    <div class="text-gray-500">Dir.: {{ $cliente->direccion ?? '—' }}</div>
                </div>
            </div>
            <div class="rounded-xl border dark:border-gray-700 p-4">
                <h3 class="text-sm font-semibold mb-2">Vendedor</h3>
                <div class="text-sm">
                    <div><b>{{ $vendedor->nombre ?? '—' }}</b></div>
                    <div class="text-gray-500">Tel.: {{ $vendedor->telefono ?? '—' }}</div>
                </div>
            </div>
        </div>

        {{-- Detalle (solo si fue venta) --}}
        @if (($f->tipo_documento ?? 'venta') === 'venta')
            <div class="mt-6 rounded-xl border dark:border-gray-700 overflow-hidden">
                <table class="min-w-full">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr class="text-xs uppercase text-gray-600 dark:text-gray-300">
                            <th class="px-4 py-2 text-left">Producto</th>
                            <th class="px-4 py-2 text-right">Cant</th>
                            <th class="px-4 py-2 text-right">P. USD</th>
                            <th class="px-4 py-2 text-right">P. Bs</th>
                            <th class="px-4 py-2 text-right">Sub USD</th>
                            <th class="px-4 py-2 text-right">Sub Bs</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-gray-700">
                        @foreach ($f->detalles as $d)
                            <tr>
                                <td class="px-4 py-2">
                                    {{ $d->producto->codigo ?? '' }} {{ $d->producto->nombre ?? '' }}
                                    @if (($d->tasa_usd_item ?? null) && (float) $d->tasa_usd_item !== (float) $f->tasa_usd)
                                        <div class="text-[11px] text-gray-500">Tasa item:
                                            {{ number_format((float) $d->tasa_usd_item, 4) }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-right">{{ number_format((float) $d->cantidad, 3) }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format((float) $d->precio_unitario_usd, 2) }}
                                </td>
                                <td class="px-4 py-2 text-right">{{ number_format((float) $d->precio_unitario_bs, 2) }}
                                </td>
                                <td class="px-4 py-2 text-right">{{ number_format((float) $d->subtotal_usd, 2) }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format((float) $d->subtotal_bs, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="mt-6 rounded-xl border dark:border-gray-700 p-4 bg-amber-50 dark:bg-amber-900/20">
                <div class="text-sm">
                    <b>Pago directo:</b> Este documento no contiene items; el total corresponde a la suma de los pagos
                    registrados.
                </div>
            </div>
        @endif

        {{-- Totales --}}
        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                @if ($f->nota)
                    <div class="rounded-xl border dark:border-gray-700 p-4">
                        <h3 class="text-sm font-semibold mb-2">Notas</h3>
                        <div class="text-sm whitespace-pre-line">{{ $f->nota }}</div>
                    </div>
                @endif

                <div class="mt-4 rounded-xl border dark:border-gray-700 overflow-hidden">
                    <table class="min-w-full">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr class="text-xs uppercase text-gray-600 dark:text-gray-300">
                                <th class="px-4 py-2 text-left">Pagos</th>
                                <th class="px-4 py-2 text-right">USD</th>
                                <th class="px-4 py-2 text-right">Bs</th>
                                <th class="px-4 py-2 text-left">Ref / Extra</th>
                                <th class="px-4 py-2 text-left">Fecha</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-gray-700">
                            @forelse($f->pagos as $p)
                                <tr>
                                    <td class="px-4 py-2 text-sm">
                                        <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800">
                                            {{ strtoupper(str_replace('_', ' ', $p->metodo)) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-right text-sm">
                                        {{ $p->monto_usd ? number_format((float) $p->monto_usd, 2) : '—' }}</td>
                                    <td class="px-4 py-2 text-right text-sm">
                                        {{ $p->monto_bs ? number_format((float) $p->monto_bs, 2) : '—' }}</td>
                                    <td class="px-4 py-2 text-xs">
                                        @if ($p->referencia)
                                            <div><b>Ref:</b> {{ $p->referencia }}</div>
                                        @endif
                                        @if (is_array($p->extra))
                                            @foreach ($p->extra as $k => $v)
                                                <div>{{ ucfirst($k) }}: {{ is_scalar($v) ? $v : json_encode($v) }}
                                                </div>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-sm">{{ optional($p->fecha_pago)->format('Y-m-d') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-3 text-sm text-gray-500">Sin pagos registrados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-xl border dark:border-gray-700 p-4 space-y-2">
                <div class="flex items-center justify-between text-sm">
                    <span>Subtotal (USD)</span>
                    <b>{{ number_format((float) $f->total_usd, 2) }}</b>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span>Total (Bs)</span>
                    <b>{{ number_format((float) $f->total_bs, 2) }}</b>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span>Pagado (USD eq.)</span>
                    <b>{{ number_format($pagadoUsd, 2) }}</b>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span>Saldo (USD)</span>
                    <b>{{ number_format((float) $f->saldo_usd, 2) }}</b>
                </div>
            </div>
        </div>

        <div class="mt-6 text-xs text-gray-500">
            <div>Creada: {{ optional($f->created_at)->format('Y-m-d H:i') }} | Actualizada:
                {{ optional($f->updated_at)->format('Y-m-d H:i') }}</div>
            <div>Reporte 20/10 — generado por el sistema</div>
        </div>
    </div>
</x-layout>
