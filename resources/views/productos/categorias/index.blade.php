{{-- resources/views/productos/categorias/index.blade.php --}}
<x-layout>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Categorías</h1>
            <a href="{{ route('productos.index') }}" class="px-3 py-2 rounded-md border dark:border-gray-600">Volver a
                productos</a>
        </div>

        <div
            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/40">
                    <tr class="text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3">Nombre</th>
                        <th class="px-4 py-3">Slug</th>
                        <th class="px-4 py-3">Descripción</th>
                        <th class="px-4 py-3">Activo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach (\App\Models\Categoria::orderBy('nombre')->get() as $c)
                        <tr>
                            <td class="px-4 py-3">{{ $c->id }}</td>
                            <td class="px-4 py-3">{{ $c->nombre }}</td>
                            <td class="px-4 py-3 font-mono">{{ $c->slug }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $c->descripcion }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $c->activo ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-700' }}">
                                    {{ $c->activo ? 'Sí' : 'No' }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-layout>
