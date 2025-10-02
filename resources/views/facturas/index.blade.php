{{-- resources/views/facturas/index.blade.php --}}
<x-layout>
    <div x-data="facturasPage($el)" x-init="init()" x-cloak class="space-y-4"
        data-initial='@json($facturas)' data-list-url="{{ route('facturas.list') }}"
        data-store-url="{{ route('facturas.store') }}" data-abonar-url-base="{{ url('facturas') }}" {{-- /{id}/pago --}}
        data-anular-url-base="{{ url('facturas') }}" {{-- /{id}/anular --}}
        data-clientes-list-url="{{ route('clientes.list') }}" data-productos-list-url="{{ route('productos.list') }}"
        data-clientes-store-url="{{ route('clientes.store') }}"
        data-productos-store-url="{{ route('productos.store') }}"
        data-vendedores-list-url="{{ route('vendedores.list') }}"
        data-vendedores-store-url="{{ route('vendedores.store') }}" data-bcv-url="/api/bcv-rate">

        {{-- Header --}}
        <div class="mb-5 flex items-center justify-between">
            <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Facturas y Pagos</h1>
            <button @click="openCreate()"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nueva factura / pago
            </button>
        </div>

        {{-- Filtros --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div class="md:col-span-2">
                <input type="text" x-model.debounce.350ms="q" placeholder="Buscar por cliente o RIF"
                    class="w-full h-10 px-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800">
            </div>
            <div>
                <select x-model="estado"
                    class="h-10 px-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800">
                    <option value="">Todas</option>
                    <option value="pendiente">Pendientes</option>
                    <option value="pagada">Pagadas</option>
                    <option value="anulada">Anuladas</option>
                </select>
            </div>
            <div class="flex items-center gap-2 justify-end text-sm">
                <span
                    class="px-3 py-1 rounded-md bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                    Tasa BCV:
                    <template x-if="tasaLoading">
                        <svg class="inline w-4 h-4 animate-spin" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"
                                fill="none" />
                        </svg>
                    </template>
                    <span x-show="!tasaLoading"
                        x-text="tasaActual ? (Number(tasaActual).toLocaleString('es-VE',{minimumFractionDigits:2}) + ' Bs/USD') : '—'"></span>
                </span>
                <button @click="fetchTasa()" class="h-9 px-3 rounded-md border dark:border-gray-600">Refrescar</button>
            </div>
        </div>

        {{-- Tabla --}}
        <div
            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/40">
                        <tr class="text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                            <th class="px-4 py-3">ID</th>
                            <th class="px-4 py-3">Cliente</th>
                            <th class="px-4 py-3">Vendedor</th>
                            <th class="px-4 py-3">Tipo</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3 text-right">Total (USD)</th>
                            <th class="px-4 py-3 text-right">Saldo (USD)</th>
                            <th class="px-4 py-3">Emisión</th>
                            <th class="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <template x-if="items.length === 0">
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No hay
                                    facturas.</td>
                            </tr>
                        </template>

                        <template x-for="f in items" :key="f.id">
                            <tr class="hover:bg-emerald-50/40 dark:hover:bg-gray-700/40">
                                <td class="px-4 py-3 font-mono">#<span x-text="f.id"></span></td>
                                <td class="px-4 py-3" x-text="f.cliente"></td>
                                <td class="px-4 py-3" x-text="f.vendedor ?? '—'"></td>
                                <td class="px-4 py-3">
                                    <span class="text-xs px-2 py-0.5 rounded bg-sky-100 text-sky-800"
                                        x-text="f.tipo_documento ?? 'venta'"></span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                        :class="f.estado === 'pagada' ?
                                            'bg-emerald-100 text-emerald-800' :
                                            (f.estado === 'anulada' ?
                                                'bg-rose-100 text-rose-800' :
                                                'bg-yellow-100 text-yellow-800')">
                                        <span x-text="f.estado"></span>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right" x-text="(f.total_usd ?? 0).toFixed(2)"></td>
                                <td class="px-4 py-3 text-right" x-text="(f.saldo_usd ?? 0).toFixed(2)"></td>
                                <td class="px-4 py-3" x-text="f.fecha_emision ?? '—'"></td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">

                                        {{-- PENDIENTE --}}
                                        <template x-if="f.estado === 'pendiente'">
                                            <button @click="openAbono(f)"
                                                class="h-9 px-3 rounded-md border dark:border-gray-600">
                                                Abonar
                                            </button>
                                        </template>

                                        {{-- ANULAR: disponible si no está anulada --}}
                                        <button @click="anular(f)"
                                            class="h-9 px-3 rounded-md border dark:border-gray-600"
                                            :disabled="f.estado === 'anulada'">
                                            Anular
                                        </button>

                                        {{-- PAGADA: sólo Ver y Editar (y Anular arriba) --}}
                                        {{-- ANULADA: sólo Ver --}}
                                        <a :href="`{{ url('facturas') }}/${f.id}`"
                                            class="h-9 px-3 rounded-md border dark:border-gray-600 inline-flex items-center">
                                            Ver
                                        </a>

                                        <template x-if="f.estado !== 'anulada'">
                                            <a :href="`{{ url('facturas') }}/${f.id}/edit`"
                                                class="h-9 px-3 rounded-md border dark:border-gray-600 inline-flex items-center">
                                                Editar
                                            </a>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Paginación --}}
            <div class="p-3 flex items-center justify-between gap-3">
                <div class="text-sm text-gray-600 dark:text-gray-300">
                    <span x-text="`Mostrando ${items.length} de ${meta.total} registros`"></span>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="prevPage" :disabled="meta.current_page <= 1"
                        class="px-3 py-1.5 rounded-md border dark:border-gray-600 text-sm disabled:opacity-50">Anterior</button>
                    <span class="text-sm" x-text="`Página ${meta.current_page} de ${meta.last_page}`"></span>
                    <button @click="nextPage" :disabled="meta.current_page >= meta.last_page"
                        class="px-3 py-1.5 rounded-md border dark:border-gray-600 text-sm disabled:opacity-50">Siguiente</button>
                </div>
            </div>
        </div>

        {{-- ===== Modal CREAR (venta o pago directo) — Revisado para PC/Móvil ===== --}}
        <div x-show="createOpen" x-transition.opacity class="fixed inset-0 z-[60] bg-black/40 overflow-y-auto"
            @keydown.escape.window="closeCreate" aria-modal="true" role="dialog">
            <!-- wrapper para centrar en desktop y ocupar pantalla en móvil -->
            <div class="min-h-full w-full flex items-start justify-center p-2 md:p-6">
                <!-- panel -->
                <div
                    class="w-full md:max-w-5xl bg-white dark:bg-gray-800 rounded-none md:rounded-2xl shadow-2xl ring-1 ring-black/5">
                    <div class="flex flex-col">
                        {{-- Header (sticky) --}}
                        <div
                            class="sticky top-0 z-10 px-4 md:px-6 py-3 border-b bg-white/95 dark:bg-gray-800/95 backdrop-blur">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <img src="{{ asset('logo.png') }}" class="w-7 h-7 rounded bg-emerald-600/10 p-1"
                                        alt="Logo">
                                    <div>
                                        <h3 class="text-lg font-semibold">Nueva factura / pago</h3>
                                        <p class="text-[11px] text-gray-500">Ctrl/⌘ + Enter para guardar</p>
                                    </div>
                                </div>
                                <button @click="closeCreate"
                                    class="h-9 px-3 rounded-md border dark:border-gray-600">Cerrar</button>
                            </div>
                        </div>

                        {{-- Body (contenido; dejamos que el overlay haga el scroll) --}}
                        <div class="px-4 md:px-6 py-4">
                            {{-- Cabecera: cliente + fechas + vendedor + tipo --}}
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                                {{-- Cliente --}}
                                <div class="md:col-span-2">
                                    <label class="block text-sm mb-1">Cliente *</label>
                                    <div x-data="comboRemote({
                                        endpoint: '{{ route('clientes.list') }}',
                                        labelFn: (c) => `${c.nombre ?? ''} ${c.apellido ?? ''} (${c.rif ?? '—'})`,
                                        onSelect: (c) => { form.cliente_id = c.id; }
                                    })" class="relative">
                                        <div class="flex gap-2">
                                            <input x-model="query" @focus="open=true; search()"
                                                @input.debounce.250ms="search()" @keydown.arrow-down.prevent="move(1)"
                                                @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="choose()"
                                                placeholder="Buscar cliente…"
                                                class="w-full h-10 px-3 rounded-md border dark:border-gray-600 bg-white dark:bg-gray-900">
                                            <button type="button" @click="openNewCliente"
                                                class="px-3 rounded-md border dark:border-gray-600">Nuevo</button>
                                        </div>
                                        <div x-show="open" @click.outside="close()"
                                            class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-900 border dark:border-gray-700 rounded-md shadow-xl max-h-60 overflow-auto">
                                            <template x-for="(opt,i) in options" :key="opt.id">
                                                <button type="button"
                                                    class="w-full text-left px-3 py-2 hover:bg-emerald-50 dark:hover:bg-gray-700"
                                                    :class="{ 'bg-emerald-50 dark:bg-gray-700': i === cursor }"
                                                    @mouseenter="cursor=i" @click="choose(i)">
                                                    <div class="font-medium" x-text="render(opt)"></div>
                                                    <div class="text-xs text-gray-500" x-text="opt.email ?? ''"></div>
                                                </button>
                                            </template>
                                            <div x-show="loading" class="px-3 py-2 text-sm text-gray-500">Buscando…
                                            </div>
                                            <div x-show="!loading && !options.length"
                                                class="px-3 py-2 text-sm text-gray-500">Sin resultados</div>
                                        </div>
                                    </div>
                                    <p class="text-xs text-emerald-700 mt-1" x-show="form.cliente_id">Cliente
                                        seleccionado ✔</p>
                                </div>

                                {{-- Fecha emisión --}}
                                <div>
                                    <label class="block text-sm mb-1">Fecha emisión *</label>
                                    <input type="date" x-model="form.fecha_emision"
                                        class="w-full h-10 px-3 rounded-md border dark:border-gray-600 bg-white dark:bg-gray-900">
                                </div>

                                {{-- Vence --}}
                                <div>
                                    <label class="block text-sm mb-1">Vence</label>
                                    <input type="date" x-model="form.fecha_vencimiento"
                                        class="w-full h-10 px-3 rounded-md border dark:border-gray-600 bg-white dark:bg-gray-900">
                                </div>

                                {{-- Vendedor --}}
                                <div class="md:col-span-2">
                                    <label class="block text-sm mb-1">Vendedor</label>
                                    <div x-data="comboRemote({
                                        endpoint: '{{ route('vendedores.list') }}',
                                        labelFn: (v) => `${v.nombre} ${v.telefono ? '('+v.telefono+')' : ''}`,
                                        onSelect: (v) => { form.vendedor_id = v.id; }
                                    })" class="relative">
                                        <div class="flex gap-2">
                                            <input x-model="query" @focus="open=true; search()"
                                                @input.debounce.250ms="search()" @keydown.arrow-down.prevent="move(1)"
                                                @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="choose()"
                                                placeholder="Buscar vendedor…"
                                                class="w-full h-10 px-3 rounded-md border dark:border-gray-600 bg-white dark:bg-gray-900">
                                            <button type="button" @click="openNewVendedor"
                                                class="px-3 rounded-md border dark:border-gray-600">Nuevo</button>
                                        </div>
                                        <div x-show="open" @click.outside="close()"
                                            class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-900 border dark:border-gray-700 rounded-md shadow-xl max-h-60 overflow-auto">
                                            <template x-for="(opt,i) in options" :key="opt.id">
                                                <button type="button"
                                                    class="w-full text-left px-3 py-2 hover:bg-emerald-50 dark:hover:bg-gray-700"
                                                    :class="{ 'bg-emerald-50 dark:bg-gray-700': i === cursor }"
                                                    @mouseenter="cursor=i" @click="choose(i)">
                                                    <div class="font-medium" x-text="render(opt)"></div>
                                                </button>
                                            </template>
                                            <div x-show="loading" class="px-3 py-2 text-sm text-gray-500">Buscando…
                                            </div>
                                            <div x-show="!loading && !options.length"
                                                class="px-3 py-2 text-sm text-gray-500">Sin resultados</div>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">Opcional. Si no seleccionas, queda en blanco.
                                    </p>
                                </div>

                                {{-- Tipo --}}
                                <div class="md:col-span-2">
                                    <label class="block text-sm mb-1">Tipo</label>
                                    <div class="flex gap-2">
                                        <label
                                            class="inline-flex items-center gap-2 px-3 py-2 rounded-md border cursor-pointer"
                                            :class="form.tipo_documento === 'venta' ? 'border-emerald-500' :
                                                'dark:border-gray-600'">
                                            <input type="radio" class="hidden" value="venta"
                                                x-model="form.tipo_documento">
                                            <span>Venta</span>
                                        </label>
                                        <label
                                            class="inline-flex items-center gap-2 px-3 py-2 rounded-md border cursor-pointer"
                                            :class="form.tipo_documento === 'pago_directo' ? 'border-emerald-500' :
                                                'dark:border-gray-600'">
                                            <input type="radio" class="hidden" value="pago_directo"
                                                x-model="form.tipo_documento">
                                            <span>Pago directo</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {{-- Productos (solo en venta) --}}
                            <template x-if="form.tipo_documento === 'venta'">
                                <div class="rounded-xl border dark:border-gray-700 p-3 mt-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="text-sm font-medium">Productos</div>
                                        <div class="flex items-center gap-2 text-sm">
                                            <span
                                                class="px-2 py-1 rounded bg-emerald-50 dark:bg-emerald-900/30">Tasa</span>
                                            <input type="number" step="0.0001" x-model.number="form.tasa_usd"
                                                class="h-8 w-28 px-2 rounded-md border dark:border-gray-600"
                                                placeholder="Bs/USD">
                                            <button @click="usarTasaActual"
                                                class="px-3 h-8 rounded-md border dark:border-gray-600">
                                                Usar BCV
                                            </button>
                                        </div>
                                    </div>

                                    <div class="space-y-3">
                                        <template x-for="(it, idx) in form.items" :key="idx">
                                            <div
                                                class="grid grid-cols-1 md:grid-cols-12 gap-2 items-start border-t pt-3 first:border-none first:pt-0 dark:border-gray-700">
                                                {{-- Producto combobox --}}
                                                <div class="md:col-span-5">
                                                    <label class="block text-xs text-gray-500 mb-1">Producto</label>
                                                    <div x-data="comboRemote({
                                                        endpoint: '{{ route('productos.list') }}',
                                                        labelFn: (p) => `${p.codigo ?? ''} ${p.nombre}`,
                                                        onSelect: (p) => selectProducto(idx, p)
                                                    })" class="relative">
                                                        <div class="flex gap-2">
                                                            <input x-model="query" @focus="open=true; search()"
                                                                @input.debounce.250ms="search()"
                                                                @keydown.arrow-down.prevent="move(1)"
                                                                @keydown.arrow-up.prevent="move(-1)"
                                                                @keydown.enter.prevent="choose()"
                                                                placeholder="Buscar…"
                                                                class="h-10 px-3 rounded-md border dark:border-gray-600 bg-white dark:bg-gray-900 w-full">
                                                            <button type="button" @click="openNewProducto(idx)"
                                                                class="px-3 rounded-md border dark:border-gray-600">Nuevo</button>
                                                        </div>
                                                        <div x-show="open" @click.outside="close()"
                                                            class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-900 border dark:border-gray-700 rounded-md shadow-xl max-h-56 overflow-auto">
                                                            <template x-for="(opt,i) in options"
                                                                :key="opt.id">
                                                                <button type="button"
                                                                    class="w-full text-left px-3 py-2 hover:bg-emerald-50 dark:hover:bg-gray-700"
                                                                    :class="{ 'bg-emerald-50 dark:bg-gray-700': i === cursor }"
                                                                    @mouseenter="cursor=i" @click="choose(i)">
                                                                    <div class="font-medium" x-text="render(opt)">
                                                                    </div>
                                                                </button>
                                                            </template>
                                                            <div x-show="loading"
                                                                class="px-3 py-2 text-sm text-gray-500">Buscando…</div>
                                                            <div x-show="!loading && !options.length"
                                                                class="px-3 py-2 text-sm text-gray-500">Sin resultados
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="text-xs text-gray-500 mt-1" x-show="it.producto_id">
                                                        Sel: <span x-text="it.nombre"></span>
                                                    </div>
                                                </div>

                                                <div class="md:col-span-2">
                                                    <label class="block text-xs text-gray-500 mb-1">Cant</label>
                                                    <input type="number" min="0.001" step="0.001"
                                                        x-model.number="it.cantidad"
                                                        class="h-10 w-full px-3 rounded-md border dark:border-gray-600 bg-white dark:bg-gray-900">
                                                </div>

                                                <div class="md:col-span-2">
                                                    <label class="block text-xs text-gray-500 mb-1">P. USD</label>
                                                    <input type="number" step="0.01"
                                                        x-model="it.precio_unitario_usd"
                                                        class="h-10 w-full px-3 rounded-md border dark:border-gray-600 bg-white dark:bg-gray-900">
                                                </div>

                                                <div class="md:col-span-2">
                                                    <label class="block text-xs text-gray-500 mb-1">Sub USD</label>
                                                    <div
                                                        class="h-10 flex items-center px-3 rounded-md border bg-gray-50 dark:bg-gray-900 dark:border-gray-700">
                                                        <span
                                                            x-text="fmtUsd(toNum(it.cantidad) * toNum(it.precio_unitario_usd))"></span>
                                                    </div>
                                                </div>

                                                <div class="md:col-span-1 flex md:justify-end">
                                                    <button @click="removeItem(idx)"
                                                        class="px-2 h-8 text-xs rounded-md bg-red-600 text-white">Quitar</button>
                                                </div>

                                                <div class="md:col-span-12 text-xs text-gray-600 dark:text-gray-300">
                                                    Subtotal Bs:
                                                    <b
                                                        x-text="fmtBs((toNum(it.cantidad) * toNum(it.precio_unitario_usd)) * (toNum(it.tasa_usd_item) || toNum(form.tasa_usd)))"></b>
                                                </div>
                                            </div>
                                        </template>
                                    </div>

                                    <div class="mt-3">
                                        <button @click="addItem"
                                            class="px-3 h-9 rounded-md border dark:border-gray-600">Agregar
                                            producto</button>
                                    </div>

                                    <div class="mt-3 text-right text-xs text-gray-600">
                                        Subtotal USD: <b x-text="fmtUsd(subtotalUsd())"></b>
                                    </div>
                                </div>
                            </template>

                            {{-- Nota + Totales --}}
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-4">
                                <div class="md:col-span-2">
                                    <label class="block text-sm mb-1">Nota (opcional)</label>
                                    <textarea x-model="form.nota" rows="3"
                                        class="w-full px-3 py-2 rounded-md border dark:border-gray-600 bg-white dark:bg-gray-900"></textarea>
                                </div>

                                <div class="rounded-xl border dark:border-gray-700 p-3 space-y-2">
                                    <div class="flex items-center justify-between text-sm">
                                        <span
                                            x-text="form.tipo_documento==='venta' ? 'Total (USD)' : 'Total por pagos (USD)'"></span>
                                        <b x-text="fmtUsd(totalUsd())"></b>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span>Total (Bs)</span>
                                        <b x-text="fmtBs(totalUsd() * toNum(form.tasa_usd))"></b>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2 pt-2 border-t dark:border-gray-700">
                                        <div class="text-sm">
                                            <div class="flex items-center justify-between">
                                                <span>Pagado (USD eq.)</span>
                                                <b x-text="fmtUsd(pagadoUsd())"></b>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span>Saldo (USD)</span>
                                                <b x-text="fmtUsd(saldoUsd())"></b>
                                            </div>
                                        </div>
                                        <div class="text-sm">
                                            <label class="block text-xs text-gray-500 mb-1">Vuelto</label>
                                            <div class="flex items-center gap-2">
                                                <select x-model="vueltoEn"
                                                    class="h-9 px-2 rounded-md border dark:border-gray-600 w-24">
                                                    <option value="usd">USD</option>
                                                    <option value="bs">Bs</option>
                                                </select>
                                                <b x-text="vueltoTexto()"></b>
                                            </div>
                                        </div>
                                    </div>
                                    <label class="mt-2 flex items-center gap-2 text-xs">
                                        <input type="checkbox" x-model="form.permitir_pendiente"
                                            class="rounded border-gray-300 dark:border-gray-600">
                                        Permitir crear cuenta pendiente si el pago no cubre el total.
                                    </label>
                                </div>
                            </div>

                            {{-- Pagos --}}
                            <div class="rounded-xl border dark:border-gray-700 p-3 mt-4">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm font-medium">Pagos</div>
                                    <button @click="addPago"
                                        class="px-3 h-8 rounded-md border dark:border-gray-600">Agregar pago</button>
                                </div>

                                <div class="mt-2 space-y-2">
                                    <template x-for="(pg, i) in form.pagos" :key="i">
                                        <div class="grid grid-cols-1 md:grid-cols-12 gap-2 p-2 rounded-md border dark:border-gray-700"
                                            x-effect="isUsd(pg.metodo) ? (pg.monto_bs = 0) : (pg.monto_usd = 0)">
                                            <div class="md:col-span-2">
                                                <label class="block text-xs text-gray-500 mb-1">Método</label>
                                                <select x-model="pg.metodo"
                                                    @change="normalizePagos(); $nextTick(()=>{ isUsd(pg.metodo) ? $refs['usd'+i]?.focus() : $refs['bs'+i]?.focus() })"
                                                    class="h-9 px-2 rounded-md border dark:border-gray-600 w-full">
                                                    <option value="efectivo_usd">Efectivo USD</option>
                                                    <option value="efectivo_bs">Efectivo Bs</option>
                                                    <option value="zelle">Zelle</option>
                                                    <option value="pmovil">Pago Móvil</option>
                                                    <option value="transferencia">Transferencia</option>
                                                    <option value="pos">Punto de Venta</option>
                                                    <option value="loteria">Lotería</option>
                                                    <option value="favor_vecina">Favor vecina</option>
                                                </select>
                                            </div>

                                            <div class="md:col-span-2" x-show="isUsd(pg.metodo)">
                                                <label class="block text-xs text-gray-500 mb-1">Monto USD</label>
                                                <input :x-ref="'usd' + i" type="number" step="0.01"
                                                    x-model="pg.monto_usd" @input="normalizePagos()"
                                                    class="h-9 px-2 rounded-md border dark:border-gray-600 w-full">
                                            </div>

                                            <div class="md:col-span-2" x-show="isBs(pg.metodo)">
                                                <label class="block text-xs text-gray-500 mb-1">Monto Bs</label>
                                                <input :x-ref="'bs' + i" type="number" step="0.01"
                                                    x-model="pg.monto_bs" @input="normalizePagos()"
                                                    class="h-9 px-2 rounded-md border dark:border-gray-600 w-full">
                                            </div>

                                            <div class="md:col-span-2">
                                                <label class="block text-xs text-gray-500 mb-1">Tasa</label>
                                                <input type="text" x-model="pg.tasa_usd"
                                                    placeholder="Vacío=Factura" @input="normalizePagos()"
                                                    class="h-9 px-2 rounded-md border dark:border-gray-600 w-full">
                                            </div>

                                            <div class="md:col-span-3">
                                                <label class="block text-xs text-gray-500 mb-1">Referencia</label>
                                                <input type="text" x-model="pg.referencia"
                                                    class="h-9 px-2 rounded-md border dark:border-gray-600 w-full">
                                            </div>

                                            <div class="md:col-span-1 flex items-end justify-end">
                                                <button @click="removePago(i)"
                                                    class="px-2 h-8 text-xs rounded-md bg-red-600 text-white">Quitar</button>
                                            </div>

                                            {{-- EXTRA POS --}}
                                            <template x-if="pg.metodo === 'pos'">
                                                <div class="md:col-span-12 grid grid-cols-1 md:grid-cols-6 gap-2">
                                                    <input class="h-9 px-2 rounded-md border dark:border-gray-600"
                                                        placeholder="Voucher" x-model="pg.extra.voucher">
                                                    <input class="h-9 px-2 rounded-md border dark:border-gray-600"
                                                        placeholder="Banco" x-model="pg.extra.banco">
                                                    <input class="h-9 px-2 rounded-md border dark:border-gray-600"
                                                        placeholder="Terminal" x-model="pg.extra.terminal">
                                                    <input class="h-9 px-2 rounded-md border dark:border-gray-600"
                                                        placeholder="Lote" x-model="pg.extra.lote">
                                                    <input class="h-9 px-2 rounded-md border dark:border-gray-600"
                                                        placeholder="Últimos 4" x-model="pg.extra.ult4">
                                                    <input class="h-9 px-2 rounded-md border dark:border-gray-600"
                                                        placeholder="Titular" x-model="pg.extra.titular">
                                                </div>
                                            </template>

                                            {{-- Equivalentes informativos --}}
                                            <div class="md:col-span-12 text-xs text-gray-600 dark:text-gray-300">
                                                <template x-if="isUsd(pg.metodo)">
                                                    <span>≈ Bs: <b
                                                            x-text="fmtBs(toNum(pg.monto_usd) * toNum(pg.tasa_usd || form.tasa_usd))"></b></span>
                                                </template>
                                                <template x-if="isBs(pg.metodo)">
                                                    <span>≈ USD:
                                                        <b
                                                            x-text="fmtUsd(toNum(pg.tasa_usd || form.tasa_usd) > 0 ? (toNum(pg.monto_bs) / toNum(pg.tasa_usd || form.tasa_usd)) : 0)"></b>
                                                    </span>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        {{-- Footer (sticky) --}}
                        <div
                            class="sticky bottom-0 z-10 px-4 md:px-6 py-3 border-t bg-white/95 dark:bg-gray-800/95 backdrop-blur">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="text-sm text-gray-600">
                                    <span>Total </span><b x-text="fmtUsd(totalUsd())"></b>
                                    <span class="mx-2">·</span>
                                    <span>Pagado </span><b x-text="fmtUsd(pagadoUsd())"></b>
                                    <span class="mx-2">·</span>
                                    <span>Saldo </span><b x-text="fmtUsd(saldoUsd())"></b>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button @click="closeCreate"
                                        class="px-4 py-2 rounded-md bg-gray-100 dark:bg-gray-700">Cancelar</button>
                                    <button @click="submitCreate"
                                        class="px-4 py-2 rounded-md bg-emerald-600 hover:bg-emerald-700 text-white">
                                        Guardar y cobrar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Modal ABONO (UX mejorado y estable) ===== --}}
        <div x-cloak x-show="abonoOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
            x-transition.opacity @keydown.escape.window="abonoOpen=false">

            <div id="abono-modal"
                class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-2xl p-4 md:p-6 ring-1 ring-black/5"
                x-transition.scale @click.outside="abonoOpen=false"
                @keydown.ctrl.enter.prevent="!abonoDisabled() && submitAbono()">

                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg md:text-xl font-semibold">Registrar abono</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Factura <b>#<span x-text="abonoFactura?.id"></span></b> · Saldo:
                            <span
                                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">
                                <span x-text="fmtUsd(Number(abonoSaldoUsd()))"></span> USD
                            </span>
                        </p>
                    </div>
                    <button @click="abonoOpen=false" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800"
                        aria-label="Cerrar">✕</button>
                </div>

                <!-- Métodos como chips -->
                <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2">
                    <template
                        x-for="m in [
                    {v:'efectivo_usd',l:'Efectivo USD'},
                    {v:'efectivo_bs', l:'Efectivo Bs'},
                    {v:'zelle',       l:'Zelle'},
                    {v:'pmovil',      l:'Pago Móvil'},
                    {v:'transferencia',l:'Transferencia'},
                    {v:'pos',         l:'POS'},
                ]"
                        :key="m.v">
                        <button @click="abono.metodo=m.v" class="h-9 px-3 rounded-xl border text-sm"
                            :class="abono.metodo === m.v ?
                                'bg-emerald-600 text-white border-emerald-600' :
                                'border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800'">
                            <span x-text="m.l"></span>
                        </button>
                    </template>
                </div>

                <!-- Monto y tasa -->
                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="space-y-1">
                        <label class="text-xs text-gray-500">Monto USD</label>
                        <input name="usd" type="text" inputmode="decimal" :readonly="isAbonoBs()"
                            x-model.number="abono.monto_usd"
                            class="h-10 w-full px-3 rounded-xl border dark:border-gray-700 focus:outline-none"
                            :class="isAbonoBs() ? 'opacity-60' : ''">
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs text-gray-500">Monto Bs</label>
                        <input type="text" inputmode="decimal" :readonly="isAbonoUsd()"
                            x-model.number="abono.monto_bs"
                            class="h-10 w-full px-3 rounded-xl border dark:border-gray-700 focus:outline-none"
                            :class="isAbonoUsd() ? 'opacity-60' : ''">
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs text-gray-500">Tasa (vacío = Factura/BCV)</label>
                        <div class="flex gap-2">
                            <input type="number" step="0.0001" x-model.number="abono.tasa_usd"
                                class="h-10 w-full px-3 rounded-xl border dark:border-gray-700 focus:outline-none">
                        </div>
                        <div class="flex gap-2 mt-1">
                            <button type="button" class="text-xs px-2 py-1 rounded-lg border dark:border-gray-700"
                                @click="useFacturaRateForAbono()">Usar tasa factura</button>
                            <button type="button" class="text-xs px-2 py-1 rounded-lg border dark:border-gray-700"
                                @click="useBcvForAbono()">Usar BCV</button>
                        </div>
                    </div>
                </div>

                <!-- Referencia -->
                <div class="mt-3">
                    <input type="text" x-model.trim="abono.referencia" placeholder="Referencia (opcional)"
                        class="h-10 w-full px-3 rounded-xl border dark:border-gray-700 focus:outline-none">
                </div>

                <!-- EXTRA para POS -->
                <template x-if="abono.metodo === 'pos'">
                    <div class="mt-3 grid grid-cols-1 md:grid-cols-6 gap-2">
                        <input class="h-10 px-3 rounded-xl border dark:border-gray-700" placeholder="Voucher"
                            x-model.trim="abono.extra.voucher">
                        <input class="h-10 px-3 rounded-xl border dark:border-gray-700" placeholder="Banco"
                            x-model.trim="abono.extra.banco">
                        <input class="h-10 px-3 rounded-xl border dark:border-gray-700" placeholder="Terminal"
                            x-model.trim="abono.extra.terminal">
                        <input class="h-10 px-3 rounded-xl border dark:border-gray-700" placeholder="Lote"
                            x-model.trim="abono.extra.lote">
                        <input class="h-10 px-3 rounded-xl border dark:border-gray-700" placeholder="Últimos 4"
                            x-model.trim="abono.extra.ult4">
                        <input class="h-10 px-3 rounded-xl border dark:border-gray-700" placeholder="Titular"
                            x-model.trim="abono.extra.titular">
                    </div>
                </template>

                <!-- Presets -->
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <span class="text-xs text-gray-500">Rápidos:</span>
                    <button class="text-xs px-2 py-1 rounded-lg border dark:border-gray-700"
                        @click="quickPercent(25)">25%</button>
                    <button class="text-xs px-2 py-1 rounded-lg border dark:border-gray-700"
                        @click="quickPercent(50)">50%</button>
                    <button class="text-xs px-2 py-1 rounded-lg border dark:border-gray-700"
                        @click="quickPercent(75)">75%</button>
                    <button class="text-xs px-2 py-1 rounded-lg border dark:border-gray-700"
                        @click="quickPercent(100)">100%</button>
                    <button class="text-xs px-2 py-1 rounded-lg border dark:border-gray-700"
                        @click="abono.monto_usd = Number(abonoSaldoUsd()||0); abono.monto_bs = 0">Pagar todo</button>
                </div>

                <!-- Equivalentes & validaciones -->
                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-800">
                        <div class="text-xs text-gray-500">Equivalente (USD)</div>
                        <div class="text-lg font-semibold" x-text="fmtUsd(Number(abonoEqUsd()))"></div>
                    </div>
                    <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-800">
                        <div class="text-xs text-gray-500">Equivalente (Bs)</div>
                        <div class="text-lg font-semibold" x-text="fmtBs(Number(abonoEqBs()))"></div>
                    </div>
                    <div class="p-3 rounded-xl"
                        :class="abonoSaldoPost() === 0 ? 'bg-emerald-50 dark:bg-emerald-900/20' : 'bg-gray-50 dark:bg-gray-800'">
                        <div class="text-xs text-gray-500">Saldo después</div>
                        <div class="text-lg font-semibold"
                            :class="abonoSaldoPost() === 0 ? 'text-emerald-700 dark:text-emerald-300' : ''"
                            x-text="fmtUsd(Number(abonoSaldoPost()))"></div>
                    </div>
                </div>

                <p class="mt-2 text-xs text-rose-600" x-show="abonoEqUsd() > abonoSaldoUsd()">
                    El abono excede el saldo. Se ajustará automáticamente.
                </p>
                <p class="mt-2 text-xs text-rose-600" x-show="abonoError" x-text="abonoError"></p>

                <!-- Historial -->
                <div class="mt-5">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-semibold">Historial de pagos</h4>
                        <span class="text-xs text-gray-500" x-show="!abonoFacturaFull">Cargando...</span>
                    </div>
                    <div class="mt-2 max-h-40 overflow-auto rounded-xl border dark:border-gray-800"
                        x-show="abonoFacturaFull?.pagos?.length">
                        <table class="w-full text-sm">
                            <thead class="text-xs text-gray-500 sticky top-0 bg-white dark:bg-gray-900">
                                <tr>
                                    <th class="text-left p-2">Fecha</th>
                                    <th class="text-left p-2">Método</th>
                                    <th class="text-right p-2">USD</th>
                                    <th class="text-right p-2">Bs</th>
                                    <th class="text-left p-2">Ref</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="p in abonoFacturaFull?.pagos" :key="p.id">
                                    <tr class="border-t dark:border-gray-800">
                                        <td class="p-2" x-text="p.fecha_pago"></td>
                                        <td class="p-2" x-text="(p.metodo || '').toUpperCase()"></td>
                                        <td class="p-2 text-right"
                                            x-text="p.monto_usd ? fmtUsd(Number(p.monto_usd)) : '—'"></td>
                                        <td class="p-2 text-right"
                                            x-text="p.monto_bs ? fmtBs(Number(p.monto_bs)) : '—'"></td>
                                        <td class="p-2 truncate" x-text="p.referencia || '—'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-1 text-xs text-gray-500"
                        x-show="abonoFacturaFull && (!abonoFacturaFull?.pagos?.length)">
                        Sin pagos registrados aún.
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button @click="abonoOpen=false"
                        class="px-4 h-10 rounded-xl border dark:border-gray-700">Cancelar</button>
                    <button @click="submitAbono" :disabled="abonoDisabled()" class="px-4 h-10 rounded-xl text-white"
                        :class="abonoDisabled() ?
                            'bg-emerald-400/60 cursor-not-allowed' :
                            'bg-emerald-600 hover:bg-emerald-700'">
                        <span x-show="!abonoLoading">Guardar (Ctrl+Enter)</span>
                        <span x-show="abonoLoading">Guardando…</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Atajo de teclado global (scoped a componente facturas) --}}
        <script>
            document.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                    const root = document.querySelector('[x-data^="facturas"]') || document.querySelector('[x-data]');
                    if (!root) return;
                    const comp = Alpine.$data(root);
                    if (comp?.abonoOpen && !comp?.abonoDisabled?.()) comp?.submitAbono?.();
                }
            });
        </script>




        {{-- Quick modals --}}
        <template x-if="quickClienteOpen">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" x-transition.opacity>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md p-6" x-transition.scale>
                    <h3 class="text-lg font-semibold mb-3">Nuevo cliente</h3>
                    <div class="space-y-2">
                        <input
                            class="w-full h-10 px-3 rounded-md border dark:border-gray-600 bg-white dark:bg-gray-900"
                            placeholder="Nombre *" x-model="newCliente.nombre">
                        <input class="w-full h-10 px-3 rounded-md border dark:border-gray-600" placeholder="Apellido"
                            x-model="newCliente.apellido">
                        <input class="w-full h-10 px-3 rounded-md border dark:border-gray-600" placeholder="RIF/CI *"
                            x-model="newCliente.rif">
                        <input class="w-full h-10 px-3 rounded-md border dark:border-gray-600" placeholder="Email"
                            x-model="newCliente.email">
                        <input class="w-full h-10 px-3 rounded-md border dark:border-gray-600" placeholder="Teléfono"
                            x-model="newCliente.telefono">
                        <textarea class="w-full px-3 py-2 rounded-md border dark:border-gray-600" placeholder="Dirección"
                            x-model="newCliente.direccion"></textarea>
                    </div>
                    <div class="mt-4 flex justify-end gap-2">
                        <button @click="quickClienteOpen=false"
                            class="px-3 py-2 rounded-md border dark:border-gray-600">Cancelar</button>
                        <button @click="submitQuickCliente"
                            class="px-3 py-2 rounded-md bg-emerald-600 text-white">Guardar</button>
                    </div>
                </div>
            </div>
        </template>

        <template x-if="quickProductoOpen">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" x-transition.opacity>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md p-6" x-transition.scale>
                    <h3 class="text-lg font-semibold mb-3">Nuevo producto</h3>
                    <div class="space-y-2">
                        <select class="w-full h-10 px-3 rounded-md border dark:border-gray-600"
                            x-model="newProducto.categoria_id">
                            <option value="">Categoría…</option>
                            @foreach (\App\Models\Categoria::where('activo', 1)->orderBy('nombre')->get(['id', 'nombre']) as $c)
                                <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                            @endforeach
                        </select>
                        <input class="w-full h-10 px-3 rounded-md border dark:border-gray-600" placeholder="Nombre *"
                            x-model="newProducto.nombre">
                        <input class="w-full h-10 px-3 rounded-md border dark:border-gray-600"
                            placeholder="Unidad (UND/KG/…)" x-model="newProducto.unidad">
                        <input type="number" step="0.0001"
                            class="w-full h-10 px-3 rounded-md border dark:border-gray-600"
                            placeholder="Precio USD base *" x-model="newProducto.precio_usd_base">
                    </div>
                    <div class="mt-4 flex justify-end gap-2">
                        <button @click="quickProductoOpen=false"
                            class="px-3 py-2 rounded-md border dark:border-gray-600">Cancelar</button>
                        <button @click="submitQuickProducto"
                            class="px-3 py-2 rounded-md bg-emerald-600 text-white">Guardar</button>
                    </div>
                </div>
            </div>
        </template>

        <template x-if="quickVendedorOpen">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" x-transition.opacity>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md p-6" x-transition.scale>
                    <h3 class="text-lg font-semibold mb-3">Nuevo vendedor</h3>
                    <div class="space-y-2">
                        <input class="w-full h-10 px-3 rounded-md border dark:border-gray-600" placeholder="Nombre *"
                            x-model="newVendedor.nombre">
                        <input class="w-full h-10 px-3 rounded-md border dark:border-gray-600" placeholder="Teléfono"
                            x-model="newVendedor.telefono">
                        <label class="inline-flex items-center gap-2 mt-1">
                            <input type="checkbox" class="rounded border-gray-300 dark:border-gray-600"
                                x-model="newVendedor.activo">
                            <span class="text-sm">Activo</span>
                        </label>
                    </div>
                    <div class="mt-4 flex justify-end gap-2">
                        <button @click="quickVendedorOpen=false"
                            class="px-3 py-2 rounded-md border dark:border-gray-600">Cancelar</button>
                        <button @click="submitQuickVendedor"
                            class="px-3 py-2 rounded-md bg-emerald-600 text-white">Guardar</button>
                    </div>
                </div>
            </div>
        </template>

        {{-- Loader --}}
        <div x-show="loading" class="fixed bottom-4 right-4 z-50">
            <div class="px-3 py-1.5 rounded-md bg-gray-900/90 text-white text-sm">Cargando…</div>
        </div>
    </div>
</x-layout>
