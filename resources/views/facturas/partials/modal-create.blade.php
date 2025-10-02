<div x-show="createOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" x-transition.opacity>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-5xl p-6" x-transition.scale>
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <img src="{{ asset('logo.png') }}" class="w-7 h-7 rounded bg-emerald-600/10 p-1" alt="Logo">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Nueva factura</h3>
            </div>
            <button @click="closeCreate" class="h-9 px-3 rounded-md border dark:border-gray-600">Cerrar</button>
        </div>

        {{-- Cabecera --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
            <div>
                <label class="block text-sm mb-1">Cliente *</label>
                <div class="flex gap-2">
                    <select x-model.number="form.cliente_id"
                        class="flex-1 h-10 px-3 rounded-md border dark:border-gray-600 bg-white dark:bg-gray-900">
                        <option value="">Seleccione…</option>
                        <template x-for="c in clientes" :key="c.id">
                            <option :value="c.id" x-text="fullName(c)"></option>
                        </template>
                    </select>
                    <button type="button" @click="openQuickCliente()"
                        class="h-10 px-3 rounded-md border dark:border-gray-600">+ Cliente</button>
                </div>
                <p class="text-sm text-red-600 mt-1" x-text="errors.cliente_id"></p>
            </div>

            <div>
                <label class="block text-sm mb-1">Tasa BCV (al emitir) *</label>
                <div class="flex gap-2">
                    <input type="number" step="0.0001" x-model.number="form.tasa_usd"
                        class="flex-1 h-10 px-3 rounded-md border dark:border-gray-600 bg-white dark:bg-gray-900">
                    <button type="button" @click="usarTasaActual()"
                        class="h-10 px-3 rounded-md border dark:border-gray-600">Usar BCV</button>
                </div>
                <p class="text-sm text-red-600 mt-1" x-text="errors.tasa_usd"></p>
            </div>

            <div>
                <label class="block text-sm mb-1">IVA (%)</label>
                <input type="number" step="0.01" x-model.number="form.iva_percent"
                    class="w-full h-10 px-3 rounded-md border dark:border-gray-600 bg-white dark:bg-gray-900">
            </div>
        </div>

        {{-- Items --}}
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <h4 class="font-semibold text-gray-800 dark:text-gray-100">Items</h4>
                <button type="button" @click="addItem()" class="h-9 px-3 rounded-md border dark:border-gray-600">+
                    Producto</button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr
                            class="text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase border-b dark:border-gray-700">
                            <th class="px-2 py-2">Producto</th>
                            <th class="px-2 py-2">Cant.</th>
                            <th class="px-2 py-2">P.Unit USD</th>
                            <th class="px-2 py-2">Tasa Item</th>
                            <th class="px-2 py-2 text-right">Sub USD</th>
                            <th class="px-2 py-2 text-right">Sub Bs</th>
                            <th class="px-2 py-2 text-right">—</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(it, idx) in form.items" :key="idx">
                            <tr class="border-b dark:border-gray-700">
                                <td class="px-2 py-1">
                                    <div class="flex gap-2">
                                        <select x-model.number="it.producto_id" @change="onPickProducto(idx)"
                                            class="h-9 px-2 rounded-md border dark:border-gray-600 bg-white dark:bg-gray-900">
                                            <option value="">Seleccione…</option>
                                            <template x-for="p in productos" :key="p.id">
                                                <option :value="p.id" x-text="p.nombre"></option>
                                            </template>
                                        </select>
                                        <button type="button" @click="openQuickProducto(idx)"
                                            class="h-9 px-2 rounded-md border dark:border-gray-600">+ Prod</button>
                                    </div>
                                </td>
                                <td class="px-2 py-1">
                                    <input type="number" step="0.01" x-model.number="it.cantidad"
                                        class="w-24 h-9 px-2 rounded-md border dark:border-gray-600 bg-white dark:bg-gray-900">
                                </td>
                                <td class="px-2 py-1">
                                    <input type="number" step="0.0001" x-model.number="it.precio_unitario_usd"
                                        class="w-28 h-9 px-2 rounded-md border dark:border-gray-600 bg-white dark:bg-gray-900">
                                </td>
                                <td class="px-2 py-1">
                                    <input type="number" step="0.0001" x-model.number="it.tasa_usd_item"
                                        class="w-28 h-9 px-2 rounded-md border dark:border-gray-600 bg-white dark:bg-gray-900"
                                        placeholder="(opcional)">
                                </td>
                                <td class="px-2 py-1 text-right" x-text="subUsd(row).toFixed(2)"></td>
                                <td class="px-2 py-1 text-right" x-text="subBs(row).toFixed(2)"></td>
                                <td class="px-2 py-1 text-right">
                                    <button type="button" @click="removeItem(idx)"
                                        class="h-9 px-3 rounded-md bg-red-600 hover:bg-red-700 text-white">X</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <p class="text-sm text-red-600" x-text="errors.items"></p>
        </div>

        {{-- Totales --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-4">
            <div class="md:col-span-2"></div>
            <div class="space-y-1 text-sm">
                <div class="flex justify-between"><span>SubTotal USD</span><span
                        x-text="subtotalUsd().toFixed(2)"></span></div>
                <div class="flex justify-between"><span>IVA (<span x-text="form.iva_percent"></span>%)</span><span
                        x-text="ivaUsd().toFixed(2)"></span></div>
                <div class="flex justify-between font-semibold text-emerald-700 dark:text-emerald-300"><span>Total
                        USD</span><span x-text="totalUsd().toFixed(2)"></span></div>
                <div class="flex justify-between"><span>Total Bs</span><span x-text="totalBs().toFixed(2)"></span>
                </div>
            </div>
        </div>

        {{-- Pagos --}}
        <div class="mt-6 space-y-2">
            <div class="flex items-center justify-between">
                <h4 class="font-semibold text-gray-800 dark:text-gray-100">Pagos (opcional / mixto)</h4>
                <button type="button" @click="addPago()" class="h-9 px-3 rounded-md border dark:border-gray-600">+
                    Pago</button>
            </div>

            <div class="space-y-2">
                <template x-for="(pg, i) in form.pagos" :key="i">
                    <div class="grid grid-cols-1 md:grid-cols-6 gap-2">
                        <div>
                            <label class="block text-xs">Método</label>
                            <input type="text" x-model="pg.metodo"
                                class="w-full h-9 px-2 rounded-md border dark:border-gray-600 bg-white dark:bg-gray-900"
                                placeholder="efectivo_usd / zelle / transf_bs…">
                        </div>
                        <div>
                            <label class="block text-xs">USD</label>
                            <input type="number" step="0.01" x-model.number="pg.monto_usd"
                                class="w-full h-9 px-2 rounded-md border dark:border-gray-600 bg-white dark:bg-gray-900">
                        </div>
                        <div>
                            <label class="block text-xs">Bs</label>
                            <input type="number" step="0.01" x-model.number="pg.monto_bs"
                                class="w-full h-9 px-2 rounded-md border dark:border-gray-600 bg-white dark:bg-gray-900">
                        </div>
                        <div>
                            <label class="block text-xs">Tasa pago</label>
                            <input type="number" step="0.0001" x-model.number="pg.tasa_usd"
                                class="w-full h-9 px-2 rounded-md border dark:border-gray-600 bg-white dark:bg-gray-900"
                                :placeholder="form.tasa_usd">
                        </div>
                        <div>
                            <label class="block text-xs">Ref</label>
                            <input type="text" x-model="pg.referencia"
                                class="w-full h-9 px-2 rounded-md border dark:border-gray-600 bg-white dark:bg-gray-900">
                        </div>
                        <div class="flex items-end justify-end">
                            <button type="button" @click="removePago(i)"
                                class="h-9 px-3 rounded-md bg-red-600 hover:bg-red-700 text-white">Quitar</button>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Sugerencia de vuelto --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm mt-2">
                <div class="md:col-span-2">
                    <div class="flex items-center gap-2">
                        <span>Vuelto en:</span>
                        <label class="inline-flex items-center gap-1"><input type="radio" value="USD"
                                x-model="vueltoEn"> USD</label>
                        <label class="inline-flex items-center gap-1"><input type="radio" value="BS"
                                x-model="vueltoEn"> Bs</label>
                    </div>
                </div>
                <div class="text-right">
                    <div>Pagado USD eq: <span x-text="pagadoUsdEquiv().toFixed(2)"></span></div>
                    <div x-show="pagadoUsdEquiv() >= totalUsd()">
                        <template x-if="vueltoEn === 'USD'">
                            <div class="font-semibold">Vuelto USD: <span
                                    x-text="(pagadoUsdEquiv() - totalUsd()).toFixed(2)"></span></div>
                        </template>
                        <template x-if="vueltoEn === 'BS'">
                            <div class="font-semibold">Vuelto Bs: <span
                                    x-text="((pagadoUsdEquiv() - totalUsd()) * form.tasa_usd).toFixed(2)"></span></div>
                        </template>
                    </div>
                    <div x-show="pagadoUsdEquiv() < totalUsd()" class="text-red-600">Saldo USD: <span
                            x-text="(totalUsd() - pagadoUsdEquiv()).toFixed(2)"></span></div>
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <button type="button" @click="closeCreate"
                class="px-4 py-2 rounded-md bg-gray-100 dark:bg-gray-700">Cancelar</button>
            <button type="button" @click="submitCreate"
                class="px-4 py-2 rounded-md bg-emerald-600 hover:bg-emerald-700 text-white">Guardar factura</button>
        </div>

        {{-- Quick create Cliente --}}
        <template x-if="quickClienteOpen">
            <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40" x-transition.opacity>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-xl p-6" x-transition.scale>
                    <h4 class="text-lg font-semibold mb-3">Nuevo cliente</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <input type="text" x-model="quickCliente.nombre"
                            class="h-10 px-3 rounded-md border dark:border-gray-600" placeholder="Nombre *">
                        <input type="text" x-model="quickCliente.apellido"
                            class="h-10 px-3 rounded-md border dark:border-gray-600" placeholder="Apellido">
                        <input type="text" x-model="quickCliente.rif"
                            class="h-10 px-3 rounded-md border dark:border-gray-600 md:col-span-2"
                            placeholder="RIF/Cédula *">
                        <input type="email" x-model="quickCliente.email"
                            class="h-10 px-3 rounded-md border dark:border-gray-600 md:col-span-2"
                            placeholder="Email">
                    </div>
                    <div class="mt-4 flex justify-end gap-2">
                        <button @click="quickClienteOpen=false"
                            class="px-3 py-2 rounded-md border dark:border-gray-600">Cerrar</button>
                        <button @click="submitQuickCliente"
                            class="px-3 py-2 rounded-md bg-emerald-600 text-white">Crear</button>
                    </div>
                </div>
            </div>
        </template>

        {{-- Quick create Producto --}}
        <template x-if="quickProductoOpen">
            <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40" x-transition.opacity>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-xl p-6" x-transition.scale>
                    <h4 class="text-lg font-semibold mb-3">Nuevo producto</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <input type="text" x-model="quickProducto.nombre"
                            class="h-10 px-3 rounded-md border dark:border-gray-600 md:col-span-2"
                            placeholder="Nombre *">
                        <input type="number" step="0.0001" x-model.number="quickProducto.precio_usd_base"
                            class="h-10 px-3 rounded-md border dark:border-gray-600" placeholder="Precio USD *">
                        <input type="text" x-model="quickProducto.unidad"
                            class="h-10 px-3 rounded-md border dark:border-gray-600" placeholder="Unidad (UND)">
                        <select x-model.number="quickProducto.categoria_id"
                            class="h-10 px-3 rounded-md border dark:border-gray-600 md:col-span-2">
                            <option value="">Categoría…</option>
                            @foreach (\App\Models\Categoria::where('activo', 1)->orderBy('nombre')->get(['id', 'nombre']) as $c)
                                <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mt-4 flex justify-end gap-2">
                        <button @click="quickProductoOpen=false"
                            class="px-3 py-2 rounded-md border dark:border-gray-600">Cerrar</button>
                        <button @click="submitQuickProducto"
                            class="px-3 py-2 rounded-md bg-emerald-600 text-white">Crear</button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
