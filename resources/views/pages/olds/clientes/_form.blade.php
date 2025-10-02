{{-- resources/views/pages/clients/_form.blade.php --}}
@props([
    'action' => '#',
    'method' => 'POST',
    'client' => null,
    'submitLabel' => 'Guardar',
])

<form method="POST" action="{{ $action }}" class="space-y-4">
    @csrf
    @if (in_array(strtoupper($method), ['PUT', 'PATCH']))
        @method($method)
    @endif

    {{-- Errores generales --}}
    @if ($errors->has('general'))
        <div class="p-3 rounded-lg bg-red-50 text-red-700 border border-red-200">
            {{ $errors->first('general') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre</label>
            <input type="text" name="nombre" value="{{ old('nombre', $client['nombre'] ?? '') }}"
                class="w-full h-10 px-3 rounded-lg border border-gray-300 dark:border-gray-600
                          bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100">
            @error('nombre')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">RIF / Cédula</label>
            <input type="text" name="rif_cedula" value="{{ old('rif_cedula', $client['rif_cedula'] ?? '') }}"
                class="w-full h-10 px-3 rounded-lg border border-gray-300 dark:border-gray-600
                          bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100">
            @error('rif_cedula')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Teléfono</label>
            <input type="text" name="telefono" value="{{ old('telefono', $client['telefono'] ?? '') }}"
                class="w-full h-10 px-3 rounded-lg border border-gray-300 dark:border-gray-600
                          bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100">
            @error('telefono')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Estado</label>
            <select name="estado"
                class="w-full h-10 px-3 rounded-lg border border-gray-300 dark:border-gray-600
                           bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100">
                @php $estado = old('estado', $client['estado'] ?? 'activo'); @endphp
                <option value="activo" {{ $estado === 'activo' ? 'selected' : '' }}>Activo</option>
                <option value="inactivo" {{ $estado === 'inactivo' ? 'selected' : '' }}>Inactivo</option>
            </select>
            @error('estado')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Dirección</label>
            <textarea name="direccion" rows="3"
                class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600
                             bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100">{{ old('direccion', $client['direccion'] ?? '') }}</textarea>
            @error('direccion')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="flex items-center justify-end gap-2 pt-2">
        <a href="{{ route('clients.index') }}"
            class="h-10 px-4 rounded-lg border border-gray-300 dark:border-gray-600
                  text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
            Cancelar
        </a>
        <button class="h-10 px-4 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold transition">
            {{ $submitLabel }}
        </button>
    </div>
</form>
