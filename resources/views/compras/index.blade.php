{{-- resources/views/compras/index.blade.php --}}
<x-layout :settings="['currencyDefault' => 'USD']">
    <div x-data="comprasPage()" x-init="init()" x-cloak class="p-4 space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Compras</h1>
                <p class="text-sm text-gray-500">Registra compras y entradas al inventario con un solo flujo.</p>
            </div>
            <div class="flex items-center gap-2">
                <input x-model.debounce.300ms="q" @input="fetchList()" type="search" placeholder="Buscar..."
                    class="border rounded-lg px-3 py-2 text-sm dark:bg-gray-800 dark:border-gray-700">
                <button @click="openNew()"
                    class="px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 transition">
                    Nueva compra
                </button>
            </div>
        </div>

        {{-- Tabla --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow divide-y dark:divide-gray-700">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/40">
                        <tr class="text-left">
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3">N°</th>
                            <th class="px-4 py-3">Proveedor</th>
                            <th class="px-4 py-3 text-right">Total USD</th>
                            <th class="px-4 py-3 text-right">Total Bs</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="row in table.items" :key="row.id">
                            <tr class="border-t dark:border-gray-700 hover:bg-gray-50/50 dark:hover:bg-gray-900/20">
                                <td class="px-4 py-2" x-text="row.fecha"></td>
                                <td class="px-4 py-2" x-text="row.numero || '-'"></td>
                                <td class="px-4 py-2">
                                    <div class="font-medium" x-text="row.proveedor"></div>
                                    <div class="text-xs text-gray-500" x-text="row.rif"></div>
                                </td>
                                <td class="px-4 py-2 text-right"
                                    x-text="row.total_usd.toLocaleString('es-VE',{minimumFractionDigits:2})"></td>
                                <td class="px-4 py-2 text-right"
                                    x-text="row.total_bs.toLocaleString('es-VE',{minimumFractionDigits:2})"></td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 text-xs rounded-full"
                                        :class="row.estado === 'registrada' ? 'bg-emerald-100 text-emerald-700' :
                                            'bg-rose-100 text-rose-700'"
                                        x-text="row.estado"></span>
                                </td>
                                <td class="px-4 py-2">
                                    <div class="flex items-center gap-2">
                                        <button @click="openEdit(row.id)"
                                            class="px-3 py-1 rounded-lg bg-blue-600 text-white text-xs hover:bg-blue-700">Editar</button>
                                        <button @click="remove(row.id)"
                                            class="px-3 py-1 rounded-lg bg-rose-600 text-white text-xs hover:bg-rose-700">Eliminar</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="table.items.length===0">
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">Sin registros.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ===== Modal Nueva/Editar Compra (responsive pro) ===== --}}
        <div x-show="newOpen" x-transition.opacity class="fixed inset-0 z-50">
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black/45" @click="closeModal()"></div>

            {{-- Contenedor --}}
            <div
                class="relative mx-auto w-full md:max-w-6xl md:my-6 md:rounded-2xl bg-white dark:bg-gray-900 shadow-2xl
              h-screen md:h-[90vh] flex flex-col">
                {{-- Header (sticky) --}}
                <div class="sticky top-0 z-10 px-4 md:px-6 py-3 border-b bg-white/90 dark:bg-gray-900/90 backdrop-blur">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="text-base md:text-lg font-semibold truncate"
                                x-text="editId ? 'Editar compra' : 'Nueva compra'"></h3>
                            <p class="hidden md:block text-xs text-gray-500">Ctrl/⌘ + Enter para guardar</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="hidden md:flex items-center text-xs text-gray-500 gap-3">
                                <span>Subtotal: <b x-text="money(form.total_usd)"></b></span>
                                <span>· Pagado: <b>0.00</b></span>
                                <span>· Saldo: <b x-text="money(form.total_usd)"></b></span>
                            </div>
                            <button @click="closeModal"
                                class="h-9 px-3 rounded-lg border dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Body (scroll único) --}}
                <div class="flex-1 overflow-y-auto px-4 md:px-6 py-4 space-y-5">

                    {{-- Cabecera --}}
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        {{-- Fecha --}}
                        <div>
                            <label class="text-xs text-gray-500 mb-1 block">Fecha</label>
                            <input type="date" x-model="form.fecha"
                                class="w-full h-10 px-3 rounded-lg border dark:border-gray-700 dark:bg-gray-800">
                        </div>

                        {{-- Proveedor --}}
                        <div class="md:col-span-2">
                            <label class="text-xs text-gray-500 mb-1 block">Proveedor</label>
                            <select x-ref="selectProveedor" placeholder="Buscar proveedor..."></select>
                            <div class="flex gap-2 mt-2">
                                <button @click="openProveedorModal(false)"
                                    class="px-3 h-8 rounded-lg bg-emerald-600 text-white text-xs">+ Crear</button>
                                <button @click="openProveedorModal(true)"
                                    class="px-3 h-8 rounded-lg bg-amber-600 text-white text-xs disabled:opacity-60"
                                    :disabled="!form.proveedor_id">Editar</button>
                            </div>
                        </div>

                        {{-- N° Documento --}}
                        <div>
                            <label class="text-xs text-gray-500 mb-1 block">N° Documento</label>
                            <input type="text" x-model.trim="form.numero"
                                class="w-full h-10 px-3 rounded-lg border dark:border-gray-700 dark:bg-gray-800">
                        </div>

                        {{-- Tasa --}}
                        <div class="md:col-span-1">
                            <label class="text-xs text-gray-500 mb-1 block">Tasa USD (BCV)</label>
                            <div class="flex gap-2">
                                <input type="number" step="0.0001" min="0" x-model.number="form.tasa_usd"
                                    class="w-full h-10 px-3 rounded-lg border dark:border-gray-700 dark:bg-gray-800">
                                <button @click="fetchBCV()"
                                    class="px-3 h-10 rounded-lg bg-gray-100 dark:bg-gray-800 text-xs border dark:border-gray-700">
                                    Refrescar
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Productos --}}
                    <div class="rounded-xl border dark:border-gray-800">
                        <div class="flex items-center justify-between px-3 md:px-4 py-2">
                            <h4 class="text-sm font-medium">Productos</h4>
                            <button @click="addItemRow()"
                                class="px-3 h-8 rounded-lg bg-indigo-600 text-white text-xs">+ Agregar
                                producto</button>
                        </div>

                        {{-- Desktop: tabla --}}
                        <div class="hidden md:block overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Producto</th>
                                        <th class="px-3 py-2 w-28">Cantidad</th>
                                        <th class="px-3 py-2 w-28">PU USD</th>
                                        <th class="px-3 py-2 w-28">PU Bs</th>
                                        <th class="px-3 py-2 w-28 text-right">Sub USD</th>
                                        <th class="px-3 py-2 w-28 text-right">Sub Bs</th>
                                        <th class="px-3 py-2 w-20">—</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="row in form.items" :key="row.tempId">
                                        <tr class="border-t dark:border-gray-800">
                                            <td class="px-3 py-2 min-w-[320px]">
                                                <select class="w-full" x-init="$nextTick(() => mountProductoTS($el, row.tempId, row))"></select>
                                                <p class="mt-1 text-[11px] text-gray-500"
                                                    x-show="row.codigo || row.nombre">
                                                    <span x-text="row.codigo || ''"></span>
                                                    <span x-text="row.nombre ? ' — '+row.nombre : ''"></span>
                                                </p>
                                            </td>
                                            <td class="px-3 py-2">
                                                <input type="number" inputmode="decimal" lang="en"
                                                    step="0.01" min="0.01" x-model="row.cantidad"
                                                    @input="recalcRow(row.tempId)"
                                                    class="w-28 h-9 px-2 rounded-lg border dark:border-gray-700 dark:bg-gray-800">
                                            </td>
                                            <td class="px-3 py-2">
                                                <input type="number" inputmode="decimal" lang="en"
                                                    step="0.01" x-model="row.precio_unitario_usd"
                                                    @input="onPriceUsdChange(row.tempId)"
                                                    class="w-28 h-9 px-2 rounded-lg border dark:border-gray-700 dark:bg-gray-800">
                                            </td>
                                            <td class="px-3 py-2">
                                                <input type="number" inputmode="decimal" lang="en"
                                                    step="0.01" min="0" x-model="row.precio_unitario_bs"
                                                    @input="onPriceBsChange(row.tempId)"
                                                    class="w-28 h-9 px-2 rounded-lg border dark:border-gray-700 dark:bg-gray-800">
                                            </td>
                                            <td class="px-3 py-2 text-right" x-text="money(row.subtotal_usd)"></td>
                                            <td class="px-3 py-2 text-right" x-text="money(row.subtotal_bs)"></td>
                                            <td class="px-3 py-2">
                                                <button @click="removeItemRow(row.tempId)"
                                                    class="px-2 h-8 rounded-full bg-rose-600 text-white text-[11px]">
                                                    Quitar
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                                <tfoot class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <td colspan="4" class="px-3 py-2 text-right font-medium">Totales</td>
                                        <td class="px-3 py-2 text-right font-semibold" x-text="money(form.total_usd)">
                                        </td>
                                        <td class="px-3 py-2 text-right font-semibold" x-text="money(form.total_bs)">
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        {{-- Mobile: tarjetas (sin tabla) --}}
                        <div class="md:hidden divide-y dark:divide-gray-800">
                            <template x-for="row in form.items" :key="row.tempId">
                                <div class="px-3 py-3 space-y-2">
                                    <div>
                                        <label class="text-[11px] text-gray-500">Producto</label>
                                        <select class="w-full" x-init="$nextTick(() => mountProductoTS($el, row.tempId, row))"></select>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="text-[11px] text-gray-500">Cantidad</label>
                                            <input type="number" inputmode="decimal" lang="en" step="0.01"
                                                min="0.01" x-model="row.cantidad" @input="recalcRow(row.tempId)"
                                                class="w-full h-10 px-2 rounded-lg border dark:border-gray-700 dark:bg-gray-800">
                                        </div>
                                        <div>
                                            <label class="text-[11px] text-gray-500">PU USD</label>
                                            <input type="number" inputmode="decimal" lang="en" step="0.01"
                                                x-model="row.precio_unitario_usd"
                                                @input="onPriceUsdChange(row.tempId)"
                                                class="w-full h-10 px-2 rounded-lg border dark:border-gray-700 dark:bg-gray-800">
                                        </div>
                                        <div>
                                            <label class="text-[11px] text-gray-500">PU Bs</label>
                                            <input type="number" inputmode="decimal" lang="en" step="0.01"
                                                min="0" x-model="row.precio_unitario_bs"
                                                @input="onPriceBsChange(row.tempId)"
                                                class="w-full h-10 px-2 rounded-lg border dark:border-gray-700 dark:bg-gray-800">
                                        </div>
                                        <div class="flex items-end justify-end">
                                            <button @click="removeItemRow(row.tempId)"
                                                class="px-3 h-8 rounded-full bg-rose-600 text-white text-[11px]">
                                                Quitar
                                            </button>
                                        </div>
                                    </div>
                                    <div
                                        class="text-xs text-gray-600 dark:text-gray-300 flex items-center justify-between pt-1">
                                        <span>Sub USD: <b x-text="money(row.subtotal_usd)"></b></span>
                                        <span>Sub Bs: <b x-text="money(row.subtotal_bs)"></b></span>
                                    </div>
                                </div>
                            </template>

                            {{-- Totales móviles --}}
                            <div class="px-3 py-3 bg-gray-50 dark:bg-gray-800 rounded-b-xl">
                                <div class="text-sm flex items-center justify-between">
                                    <span class="font-medium">Total</span>
                                    <span class="font-semibold"
                                        x-text="money(form.total_usd) + ' · ' + money(form.total_bs)"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Nota (opcional) --}}
                    <div class="rounded-xl border dark:border-gray-800">
                        <div class="flex items-center justify-between px-3 md:px-4 py-2">
                            <label class="text-sm font-medium">Nota (opcional)</label>
                        </div>
                        <div class="px-3 md:px-4 pb-3">
                            <textarea rows="3" x-model="form.nota"
                                class="w-full px-3 py-2 rounded-lg border dark:border-gray-700 dark:bg-gray-800"></textarea>
                        </div>
                    </div>

                </div>

                {{-- Footer (sticky) --}}
                <div
                    class="sticky bottom-0 z-10 px-4 md:px-6 py-3 border-t bg-white/90 dark:bg-gray-900/90 backdrop-blur">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <div class="flex items-center gap-4 text-sm">
                            <span>Total <b x-text="money(form.total_usd)"></b></span>
                            <span class="text-gray-400">·</span>
                            <span>Pagado <b>0.00</b></span>
                            <span class="text-gray-400">·</span>
                            <span>Saldo <b x-text="money(form.total_usd)"></b></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="closeModal()" class="px-4 h-10 rounded-lg border dark:border-gray-700">
                                Cancelar
                            </button>
                            <button @click="submit()"
                                class="px-4 h-10 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white">
                                <span x-text="editId ? 'Guardar cambios' : 'Registrar compra'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        {{-- Modal Proveedor (crear/editar en tiempo real) --}}
        <div x-show="proveedorModalOpen" x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div x-transition.scale class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold"
                        x-text="proveedorEditId ? 'Editar proveedor' : 'Nuevo proveedor'"></h3>
                    <button @click="closeProveedorModal()" class="text-gray-500 hover:text-gray-700">&times;</button>
                </div>

                <div class="grid grid-cols-1 gap-3">
                    <div>
                        <label class="text-xs text-gray-500">Razón Social</label>
                        <input type="text" x-model.trim="proveedor.razon_social"
                            class="w-full border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs text-gray-500">RIF</label>
                            <input type="text" x-model.trim="proveedor.rif"
                                class="w-full border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700">
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">Teléfono</label>
                            <input type="text" x-model.trim="proveedor.telefono"
                                class="w-full border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700">
                        </div>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500">Email</label>
                        <input type="email" x-model.trim="proveedor.email"
                            class="w-full border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700">
                    </div>
                    <div>
                        <label class="text-xs text-gray-500">Dirección</label>
                        <textarea x-model.trim="proveedor.direccion" rows="2"
                            class="w-full border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700"></textarea>
                    </div>
                    <div class="flex items-center gap-2">
                        <input id="prov_activo" type="checkbox" x-model="proveedor.activo">
                        <label for="prov_activo" class="text-sm">Activo</label>
                    </div>
                </div>

                <div class="mt-5 flex items-center justify-end gap-3">
                    <button @click="closeProveedorModal()"
                        class="px-4 py-2 rounded-lg border dark:border-gray-700">Cancelar</button>
                    <button @click="saveProveedor()"
                        class="px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700"
                        x-text="proveedorEditId ? 'Guardar cambios' : 'Crear proveedor'"></button>
                </div>
            </div>
        </div>

        {{-- Toast --}}
        <div x-show="toast.show" x-transition.opacity
            class="fixed bottom-4 right-4 z-50 bg-black text-white text-sm px-4 py-2 rounded-xl shadow-lg/50">
            <span x-text="toast.text"></span>
        </div>
    </div>
</x-layout>
