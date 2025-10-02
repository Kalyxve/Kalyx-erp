{{-- resources/views/productos/index.blade.php --}}
<x-layout>
    <div x-data="productosPage($el)" x-init="init()" x-cloak class="space-y-4"
        data-initial='@json($productos)' data-categories='@json($categorias)'
        data-list-url="{{ route('productos.list') }}" data-store-url="{{ route('productos.store') }}"
        data-update-url-base="{{ url('productos') }}" data-bcv-url="/api/bcv-rate">

        {{-- Header --}}
        <div class="mb-5 flex items-center justify-between">
            <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Productos</h1>
            <button @click="openCreate()"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nuevo
            </button>
        </div>

        {{-- Filtros --}}
        <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
            <div class="md:col-span-2">
                <input type="text" x-model.debounce.350ms="q" placeholder="Buscar por código, nombre o unidad"
                    class="w-full h-10 px-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100">
            </div>

            <div>
                <select x-model.number="categoria_id"
                    class="h-10 px-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100">
                    <option value="0">Todas las categorías</option>
                    <template x-for="c in categorias" :key="c.id">
                        <option :value="c.id" x-text="c.nombre"></option>
                    </template>
                </select>
            </div>

            <div>
                <select x-model.number="limit"
                    class="h-10 px-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100">
                    <option value="10">10 por página</option>
                    <option value="25">25 por página</option>
                    <option value="50">50 por página</option>
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
                <button @click="fetchTasa()"
                    class="h-9 px-3 rounded-md border border-gray-300 dark:border-gray-600">Refrescar</button>
            </div>
        </div>

        {{-- Tabla --}}
        <div
            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/40">
                        <tr class="text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                            <th class="px-4 py-3">Código</th>
                            <th class="px-4 py-3">Nombre</th>
                            <th class="px-4 py-3">Categoría</th>
                            <th class="px-4 py-3">Unidad</th>
                            <th class="px-4 py-3 text-right">USD (base)</th>
                            <th class="px-4 py-3 text-right">Bs (base)</th>
                            <th class="px-4 py-3 text-right">Stock</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <template x-if="items.length === 0">
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No hay
                                    productos registrados.</td>
                            </tr>
                        </template>

                        <template x-for="p in items" :key="p.id">
                            <tr class="hover:bg-emerald-50/40 dark:hover:bg-gray-700/40">
                                <td class="px-4 py-3 font-mono" x-text="p.codigo"></td>
                                <td class="px-4 py-3" x-text="p.nombre"></td>
                                <td class="px-4 py-3" x-text="p.categoria_nombre ?? '—'"></td>
                                <td class="px-4 py-3" x-text="p.unidad ?? 'UND'"></td>
                                <td class="px-4 py-3 text-right" x-text="(p.precio_usd_base ?? 0).toFixed(2)"></td>
                                <td class="px-4 py-3 text-right" x-text="(p.precio_bs_base ?? 0).toFixed(2)"></td>
                                <td class="px-4 py-3 text-right" x-text="Number(p.stock ?? 0)"></td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                        :class="p.activo ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-700'">
                                        <span x-text="p.activo ? 'Activo' : 'Inactivo'"></span>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="openEdit(p)"
                                            class="h-9 px-3 rounded-md border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition">Editar</button>
                                        <button @click="confirmDelete(p)"
                                            class="h-9 px-3 rounded-md bg-red-600 hover:bg-red-700 text-white transition">Eliminar</button>
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
                        class="px-3 py-1.5 rounded-md border border-gray-300 dark:border-gray-600 text-sm disabled:opacity-50 disabled:cursor-not-allowed">Anterior</button>
                    <span class="text-sm text-gray-600 dark:text-gray-300"
                        x-text="`Página ${meta.current_page} de ${meta.last_page}`"></span>
                    <button @click="nextPage" :disabled="meta.current_page >= meta.last_page"
                        class="px-3 py-1.5 rounded-md border border-gray-300 dark:border-gray-600 text-sm disabled:opacity-50 disabled:cursor-not-allowed">Siguiente</button>
                </div>
            </div>
        </div>

        {{-- ===== Modal CREAR ===== --}}
        <div x-show="createOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
            x-transition.opacity>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-2xl p-6" x-transition.scale>
                <div class="flex items-center gap-3 mb-4">
                    <img src="{{ asset('logo.png') }}" class="w-7 h-7 rounded bg-emerald-600/10 p-1" alt="Logo">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Nuevo producto</h3>
                </div>

                <form @submit.prevent="submitCreate">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="md:col-span-2">
                            <label class="block text-sm mb-1">Categoría *</label>
                            <select x-model.number="form.categoria_id"
                                class="w-full h-10 px-3 rounded-md border dark:border-gray-600 bg-white dark:bg-gray-900">
                                <option value="">Seleccione…</option>
                                <template x-for="c in categorias" :key="c.id">
                                    <option :value="c.id" x-text="c.nombre"></option>
                                </template>
                            </select>
                            <p class="text-sm text-red-600 mt-1" x-text="errors.categoria_id"></p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm mb-1">Nombre *</label>
                            <input type="text" x-model="form.nombre"
                                class="w-full h-10 px-3 rounded-md border dark:border-gray-600 bg-white dark:bg-gray-900">
                            <p class="text-sm text-red-600 mt-1" x-text="errors.nombre"></p>
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Unidad *</label>
                            <select x-model="form.unidad"
                                class="w-full h-10 px-3 rounded-md border dark:border-gray-600 bg-white dark:bg-gray-900">
                                <option value="UND">UND</option>
                                <option value="KG">KG</option>
                                <option value="LT">LT</option>
                                <option value="PAQ">PAQ</option>
                            </select>
                            <p class="text-sm text-red-600 mt-1" x-text="errors.unidad"></p>
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Stock</label>
                            <input type="number" step="0.01" x-model.number="form.stock"
                                class="w-full h-10 px-3 rounded-md border dark:border-gray-600 bg-white dark:bg-gray-900">
                            <p class="text-sm text-red-600 mt-1" x-text="errors.stock"></p>
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Precio USD (base) *</label>
                            <input type="number" step="0.0001" x-model.number="form.precio_usd_base"
                                class="w-full h-10 px-3 rounded-md border dark:border-gray-600 bg-white dark:bg-gray-900">
                            <p class="text-sm text-red-600 mt-1" x-text="errors.precio_usd_base"></p>
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Precio Bs (base)</label>
                            <input type="number" step="0.01" x-model.number="form.precio_bs_base"
                                class="w-full h-10 px-3 rounded-md border dark:border-gray-600 bg-white dark:bg-gray-900"
                                readonly>
                            <p class="text-sm text-gray-500">Se calcula con la tasa BCV del día</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm mb-1">Tasa usada (BCV)</label>
                            <div class="flex items-center gap-2">
                                <input type="number" step="0.0001" x-model.number="form.tasa_usd_registro"
                                    class="h-10 px-3 rounded-md border dark:border-gray-600 bg-white dark:bg-gray-900">
                                <button type="button" @click="usarTasaActual()"
                                    class="h-10 px-3 rounded-md border dark:border-gray-600">Usar tasa actual</button>
                            </div>
                            <p class="text-sm text-red-600 mt-1" x-text="errors.tasa_usd_registro"></p>
                        </div>

                        <div class="flex items-center gap-2 md:col-span-2">
                            <input type="checkbox" id="activo_create" x-model="form.activo" class="h-4 w-4">
                            <label for="activo_create" class="text-sm">Activo</label>
                        </div>
                    </div>

                    <div class="mt-5 flex justify-end gap-2">
                        <button type="button" @click="closeCreate"
                            class="px-4 py-2 rounded-md bg-gray-100 dark:bg-gray-700">Cancelar</button>
                        <button type="submit"
                            class="px-4 py-2 rounded-md bg-emerald-600 hover:bg-emerald-700 text-white">Guardar</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ===== Modal EDITAR ===== --}}
        <div x-show="editOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
            x-transition.opacity>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-2xl p-6" x-transition.scale>
                <div class="flex items-center gap-3 mb-4">
                    <img src="{{ asset('logo.png') }}" class="w-7 h-7 rounded bg-emerald-600/10 p-1" alt="Logo">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Editar producto</h3>
                </div>

                <form @submit.prevent="submitEdit">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="md:col-span-2">
                            <label class="block text-sm mb-1">Categoría *</label>
                            <select x-model.number="form.categoria_id"
                                class="w-full h-10 px-3 rounded-md border dark:border-gray-600 bg-white dark:bg-gray-900">
                                <template x-for="c in categorias" :key="c.id">
                                    <option :value="c.id" x-text="c.nombre"></option>
                                </template>
                            </select>
                            <p class="text-sm text-red-600 mt-1" x-text="errors.categoria_id"></p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm mb-1">Nombre *</label>
                            <input type="text" x-model="form.nombre"
                                class="w-full h-10 px-3 rounded-md border dark:border-gray-600 bg-white dark:bg-gray-900">
                            <p class="text-sm text-red-600 mt-1" x-text="errors.nombre"></p>
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Unidad *</label>
                            <select x-model="form.unidad"
                                class="w-full h-10 px-3 rounded-md border dark:border-gray-600 bg-white dark:bg-gray-900">
                                <option value="UND">UND</option>
                                <option value="KG">KG</option>
                                <option value="LT">LT</option>
                                <option value="PAQ">PAQ</option>
                            </select>
                            <p class="text-sm text-red-600 mt-1" x-text="errors.unidad"></p>
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Stock</label>
                            <input type="number" step="0.01" x-model.number="form.stock"
                                class="w-full h-10 px-3 rounded-md border dark:border-gray-600 bg-white dark:bg-gray-900">
                            <p class="text-sm text-red-600 mt-1" x-text="errors.stock"></p>
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Precio USD (base) *</label>
                            <input type="number" step="0.0001" x-model.number="form.precio_usd_base"
                                class="w-full h-10 px-3 rounded-md border dark:border-gray-600 bg-white dark:bg-gray-900">
                            <p class="text-sm text-red-600 mt-1" x-text="errors.precio_usd_base"></p>
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Precio Bs (base)</label>
                            <input type="number" step="0.01" x-model.number="form.precio_bs_base"
                                class="w-full h-10 px-3 rounded-md border dark:border-gray-600 bg-white dark:bg-gray-900"
                                readonly>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm mb-1">Tasa usada (BCV)</label>
                            <div class="flex items-center gap-2">
                                <input type="number" step="0.0001" x-model.number="form.tasa_usd_registro"
                                    class="h-10 px-3 rounded-md border dark:border-gray-600 bg-white dark:bg-gray-900">
                                <button type="button" @click="recalcularConTasaActual()"
                                    class="h-10 px-3 rounded-md border dark:border-gray-600">Recalcular con tasa
                                    actual</button>
                            </div>
                            <p class="text-sm text-gray-500">Por defecto mantiene la tasa guardada al registrar; este
                                botón la reemplaza.</p>
                        </div>

                        <div class="flex items-center gap-2 md:col-span-2">
                            <input type="checkbox" id="activo_edit" x-model="form.activo" class="h-4 w-4">
                            <label for="activo_edit" class="text-sm">Activo</label>
                        </div>
                    </div>

                    <div class="mt-5 flex justify-end gap-2">
                        <button type="button" @click="closeEdit"
                            class="px-4 py-2 rounded-md bg-gray-100 dark:bg-gray-700">Cancelar</button>
                        <button type="submit"
                            class="px-4 py-2 rounded-md bg-emerald-600 hover:bg-emerald-700 text-white">Actualizar</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ===== Modal ELIMINAR ===== --}}
        <div x-show="deleteOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
            x-transition.opacity>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-sm p-6" x-transition.scale>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">¿Eliminar producto?</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Esta acción no se puede deshacer.</p>
                <div class="mt-5 flex justify-end gap-2">
                    <button @click="deleteOpen=false"
                        class="px-4 py-2 rounded-md bg-gray-100 dark:bg-gray-700">Cancelar</button>
                    <button @click="submitDelete"
                        class="px-4 py-2 rounded-md bg-red-600 hover:bg-red-700 text-white">Eliminar</button>
                </div>
            </div>
        </div>

        {{-- Loader --}}
        <div x-show="loading" class="fixed bottom-4 right-4 z-50">
            <div class="px-3 py-1.5 rounded-md bg-gray-900/90 text-white text-sm">Cargando...</div>
        </div>
    </div>
</x-layout>
