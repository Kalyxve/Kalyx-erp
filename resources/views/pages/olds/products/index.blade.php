{{-- resources/views/pages/products/index.blade.php --}}
<x-layout>
    {{-- Toast éxito --}}
    @if (session('success'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 2400)" x-show="show" x-cloak class="fixed top-4 right-4 z-50">
            <div class="bg-emerald-600 text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-2"
                x-transition.opacity.scale>
                <img src="{{ asset('logo.png') }}" class="w-5 h-5 rounded bg-white/10 p-0.5" alt="Kalyx">
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    <div class="mb-5 flex items-center justify-between">
        <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Productos</h1>

        <button @click="$dispatch('open-create')"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Nuevo
        </button>
    </div>

    {{-- Filtros --}}
    <form method="GET" x-ref="f" class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-3">
        <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Buscar por nombre, código o marca"
            class="h-10 px-3 rounded-lg border border-gray-300 dark:border-gray-600
                      bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100"
            @input.debounce.400ms="$refs.f.submit()">

        <select name="category"
            class="h-10 px-3 rounded-lg border border-gray-300 dark:border-gray-600
                bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100"
            @change="$refs.f.submit()">
            <option value="">Todas las categorías</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat['id'] }}" {{ ($category ?? '') === $cat['id'] ? 'selected' : '' }}>
                    {{ $cat['nombre'] }}
                </option>
            @endforeach
        </select>

        <select name="limit"
            class="h-10 px-3 rounded-lg border border-gray-300 dark:border-gray-600
                bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100"
            @change="$refs.f.submit()">
            @foreach ([10, 25, 50] as $n)
                <option value="{{ $n }}" {{ (int) ($limit ?? 25) === $n ? 'selected' : '' }}>
                    {{ $n }} por página</option>
            @endforeach
        </select>

        <label class="inline-flex items-center gap-2 h-10">
            <input type="checkbox" name="onlyActive" value="1" class="rounded border-gray-300 dark:border-gray-600"
                {{ $onlyActive ? 'checked' : '' }} @change="$refs.f.submit()">
            <span class="text-sm text-gray-700 dark:text-gray-200">Solo activos</span>
        </label>
    </form>

    {{-- Tabla --}}
    <div
        class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/40">
                    <tr class="text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                        <th class="px-4 py-3">Código</th>
                        <th class="px-4 py-3">Nombre</th>
                        <th class="px-4 py-3">Marca</th>
                        <th class="px-4 py-3">Presentación</th>
                        <th class="px-4 py-3">Categoría</th>
                        <th class="px-4 py-3 text-right">USD Base</th>
                        <th class="px-4 py-3 text-right">Bs Base</th>
                        <th class="px-4 py-3 text-center">Stock</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($items as $p)
                        @if (($p['id'] ?? '') !== '__schema')
                            <tr class="hover:bg-emerald-50/40 dark:hover:bg-gray-700/40">
                                <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-200">
                                    {{ $p['codigo'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-800 dark:text-gray-100">{{ $p['nombre'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $p['marca'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $p['presentacion'] ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                    {{ $p['categoria_nombre'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-200">
                                    {{ number_format((float) ($p['precio_usd_base'] ?? 0), 2) }}</td>
                                <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-200">
                                    {{ number_format((float) ($p['precio_bs_base'] ?? 0), 2) }}</td>
                                <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-200">
                                    {{ (int) ($p['stock'] ?? 0) }}</td>
                                <td class="px-4 py-3">
                                    @php $activo = (bool)($p['activo'] ?? true); @endphp
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                        {{ $activo ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-700' }}">
                                        {{ $activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="$dispatch('open-edit', {{ json_encode($p) }})"
                                            class="h-9 px-3 rounded-md border border-gray-300 dark:border-gray-600
                                                       text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                            Editar
                                        </button>
                                        <button
                                            @click="$dispatch('open-delete', {id:'{{ $p['id'] }}', name:'{{ addslashes($p['nombre'] ?? '') }}'})"
                                            class="h-9 px-3 rounded-md bg-red-600 hover:bg-red-700 text-white transition">
                                            Eliminar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                No hay productos registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Cargar más --}}
        <div class="p-3">
            @if (!empty($nextCursor))
                <a href="{{ route(
                    'products.index',
                    array_filter([
                        'q' => $q,
                        'limit' => $limit,
                        'cursor' => $nextCursor,
                        'category' => $category,
                        'onlyActive' => $onlyActive ? 1 : null,
                    ]),
                ) }}"
                    class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 rounded-md
                          border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800
                          text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    Cargar más
                </a>
            @endif
        </div>
    </div>

    {{-- ===== MODALES ===== --}}
    <div x-data="productModals()" x-init="init()" x-cloak>
        {{-- Crear --}}
        <div x-show="createOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
            x-transition.opacity>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-2xl p-6" x-transition.scale>
                <div class="flex items-center gap-3 mb-4">
                    <img src="{{ asset('logo.png') }}" class="w-7 h-7 rounded bg-emerald-600/10 p-1" alt="Kalyx">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Nuevo producto</h3>
                </div>

                <form method="POST" action="{{ route('products.store') }}" class="space-y-3">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre</label>
                            <input name="nombre" required
                                class="w-full h-10 px-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Marca</label>
                            <input name="marca"
                                class="w-full h-10 px-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Categoría</label>
                            <select name="categoria_id" required
                                class="w-full h-10 px-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100">
                                <option value="">Seleccione...</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat['id'] }}">{{ $cat['nombre'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">Presentación</label>
                            <select name="presentacion" required
                                class="w-full h-10 px-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100">
                                @foreach ($presentaciones as $p)
                                    <option value="{{ $p }}">{{ $p }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Precio USD
                                (base)</label>
                            <input name="precio_usd_base" type="number" step="0.01" min="0" required
                                class="w-full h-10 px-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Precio Bs (base)
                            </label>
                            <input name="precio_bs_base" type="number" step="0.01" min="0"
                                class="w-full h-10 px-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-100 text-gray-600"
                                readonly>
                            <p class="text-xs text-gray-500 mt-1">Se calculará con la tasa del día.</p>
                        </div>


                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Stock</label>
                            <input name="stock" type="number" step="1" min="0"
                                class="w-full h-10 px-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100">
                        </div>

                        <label class="inline-flex items-center gap-2 mt-6">
                            <input type="checkbox" name="activo" value="1" checked
                                class="rounded border-gray-300 dark:border-gray-600">
                            <span class="text-sm text-gray-700 dark:text-gray-200">Activo</span>
                        </label>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="createOpen=false"
                            class="h-10 px-4 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            Cancelar
                        </button>
                        <button
                            class="h-10 px-4 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white">Crear</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Editar --}}
        <div x-show="editOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
            x-transition.opacity>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-2xl p-6" x-transition.scale>
                <div class="flex items-center gap-3 mb-4">
                    <img src="{{ asset('logo.png') }}" class="w-7 h-7 rounded bg-emerald-600/10 p-1" alt="Kalyx">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Editar producto</h3>
                </div>

                <form method="POST" :action="'/products/' + editData.id" class="space-y-3">
                    @csrf @method('PUT')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre</label>
                            <input name="nombre" x-model="editData.nombre" required
                                class="w-full h-10 px-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Marca</label>
                            <input name="marca" x-model="editData.marca"
                                class="w-full h-10 px-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Categoría</label>
                            <select name="categoria_id" x-model="editData.categoria_id" required
                                class="w-full h-10 px-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100">
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat['id'] }}">{{ $cat['nombre'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">Presentación</label>
                            <select name="presentacion" x-model="editData.presentacion" required
                                class="w-full h-10 px-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100">
                                @foreach ($presentaciones as $p)
                                    <option value="{{ $p }}">{{ $p }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Precio USD
                                (base)</label>
                            <input name="precio_usd_base" type="number" step="0.01" min="0"
                                x-model="editData.precio_usd_base" required
                                class="w-full h-10 px-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100">
                        </div>

                        <input name="precio_bs_base" type="number" step="0.01" min="0"
                            x-model="editData.precio_bs_base"
                            class="w-full h-10 px-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-100 text-gray-600"
                            readonly>


                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Stock</label>
                            <input name="stock" type="number" step="1" min="0"
                                x-model="editData.stock"
                                class="w-full h-10 px-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100">
                        </div>

                        <label class="inline-flex items-center gap-2 mt-6">
                            <input type="checkbox" name="activo" value="1" :checked="editData.activo"
                                class="rounded border-gray-300 dark:border-gray-600">
                            <span class="text-sm text-gray-700 dark:text-gray-200">Activo</span>
                        </label>
                    </div>

                    <div class="flex justify-between items-center pt-2">
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            <span class="font-mono">Código:</span>
                            <span x-text="editData.codigo || 'Se genera por categoría'"></span>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" @click="editOpen=false"
                                class="h-10 px-4 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                Cancelar
                            </button>
                            <button
                                class="h-10 px-4 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white">Guardar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Eliminar --}}
        <div x-show="deleteOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
            x-transition.opacity>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md p-6" x-transition.scale>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">¿Eliminar producto?</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">Esta acción no se puede deshacer.</p>
                <p class="mt-2 text-sm text-gray-800 dark:text-gray-200"><strong x-text="deleteName"></strong></p>
                <div class="mt-6 flex justify-end gap-2">
                    <button @click="deleteOpen=false"
                        class="px-4 py-2 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200">
                        Cancelar
                    </button>
                    <form method="POST" :action="'/products/' + deleteId">
                        @csrf @method('DELETE')
                        <button class="px-4 py-2 rounded-md bg-red-600 hover:bg-red-700 text-white">
                            Eliminar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function productModals() {
            return {
                createOpen: false,
                editOpen: false,
                deleteOpen: false,
                editData: {},
                deleteId: null,
                deleteName: '',
                init() {
                    document.addEventListener('open-create', () => {
                        this.createOpen = true
                    });
                    document.addEventListener('open-edit', (e) => {
                        this.editData = e.detail;
                        this.editOpen = true
                    });
                    document.addEventListener('open-delete', (e) => {
                        this.deleteId = e.detail.id;
                        this.deleteName = e.detail.name;
                        this.deleteOpen = true;
                    });
                }
            }
        }
    </script>
    <script>
        // Tasa enviada desde el controller
        window.KALYX_RATE = Number({{ json_encode($exchangeRate) }});

        // Escucha cambios en cualquier form de productos
        document.addEventListener('input', (e) => {
            if (e.target && e.target.name === 'precio_usd_base') {
                const form = e.target.closest('form');
                const bs = form?.querySelector('input[name="precio_bs_base"]');
                const usdVal = Number(e.target.value || 0);
                const rate = Number(window.KALYX_RATE || 0);
                if (bs && rate > 0) bs.value = (usdVal * rate).toFixed(2);
            }
        }, {
            passive: true
        });
    </script>
</x-layout>
