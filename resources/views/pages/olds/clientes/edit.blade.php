{{-- resources/views/pages/clients/edit.blade.php --}}
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
        <div>
            <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Editar cliente</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">ID: {{ $client['id'] }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('clients.index') }}"
                class="h-10 px-4 rounded-lg border border-gray-300 dark:border-gray-600
                      text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                Volver
            </a>

            {{-- Modal eliminar --}}
            <div x-data="{ open: false }">
                <button @click="open=true" class="h-10 px-4 rounded-lg bg-red-600 hover:bg-red-700 text-white">
                    Eliminar
                </button>
                <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
                    x-transition.opacity>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-sm p-6" x-transition.scale>
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">¿Eliminar cliente?</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Esta acción no se puede deshacer.
                        </p>
                        <div class="mt-5 flex justify-end gap-2">
                            <button @click="open=false"
                                class="px-4 py-2 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200">
                                Cancelar
                            </button>
                            <form method="POST" action="{{ url('/clients/' . $client['id']) }}">
                                @csrf @method('DELETE')
                                <button class="px-4 py-2 rounded-md bg-red-600 hover:bg-red-700 text-white">
                                    Eliminar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
        @include('pages.clients._form', [
            'action' => route('clients.update', $client['id']),
            'method' => 'PUT',
            'client' => $client,
            'submitLabel' => 'Guardar cambios',
        ])
    </div>
</x-layout>
