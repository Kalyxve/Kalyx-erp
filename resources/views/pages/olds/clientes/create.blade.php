{{-- resources/views/pages/clients/create.blade.php --}}
<x-layout>
    {{-- Toast éxito (si algún flow te redirige aquí con success) --}}
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
        <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Nuevo cliente</h1>
        <a href="{{ route('clients.index') }}"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600
                  text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
            Volver
        </a>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
        @include('pages.clients._form', [
            'action' => route('clients.store'),
            'method' => 'POST',
            'client' => null,
            'submitLabel' => 'Crear cliente',
        ])
    </div>
</x-layout>
