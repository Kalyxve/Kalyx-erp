{{-- resources/views/pages/inventory/index.blade.php --}}
<x-layout>
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">Inventario</h1>
            <p class="text-gray-500">Movimientos de stock</p>
        </div>

        <button x-data @click="$dispatch('open-movement-modal')"
            class="relative inline-flex items-center justify-center h-10 px-4 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white shadow transition transform hover:scale-105 focus:ring-4 focus:ring-emerald-300/50"
            title="Nuevo movimiento" aria-label="Nuevo movimiento">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="1.7">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Nuevo
        </button>
    </div>

    {{-- Table + Filters (Alpine) --}}
    <div x-data="inventoryPage({ products: @js($products) })" x-init="init()" x-cloak>
        {{-- Filtros --}}
        <div class="card p-3 mb-3 grid grid-cols-1 md:grid-cols-5 gap-3">
            <div class="md:col-span-2">
                <label class="block text-xs text-gray-500">Buscar</label>
                <input type="text" x-model="filters.q" @input.debounce.300ms="fetchRows()"
                    placeholder="Producto, SKU, referencia, notas…"
                    class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
            </div>
            <div>
                <label class="block text-xs text-gray-500">Producto</label>
                <select x-model="filters.productId" @change="fetchRows()"
                    class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                    <option value="">Todos</option>
                    <template x-for="p in products" :key="p.id">
                        <option :value="p.id" x-text="p.name + (p.sku ? ' ('+p.sku+')' : '')"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500">Tipo</label>
                <select x-model="filters.type" @change="fetchRows()"
                    class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                    <option value="">Todos</option>
                    <option value="in">Entrada</option>
                    <option value="out">Salida</option>
                    <option value="adjust">Ajuste</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs text-gray-500">Desde</label>
                    <input type="date" x-model="filters.date_from" @change="fetchRows()"
                        class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                </div>
                <div>
                    <label class="block text-xs text-gray-500">Hasta</label>
                    <input type="date" x-model="filters.date_to" @change="fetchRows()"
                        class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                </div>
            </div>
        </div>

        {{-- Tabla --}}
        <div
            class="overflow-x-auto bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-200">
                        <th class="px-3 py-2 text-left cursor-pointer" @click="sortBy('date')">
                            Fecha <span x-show="sort.field==='date'">(<span x-text="sort.dir"></span>)</span>
                        </th>
                        <th class="px-3 py-2 text-left">Producto</th>
                        <th class="px-3 py-2">SKU</th>
                        <th class="px-3 py-2">Tipo</th>
                        <th class="px-3 py-2 text-right">Cantidad</th>
                        <th class="px-3 py-2 text-right">Costo Unit. (USD)</th>
                        <th class="px-3 py-2 text-right">Total (USD)</th>
                        <th class="px-3 py-2">Ref.</th>
                        <th class="px-3 py-2 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Loader shimmer --}}
                    <template x-if="loading">
                        <tr>
                            <td colspan="9" class="px-3 py-6">
                                <div class="animate-pulse space-y-2">
                                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded"></div>
                                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-11/12"></div>
                                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-10/12"></div>
                                </div>
                            </td>
                        </tr>
                    </template>

                    {{-- Rows --}}
                    <template x-for="row in paged" :key="row.id">
                        <tr class="border-t border-gray-100 dark:border-gray-700 transition hover:bg-emerald-50/40 dark:hover:bg-emerald-900/10"
                            :class="row._flash ? 'animate-[flash_1.2s_ease]' : ''">
                            <td class="px-3 py-2" x-text="row.date"></td>
                            <td class="px-3 py-2">
                                <div class="font-medium" x-text="row.productName || '—'"></div>
                            </td>
                            <td class="px-3 py-2" x-text="row.sku || '—'"></td>
                            <td class="px-3 py-2">
                                <span class="inline-flex items-center text-xs px-2 py-0.5 rounded-full"
                                    :class="{
                                        'bg-emerald-100 text-emerald-700': row.type==='in',
                                        'bg-red-100 text-red-700': row.type==='out',
                                        'bg-amber-100 text-amber-700': row.type==='adjust',
                                    }"
                                    x-text="row.type==='in' ? 'Entrada' : (row.type==='out' ? 'Salida' : 'Ajuste')"></span>
                            </td>
                            <td class="px-3 py-2 text-right" x-text="Number(row.qty||0).toLocaleString()"></td>
                            <td class="px-3 py-2 text-right" x-text="Number(row.unitCostUSD||0).toFixed(2)"></td>
                            <td class="px-3 py-2 text-right" x-text="Number(row.totalCostUSD||0).toFixed(2)"></td>
                            <td class="px-3 py-2" x-text="row.reference || '—'"></td>
                            <td class="px-3 py-2">
                                <div class="flex items-center justify-center gap-2">
                                    <button @click="openEdit(row)"
                                        class="p-2 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/30 text-blue-600"
                                        title="Editar" aria-label="Editar">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M16.862 4.487l2.651 2.651M5 19l4.243-.707a2 2 0 00.985-.546l7.92-7.92a1.5 1.5 0 000-2.121L16.121 5.5a1.5 1.5 0 00-2.121 0l-7.92 7.92a2 2 0 00-.546.985L5 19z" />
                                        </svg>
                                    </button>

                                    <button @click="askDelete(row)"
                                        class="p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/30 text-red-600"
                                        title="Eliminar" aria-label="Eliminar">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M6 7.5h12M9.75 7.5V6A1.5 1.5 0 0111.25 4.5h1.5A1.5 1.5 0 0114.25 6v1.5M18 7.5l-.75 12a2.25 2.25 0 01-2.25 2.1H9A2.25 2.25 0 016.75 19.5L6 7.5" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <tr x-show="!loading && rows.length===0">
                        <td colspan="9" class="px-3 py-6 text-center text-gray-500">Sin movimientos…</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Paginación simple (cliente) --}}
        <div class="mt-3 flex items-center justify-between text-sm">
            <div>Mostrando <span x-text="paged.length"></span> de <span x-text="rows.length"></span></div>
            <div class="flex items-center gap-2">
                <select x-model.number="perPage" @change="page=1"
                    class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                    <option>10</option>
                    <option>25</option>
                    <option>50</option>
                </select>
                <button @click="prevPage()" :disabled="page === 1"
                    class="px-2 py-1 rounded border border-gray-200 dark:border-gray-700 disabled:opacity-50">«</button>
                <span x-text="page"></span>
                <button @click="nextPage()" :disabled="(page * perPage) >= rows.length"
                    class="px-2 py-1 rounded border border-gray-200 dark:border-gray-700 disabled:opacity-50">»</button>
            </div>
        </div>

        {{-- Toast --}}
        <template x-if="toast">
            <div class="fixed right-4 bottom-6 z-50 rounded-lg bg-emerald-600 text-white px-4 py-2 shadow"
                x-text="toast" x-transition @click="toast=null"></div>
        </template>

        {{-- Modal Crear/Editar --}}
        <div x-show="modal.open" class="fixed inset-0 z-50" x-transition.opacity>
            <div class="absolute inset-0 bg-black/40"></div>
            <div class="absolute inset-0 grid place-items-center p-2 sm:p-4">
                <div class="w-full h-full sm:h-auto sm:max-h-[90vh] sm:max-w-3xl overflow-y-auto bg-white dark:bg-gray-800 rounded-none sm:rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700"
                    x-transition.scale>
                    <div
                        class="sticky top-0 p-4 border-b border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 flex items-center justify-between">
                        <h3 class="text-lg font-semibold"
                            x-text="modal.isEdit ? 'Editar movimiento' : 'Nuevo movimiento'"></h3>
                        <button class="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700"
                            @click="closeModal()">✕</button>
                    </div>

                    <form class="p-4 space-y-4" @submit.prevent="save()">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-sm">Producto</label>
                                <div class="flex gap-2">
                                    <input type="text" placeholder="Buscar… nombre o SKU" x-model="modal.search"
                                        @input="filterProducts()"
                                        class="mt-1 w-1/2 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 px-2">
                                    <select x-model="modal.form.productId"
                                        class="mt-1 flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                        <option value="">— seleccionar —</option>
                                        <template x-for="p in modal.filtered" :key="p.id">
                                            <option :value="p.id"
                                                x-text="p.name + (p.sku? ' ('+p.sku+')':'')"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm">Fecha</label>
                                <input type="date" x-model="modal.form.date" required
                                    class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm">Tipo</label>
                                <select x-model="modal.form.type" required
                                    class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                    <option value="in">Entrada</option>
                                    <option value="out">Salida</option>
                                    <option value="adjust">Ajuste (+/-)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm">Cantidad</label>
                                <input type="number" step="1" x-model.number="modal.form.qty" required
                                    class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                            </div>
                            <div>
                                <label class="block text-sm">Costo unit. (USD)</label>
                                <input type="number" step="0.01" x-model.number="modal.form.unitCostUSD"
                                    class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                            </div>
                            <div>
                                <label class="block text-sm">Referencia</label>
                                <input type="text" x-model="modal.form.reference" maxlength="100"
                                    class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm">Notas</label>
                            <textarea rows="3" x-model="modal.form.notes"
                                class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"></textarea>
                        </div>

                        <div
                            class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                            <button type="button" @click="closeModal()"
                                class="px-4 py-2 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200">Cancelar</button>
                            <button type="submit"
                                class="px-4 py-2 rounded-md bg-emerald-600 hover:bg-emerald-700 text-white font-medium"
                                :disabled="saving">
                                <span x-show="!saving">Guardar</span>
                                <span x-show="saving" class="inline-flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"></svg> Guardando…
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal confirmar delete --}}
        <div x-show="confirm.open" class="fixed inset-0 z-50" x-transition.opacity>
            <div class="absolute inset-0 bg-black/40"></div>
            <div class="absolute inset-0 grid place-items-center p-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6" x-transition.scale>
                    <h2 class="text-lg font-semibold mb-2">¿Eliminar movimiento?</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Esta acción no se puede deshacer.</p>
                    <div class="mt-6 flex justify-end gap-2">
                        <button @click="confirm.open=false"
                            class="px-4 py-2 rounded bg-gray-100 dark:bg-gray-700">Cancelar</button>
                        <button @click="doDelete()" class="px-4 py-2 rounded bg-red-600 hover:bg-red-700 text-white">
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Script Alpine --}}
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('inventoryPage', (opts) => ({
                    products: opts.products || [],
                    rows: [],
                    loading: false,
                    toast: null,

                    // filtros + orden + paginación
                    filters: {
                        q: '',
                        productId: '',
                        type: '',
                        date_from: '',
                        date_to: ''
                    },
                    sort: {
                        field: 'date',
                        dir: 'desc'
                    },
                    page: 1,
                    perPage: 25,

                    // modal crear/editar
                    modal: {
                        open: false,
                        isEdit: false,
                        id: null,
                        search: '',
                        filtered: [],
                        form: {
                            productId: '',
                            type: 'in',
                            qty: 1,
                            unitCostUSD: null,
                            date: (new Date()).toISOString().slice(0, 10),
                            reference: '',
                            notes: ''
                        }
                    },
                    saving: false,

                    // confirm delete
                    confirm: {
                        open: false,
                        id: null
                    },

                    get sorted() {
                        const copy = [...this.rows];
                        const f = this.sort.field,
                            d = this.sort.dir === 'asc' ? 1 : -1;
                        copy.sort((a, b) => {
                            const va = (a[f] ?? ''),
                                vb = (b[f] ?? '');
                            if (va < vb) return -1 * d;
                            if (va > vb) return 1 * d;
                            return 0;
                        });
                        return copy;
                    },
                    get paged() {
                        const start = (this.page - 1) * this.perPage;
                        return this.sorted.slice(start, start + this.perPage);
                    },

                    init() {
                        this.filterProducts();
                        this.fetchRows();
                    },

                    sortBy(f) {
                        if (this.sort.field === f) this.sort.dir = this.sort.dir === 'asc' ? 'desc' : 'asc';
                        else {
                            this.sort.field = f;
                            this.sort.dir = 'asc';
                        }
                    },
                    nextPage() {
                        if ((this.page * this.perPage) < this.rows.length) this.page++;
                    },
                    prevPage() {
                        if (this.page > 1) this.page--;
                    },

                    async fetchRows() {
                        this.loading = true;
                        try {
                            const params = new URLSearchParams(this.filters);
                            const res = await fetch(`/api/inventory?${params.toString()}`, {
                                headers: {
                                    'Accept': 'application/json'
                                }
                            });
                            this.rows = await res.json();
                            this.rows.forEach(r => r._flash = false);
                            this.page = 1;
                        } catch (e) {
                            console.error(e);
                        } finally {
                            this.loading = false;
                        }
                    },

                    // Modal helpers
                    openCreate() {
                        this.modal.open = true;
                        this.modal.isEdit = false;
                        this.modal.id = null;
                        this.modal.form = {
                            productId: '',
                            type: 'in',
                            qty: 1,
                            unitCostUSD: null,
                            date: (new Date()).toISOString().slice(0, 10),
                            reference: '',
                            notes: ''
                        };
                        this.modal.search = '';
                        this.filterProducts();
                    },
                    openEdit(row) {
                        this.modal.open = true;
                        this.modal.isEdit = true;
                        this.modal.id = row.id;
                        this.modal.search = '';
                        this.modal.form = {
                            productId: row.productId || '',
                            type: row.type || 'in',
                            qty: Number(row.qty || 1),
                            unitCostUSD: row.unitCostUSD ?? null,
                            date: row.date || (new Date()).toISOString().slice(0, 10),
                            reference: row.reference || '',
                            notes: row.notes || ''
                        };
                        this.filterProducts();
                    },
                    closeModal() {
                        this.modal.open = false;
                    },

                    filterProducts() {
                        const q = (this.modal.search || '').toLowerCase();
                        this.modal.filtered = this.products.filter(p =>
                            (p.name || '').toLowerCase().includes(q) || (p.sku || '').toLowerCase()
                            .includes(q)
                        ).slice(0, 80);
                    },

                    async save() {
                        if (!this.modal.form.productId) {
                            alert('Selecciona un producto');
                            return;
                        }
                        this.saving = true;
                        try {
                            const method = this.modal.isEdit ? 'PUT' : 'POST';
                            const url = this.modal.isEdit ? `/api/inventory/${this.modal.id}` :
                                '/api/inventory';
                            const res = await fetch(url, {
                                method,
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify(this.modal.form)
                            });
                            if (!res.ok) throw new Error('Error guardando');
                            this.closeModal();
                            await this.fetchRows();
                            // flash 1er registro (asumiendo orden por fecha desc)
                            if (this.rows[0]) this.rows[0]._flash = true;
                            this.toast = this.modal.isEdit ? 'Movimiento actualizado' :
                                'Movimiento creado';
                            setTimeout(() => this.toast = null, 2500);
                        } catch (e) {
                            console.error(e);
                            alert('No se pudo guardar');
                        } finally {
                            this.saving = false;
                        }
                    },

                    askDelete(row) {
                        this.confirm.open = true;
                        this.confirm.id = row.id;
                    },
                    async doDelete() {
                        if (!this.confirm.id) return;
                        try {
                            const res = await fetch(`/api/inventory/${this.confirm.id}`, {
                                method: 'DELETE',
                                headers: {
                                    'Accept': 'application/json'
                                }
                            });
                            if (!res.ok) throw new Error('Error');
                            this.confirm.open = false;
                            await this.fetchRows();
                            this.toast = 'Movimiento eliminado';
                            setTimeout(() => this.toast = null, 2000);
                        } catch (e) {
                            console.error(e);
                            alert('No se pudo eliminar');
                        }
                    }
                }));
            });
        </script>

        {{-- Abrir modal nuevo desde el botón del header --}}
        <script>
            document.addEventListener('open-movement-modal', () => {
                const cmp = Alpine.$data(document.querySelector('[x-data^="inventoryPage"]'));
                cmp?.openCreate();
            });
        </script>

        <style>
            @keyframes flash {
                0% {
                    background-color: rgba(16, 185, 129, .15)
                }

                100% {
                    background-color: transparent
                }
            }
        </style>
    </div>
</x-layout>
