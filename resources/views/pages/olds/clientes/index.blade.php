{{-- resources/views/pages/clients/index.blade.php --}}
<x-layout>
    {{-- Toast éxito --}}
    @if (session('success'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 2500)" x-show="show" x-cloak class="fixed top-4 right-4 z-50">
            <div class="bg-emerald-600 text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-2"
                x-transition.opacity.scale>
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    <div class="mb-5 flex items-center justify-between">
        <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Clientes</h1>
        <a href="{{ route('clients.create') }}"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Nuevo
        </a>
    </div>

    {{-- Filtros / búsqueda --}}
    <form method="GET" x-data x-ref="form" class="mb-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <input type="text" name="q" value="{{ $q ?? '' }}"
                    placeholder="Buscar por nombre o RIF/Cédula"
                    class="w-full h-10 px-3 rounded-lg border border-gray-300 dark:border-gray-600
                              bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100"
                    @input.debounce.400ms="$refs.form.submit()">
            </div>
            <div>
                <select name="limit"
                    class="h-10 px-3 rounded-lg border border-gray-300 dark:border-gray-600
                       bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100"
                    @change="$refs.form.submit()">
                    @foreach ([10, 25, 50] as $n)
                        <option value="{{ $n }}" {{ (int) ($limit ?? 25) === $n ? 'selected' : '' }}>
                            {{ $n }} por página</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>

    {{-- Tabla --}}
    <div
        class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/40">
                    <tr class="text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                        <th class="px-4 py-3">Nombre</th>
                        <th class="px-4 py-3">RIF/Cédula</th>
                        <th class="px-4 py-3">Teléfono</th>
                        <th class="px-4 py-3">Dirección</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($clients as $c)
                        @if (($c['id'] ?? '') !== '__schema')
                            <tr class="hover:bg-emerald-50/40 dark:hover:bg-gray-700/40">
                                <td class="px-4 py-3">
                                    <a href="{{ route('clients.show', $c['id']) }}"
                                        class="font-medium text-emerald-700 dark:text-emerald-400 hover:underline">
                                        {{ $c['nombre'] ?? '—' }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $c['rif_cedula'] ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $c['telefono'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300 truncate max-w-xs">
                                    {{ $c['direccion'] ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    @php $estado = $c['estado'] ?? 'activo'; @endphp
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                        {{ $estado === 'activo' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-700' }}">
                                        {{ ucfirst($estado) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('clients.edit', $c['id']) }}"
                                            class="h-9 px-3 rounded-md border border-gray-300 dark:border-gray-600
                                                  text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                            Editar
                                        </a>

                                        {{-- Eliminar (modal pequeño) --}}
                                        <div x-data="{ open: false }" class="inline-block">
                                            <button @click="open=true"
                                                class="h-9 px-3 rounded-md bg-red-600 hover:bg-red-700 text-white transition">
                                                Eliminar
                                            </button>
                                            <div x-show="open" x-cloak
                                                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
                                                x-transition.opacity>
                                                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-sm p-6"
                                                    x-transition.scale>
                                                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                                                        ¿Eliminar cliente?
                                                    </h3>
                                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                                        Esta acción no se puede deshacer.
                                                    </p>
                                                    <div class="mt-5 flex justify-end gap-2">
                                                        <button @click="open=false"
                                                            class="px-4 py-2 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200">
                                                            Cancelar
                                                        </button>
                                                        <form method="POST"
                                                            action="{{ url('/clients/' . $c['id']) }}">
                                                            @csrf @method('DELETE')
                                                            <button
                                                                class="px-4 py-2 rounded-md bg-red-600 hover:bg-red-700 text-white">
                                                                Eliminar
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                No hay clientes registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginación por cursor --}}
        <div class="p-3">
            @if (!empty($nextCursor))
                <a href="{{ route('clients.index', array_filter(['q' => $q, 'limit' => $limit, 'cursor' => $nextCursor])) }}"
                    class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 rounded-md
                          border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800
                          text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    Cargar más
                </a>
            @endif
        </div>
    </div>
</x-layout>
