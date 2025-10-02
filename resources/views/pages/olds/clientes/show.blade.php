{{-- resources/views/pages/clients/show.blade.php --}}
<x-layout>
    {{-- Toast éxito (por si llegas aquí después de alguna acción) --}}
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
        <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Detalle del cliente</h1>
        <div class="flex items-center gap-2">
            <a href="{{ route('clients.edit', $client['id']) }}"
                class="h-10 px-4 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white">
                Editar
            </a>
            <a href="{{ route('clients.index') }}"
                class="h-10 px-4 rounded-lg border border-gray-300 dark:border-gray-600
                      text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                Volver
            </a>
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-5">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
            <dl class="grid grid-cols-1 gap-y-3">
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Nombre</dt>
                    <dd class="text-base font-medium text-gray-800 dark:text-gray-100">{{ $client['nombre'] ?? '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">RIF/Cédula</dt>
                    <dd class="text-base font-medium text-gray-800 dark:text-gray-100">
                        {{ $client['rif_cedula'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Teléfono</dt>
                    <dd class="text-base font-medium text-gray-800 dark:text-gray-100">{{ $client['telefono'] ?? '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Dirección</dt>
                    <dd class="text-base font-medium text-gray-800 dark:text-gray-100 whitespace-pre-wrap">
                        {{ $client['direccion'] ?? '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Estado</dt>
                    @php $estado = $client['estado'] ?? 'activo'; @endphp
                    <dd>
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $estado === 'activo' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-700' }}">
                            {{ ucfirst($estado) }}
                        </span>
                    </dd>
                </div>
            </dl>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Información de cuenta</h3>
            <dl class="grid grid-cols-1 gap-y-3">
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Saldo pendiente (USD eq.)</dt>
                    <dd class="text-base font-medium text-gray-800 dark:text-gray-100">
                        {{ number_format((float) ($client['saldo_pendiente_usd_eq'] ?? 0), 2) }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Último movimiento</dt>
                    <dd class="text-base font-medium text-gray-800 dark:text-gray-100">
                        {{ $client['ultimo_movimiento'] ?? '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Creado</dt>
                    <dd class="text-base font-medium text-gray-800 dark:text-gray-100">
                        {{ $client['createdAt'] ?? '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Actualizado</dt>
                    <dd class="text-base font-medium text-gray-800 dark:text-gray-100">
                        {{ $client['updatedAt'] ?? '—' }}
                    </dd>
                </div>
            </dl>
        </div>
    </div>
</x-layout>
