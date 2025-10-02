{{-- resources/views/pages/payments/index.blade.php --}}
<x-layout :settings="['currencyDefault' => 'USD', 'exchangeRate' => $rate]">
    @if (!empty($authToken))
        <meta name="kalyx-auth" content="{{ $authToken }}">
    @endif>

    {{-- Select2 (CDN) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style>
        .select2-container {
            width: 100% !important
        }

        .select2-container .select2-selection--single {
            height: 2.5rem
        }

        .select2-selection__rendered {
            line-height: 2.5rem !important
        }

        .select2-selection__arrow {
            height: 2.5rem !important
        }

        .select2-container .select2-dropdown {
            z-index: 70 !important
        }

        [x-cloak] {
            display: none !important
        }

        /* Éxito */
        .checkmark {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            display: inline-block;
            position: relative;
            box-shadow: inset 0 0 0 4px #10b981;
            transform: scale(.85);
            animation: pop .25s ease-out forwards
        }

        .checkmark__check {
            position: absolute;
            left: 18px;
            top: 34px;
            width: 28px;
            height: 14px;
            border-left: 4px solid #10b981;
            border-bottom: 4px solid #10b981;
            transform: rotate(-45deg);
            opacity: 0;
            animation: draw .35s .18s ease-out forwards
        }

        @keyframes pop {
            to {
                transform: scale(1)
            }
        }

        @keyframes draw {
            to {
                opacity: 1
            }
        }
    </style>

    <script id="bootstrap-payments-json" type="application/json">
        {!! isset($bootstrap) ? json_encode($bootstrap, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : '' !!}
    </script>

    <div x-data="salesPage({ rate: {{ json_encode($rate) }} })" x-init="init()" x-cloak>

        {{-- ===== MODAL: Éxito ===== --}}
        <div x-show="successOpen" class="fixed inset-0 z-[70] bg-black/40 overflow-y-auto" x-transition.opacity>
            <div class="mx-auto my-10 w-full max-w-sm px-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-6 text-center" x-transition.scale>
                    <span class="checkmark"></span><span class="checkmark__check"></span>
                    <h3 class="text-lg font-semibold mt-4 text-gray-900 dark:text-gray-100" x-text="successTitle"></h3>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-1" x-text="successMessage"></p>
                    <button type="button"
                        class="mt-5 px-4 h-10 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700"
                        @click="successOpen=false">Cerrar</button>
                </div>
            </div>
        </div>

        {{-- ===== MODAL: Nueva venta ===== --}}
        <div x-show="newSaleOpen" class="fixed inset-0 z-50 bg-black/40 overflow-y-auto" x-transition.opacity>
            <div class="mx-auto my-4 sm:my-10 w-full max-w-4xl px-3">
                <div id="saleModal"
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-6 max-h-[90vh] overflow-y-auto"
                    x-transition.scale.origin.top>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-9 h-9 rounded-2xl bg-emerald-600/10 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-emerald-700" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M3 3h18v4H3zM3 7v14h18V7M7 11h4v6H7zM13 11h4v6h-4z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Nueva venta</h3>
                    </div>

                    <form method="POST" action="{{ route('payments.store') }}" @submit="beforeSubmit">
                        @csrf

                        {{-- Tipo de venta --}}
                        <div class="mb-4 flex flex-wrap items-center gap-3">
                            <input type="hidden" name="sale_type" :value="saleType">
                            <div
                                class="inline-flex rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                                <button type="button"
                                    :class="saleType === 'contado' ? 'bg-emerald-600 text-white' :
                                        'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200'"
                                    class="px-3 py-1.5 text-sm" @click="setSaleType('contado')"
                                    x-transition>Contado</button>
                                <button type="button"
                                    :class="saleType === 'credito' ? 'bg-emerald-600 text-white' :
                                        'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200'"
                                    class="px-3 py-1.5 text-sm" @click="setSaleType('credito')"
                                    x-transition>Crédito</button>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-300" x-show="saleType==='contado'">Pago
                                completo al crear.</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300" x-show="saleType==='credito'">Permite
                                inicial y saldo pendiente.</p>
                        </div>

                        {{-- Cliente --}}
                        <div class="mb-4">
                            <div class="flex items-center justify-between mb-1">
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cliente</label>
                                <button type="button"
                                    class="text-sm text-emerald-700 hover:underline flex items-center gap-1"
                                    @click="openClientCreate()">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                    Nuevo cliente
                                </button>
                            </div>

                            {{-- Primera línea fija + clientes desde la segunda --}}
                            <select id="clientSelect" class="w-full">
                                <option value="">— Selección cliente —</option>
                            </select>
                            <input type="hidden" name="cliente_id" x-ref="clienteId">
                            <input type="hidden" name="cliente_nombre" x-ref="clienteNombre">

                            <template x-if="client">
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                    Seleccionado: <span class="font-semibold" x-text="client?.nombre"></span>
                                    <span class="text-gray-500"
                                        x-text="' (' + (client?.rif_cedula || '—') + ')'"></span>
                                </p>
                            </template>
                        </div>

                        {{-- Productos --}}
                        <div class="mb-3">
                            <div class="flex items-center justify-between mb-1">
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">Productos</label>
                                <button type="button"
                                    class="text-sm text-emerald-700 hover:underline flex items-center gap-1"
                                    @click="openProductCreate()">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                    Nuevo producto
                                </button>
                            </div>

                            <select id="productSelect" class="w-full"></select>
                            <p class="text-xs text-gray-500 mt-1">Selecciona y se agrega a la tabla de ítems.</p>
                        </div>

                        {{-- Tabla items --}}
                        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-700/50">
                                    <tr>
                                        <th class="px-2 py-2 text-left">Producto</th>
                                        <th class="px-2 py-2 text-right">Precio USD</th>
                                        <th class="px-2 py-2 text-right">Cantidad</th>
                                        <th class="px-2 py-2 text-right">Total USD</th>
                                        <th class="px-2 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(it, idx) in items" :key="idx">
                                        <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/30">
                                            <td class="px-2 py-2">
                                                <input type="hidden" :name="`items[${idx}][producto_id]`"
                                                    :value="it.id">
                                                <input type="hidden" :name="`items[${idx}][nombre]`"
                                                    :value="it.nombre">
                                                <div class="font-medium" x-text="it.nombre"></div>
                                                <div class="text-xs text-gray-500" x-text="(it.codigo||'')"></div>
                                            </td>
                                            <td class="px-2 py-2 text-right">
                                                <input type="number" step="0.01" min="0"
                                                    class="w-24 h-9 rounded border-gray-300 dark:border-gray-600 text-right"
                                                    :name="`items[${idx}][precio_usd]`" x-model.number="it.precio_usd"
                                                    @input="recalc">
                                            </td>
                                            <td class="px-2 py-2 text-right">
                                                <input type="number" step="0.01" min="0.01"
                                                    class="w-24 h-9 rounded border-gray-300 dark:border-gray-600 text-right"
                                                    :name="`items[${idx}][qty]`" x-model.number="it.qty"
                                                    @input="recalc">
                                            </td>
                                            <td class="px-2 py-2 text-right"
                                                x-text="(it.precio_usd*it.qty).toFixed(2)"></td>
                                            <td class="px-2 py-2 text-right">
                                                <button type="button"
                                                    class="h-8 w-8 rounded-lg bg-red-100 text-red-700 flex items-center justify-center"
                                                    title="Quitar" @click="removeItem(idx)">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                        stroke-width="1.5">
                                                        <path
                                                            d="M6 7h12M9 7v10m6-10v10M4 7h16l-1 13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 7zM9 7l1-2h4l1 2" />
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        {{-- Totales + BCV --}}
                        <div class="flex flex-col md:flex-row justify-end gap-6 mt-4">
                            <div class="text-sm text-gray-600 dark:text-gray-300">
                                <div>BCV (Bs/USD): <span class="font-semibold"
                                        x-text="rate.toLocaleString('es-VE',{minimumFractionDigits:2})"></span></div>
                                <div class="mt-1">IVA %:
                                    <input type="number" step="0.01" min="0" max="1"
                                        name="iva_percent"
                                        class="w-20 h-8 rounded border-gray-300 dark:border-gray-600 text-right"
                                        x-model.number="ivaP" @input="recalc">
                                </div>
                            </div>
                            <div class="min-w-[280px]">
                                <div class="flex justify-between py-0.5"><span>Subtotal (USD)</span><span
                                        x-text="subtotal.toFixed(2)"></span></div>
                                <div class="flex justify-between py-0.5"><span>IVA (USD)</span><span
                                        x-text="iva.toFixed(2)"></span></div>
                                <div class="flex justify-between py-0.5 font-semibold"><span>Total (USD)</span><span
                                        x-text="total.toFixed(2)"></span></div>
                                <div class="flex justify-between py-0.5"><span>Total (Bs)</span><span
                                        x-text="(total*rate).toFixed(2)"></span></div>
                            </div>
                        </div>

                        {{-- ===== PAGO: estilo "Recibí..." ===== --}}
                        <div class="mt-4 rounded-2xl border border-gray-200 dark:border-gray-700 p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="font-medium text-gray-900 dark:text-gray-100">Pago</h4>

                                {{-- En Contado puedes elegir modo directo; en Crédito, toggles de inicial --}}
                                <template x-if="saleType==='contado'">
                                    <div
                                        class="inline-flex rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                                        <button type="button" class="px-3 py-1.5 text-sm"
                                            :class="payMode === 'usd' ? 'bg-emerald-600 text-white' :
                                                'bg-white dark:bg-gray-800'"
                                            @click="payMode='usd'">USD</button>
                                        <button type="button" class="px-3 py-1.5 text-sm"
                                            :class="payMode === 'ves' ? 'bg-emerald-600 text-white' :
                                                'bg-white dark:bg-gray-800'"
                                            @click="payMode='ves'">Bs</button>
                                        <button type="button" class="px-3 py-1.5 text-sm"
                                            :class="payMode === 'mix' ? 'bg-emerald-600 text-white' :
                                                'bg-white dark:bg-gray-800'"
                                            @click="payMode='mix'">Mixto</button>
                                    </div>
                                </template>
                                <input type="hidden" name="initial_payment[moneda]" x-ref="ipMoneda">
                                <input type="hidden" name="initial_payment[metodo]" x-ref="ipMetodo">
                                <input type="hidden" name="initial_payment[monto]" x-ref="ipMonto">
                                <input type="hidden" name="initial_payment[referencia]" x-ref="ipRef">
                                <input type="hidden" name="initial_payment[fx_rate]" x-ref="ipFx">
                                <template x-if="saleType==='credito'">
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" class="rounded border-gray-300"
                                            x-model="credit.initialEnabled">
                                        <span>¿Recibiste inicial?</span>
                                    </label>
                                </template>
                            </div>

                            {{-- CONTADO --}}
                            <div x-show="saleType==='contado'">
                                {{-- USD --}}
                                <div x-show="payMode==='usd'" class="grid md:grid-cols-4 gap-2" x-transition>
                                    <select class="h-10 rounded border-gray-300 dark:border-gray-600"
                                        x-model="usd.method">
                                        <option value="efectivo">Efectivo</option>
                                        <option value="zelle">Zelle</option>
                                        <option value="transferencia">Transferencia</option>
                                        <option value="tdc">TDC</option>
                                        <option value="tdd">TDD</option>
                                        <option value="otro">Otro</option>
                                    </select>
                                    <input type="number" step="0.01" min="0"
                                        class="h-10 rounded border-gray-300 dark:border-gray-600"
                                        placeholder="Recibí USD" x-model.number="usd.amount" @input="recalcReceive">
                                    <input type="text" class="h-10 rounded border-gray-300 dark:border-gray-600"
                                        placeholder="Referencia" x-model="usd.ref"
                                        :disabled="usd.method === 'efectivo'">
                                    <div
                                        class="h-10 rounded border border-gray-200 dark:border-gray-700 flex items-center justify-between px-3 text-sm">
                                        <span>Vuelto en</span>
                                        <select class="bg-transparent" x-model="changeCurrency">
                                            <option value="USD">USD</option>
                                            <option value="VES">Bs/PM</option>
                                        </select>
                                    </div>
                                </div>

                                {{-- VES --}}
                                <div x-show="payMode==='ves'" class="grid md:grid-cols-4 gap-2" x-transition>
                                    <select class="h-10 rounded border-gray-300 dark:border-gray-600"
                                        x-model="ves.method">
                                        <option value="efectivo">Efectivo</option>
                                        <option value="pm">Pago móvil</option>
                                        <option value="transferencia">Transferencia</option>
                                        <option value="otro">Otro</option>
                                    </select>
                                    <input type="number" step="0.01" min="0"
                                        class="h-10 rounded border-gray-300 dark:border-gray-600"
                                        placeholder="Recibí Bs" x-model.number="ves.amount" @input="recalcReceive">
                                    <input type="text" class="h-10 rounded border-gray-300 dark:border-gray-600"
                                        placeholder="Referencia" x-model="ves.ref"
                                        :disabled="ves.method === 'efectivo'">
                                    <div
                                        class="h-10 rounded border border-gray-200 dark:border-gray-700 flex items-center justify-between px-3 text-sm">
                                        <span>Tasa</span><span class="font-semibold"
                                            x-text="rate.toFixed(2) + ' Bs/USD'"></span>
                                    </div>
                                </div>

                                {{-- MIXTO --}}
                                <div x-show="payMode==='mix'" class="grid md:grid-cols-2 gap-4" x-transition>
                                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-3">
                                        <div class="text-xs text-gray-500 mb-1">Parte en USD</div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <select class="h-10 rounded border-gray-300 dark:border-gray-600"
                                                x-model="usd.method">
                                                <option value="efectivo">Efectivo</option>
                                                <option value="zelle">Zelle</option>
                                                <option value="transferencia">Transferencia</option>
                                                <option value="otro">Otro</option>
                                            </select>
                                            <input type="number" step="0.01" min="0"
                                                class="h-10 rounded border-gray-300 dark:border-gray-600"
                                                placeholder="Recibí USD" x-model.number="usd.amount"
                                                @input="recalcReceive">
                                            <input type="text"
                                                class="h-10 rounded border-gray-300 dark:border-gray-600 col-span-2"
                                                placeholder="Referencia" x-model="usd.ref"
                                                :disabled="usd.method === 'efectivo'">
                                        </div>
                                    </div>
                                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-3">
                                        <div class="text-xs text-gray-500 mb-1">Parte en Bs</div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <select class="h-10 rounded border-gray-300 dark:border-gray-600"
                                                x-model="ves.method">
                                                <option value="efectivo">Efectivo</option>
                                                <option value="pm">Pago móvil</option>
                                                <option value="transferencia">Transferencia</option>
                                                <option value="otro">Otro</option>
                                            </select>
                                            <input type="number" step="0.01" min="0"
                                                class="h-10 rounded border-gray-300 dark:border-gray-600"
                                                placeholder="Recibí Bs" x-model.number="ves.amount"
                                                @input="recalcReceive">
                                            <input type="text"
                                                class="h-10 rounded border-gray-300 dark:border-gray-600 col-span-2"
                                                placeholder="Referencia" x-model="ves.ref"
                                                :disabled="ves.method === 'efectivo'">
                                        </div>
                                    </div>
                                    <div class="md:col-span-2 flex items-center justify-end gap-2">
                                        <span class="text-sm">Vuelto en</span>
                                        <select class="h-10 rounded border-gray-300 dark:border-gray-600"
                                            x-model="changeCurrency">
                                            <option value="USD">USD</option>
                                            <option value="VES">Bs/PM</option>
                                        </select>
                                    </div>
                                </div>

                                {{-- Resumen --}}
                                <div class="grid md:grid-cols-4 gap-2 mt-3 text-sm">
                                    <div
                                        class="flex justify-between rounded-lg px-3 py-2 bg-gray-50 dark:bg-gray-700/40">
                                        <span>Recibido (USD eq.)</span>
                                        <span class="font-semibold" x-text="receivedUsdEq().toFixed(2)"></span>
                                    </div>
                                    <div
                                        class="flex justify-between rounded-lg px-3 py-2 bg-gray-50 dark:bg-gray-700/40">
                                        <span>Total (USD)</span>
                                        <span class="font-semibold" x-text="total.toFixed(2)"></span>
                                    </div>
                                    <div class="flex justify-between rounded-lg px-3 py-2 bg-gray-50 dark:bg-gray-700/40"
                                        x-show="receivedUsdEq() < total - 0.0001">
                                        <span>Falta</span>
                                        <span class="font-semibold"
                                            x-text="(total - receivedUsdEq()).toFixed(2)"></span>
                                    </div>
                                    <div class="flex justify-between rounded-lg px-3 py-2 bg-gray-50 dark:bg-gray-700/40"
                                        x-show="receivedUsdEq() > total + 0.0001">
                                        <span>Vuelto</span>
                                        <span class="font-semibold"
                                            x-text="formatMoney(changeAmount(), changeCurrency)"></span>
                                    </div>
                                </div>
                            </div>

                            {{-- CRÉDITO (inicial opcional) --}}
                            <div x-show="saleType==='credito'">
                                <div class="grid md:grid-cols-3 gap-2" x-show="credit.initialEnabled" x-transition>
                                    <div
                                        class="inline-flex rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                                        <button type="button" class="px-3 py-1.5 text-sm"
                                            :class="credit.mode === 'usd' ? 'bg-emerald-600 text-white' :
                                                'bg-white dark:bg-gray-800'"
                                            @click="credit.mode='usd'">USD</button>
                                        <button type="button" class="px-3 py-1.5 text-sm"
                                            :class="credit.mode === 'ves' ? 'bg-emerald-600 text-white' :
                                                'bg-white dark:bg-gray-800'"
                                            @click="credit.mode='ves'">Bs</button>
                                        <button type="button" class="px-3 py-1.5 text-sm"
                                            :class="credit.mode === 'mix' ? 'bg-emerald-600 text-white' :
                                                'bg-white dark:bg-gray-800'"
                                            @click="credit.mode='mix'">Mixto</button>
                                    </div>
                                </div>

                                {{-- USD inicial --}}
                                <div class="grid md:grid-cols-4 gap-2 mt-2"
                                    x-show="credit.initialEnabled && credit.mode==='usd'">
                                    <select class="h-10 rounded border-gray-300 dark:border-gray-600"
                                        x-model="credit.usd.method">
                                        <option value="efectivo">Efectivo</option>
                                        <option value="zelle">Zelle</option>
                                        <option value="transferencia">Transferencia</option>
                                        <option value="otro">Otro</option>
                                    </select>
                                    <input type="number" step="0.01" min="0"
                                        class="h-10 rounded border-gray-300 dark:border-gray-600"
                                        placeholder="Inicial USD" x-model.number="credit.usd.amount"
                                        @input="recalcReceive">
                                    <input type="text" class="h-10 rounded border-gray-300 dark:border-gray-600"
                                        placeholder="Referencia" x-model="credit.usd.ref"
                                        :disabled="credit.usd.method === 'efectivo'">
                                </div>

                                {{-- Bs inicial --}}
                                <div class="grid md:grid-cols-4 gap-2 mt-2"
                                    x-show="credit.initialEnabled && credit.mode==='ves'">
                                    <select class="h-10 rounded border-gray-300 dark:border-gray-600"
                                        x-model="credit.ves.method">
                                        <option value="efectivo">Efectivo</option>
                                        <option value="pm">Pago móvil</option>
                                        <option value="transferencia">Transferencia</option>
                                        <option value="otro">Otro</option>
                                    </select>
                                    <input type="number" step="0.01" min="0"
                                        class="h-10 rounded border-gray-300 dark:border-gray-600"
                                        placeholder="Inicial Bs" x-model.number="credit.ves.amount"
                                        @input="recalcReceive">
                                    <input type="text" class="h-10 rounded border-gray-300 dark:border-gray-600"
                                        placeholder="Referencia" x-model="credit.ves.ref"
                                        :disabled="credit.ves.method === 'efectivo'">
                                    <div
                                        class="h-10 rounded border border-gray-200 dark:border-gray-700 flex items-center justify-between px-3 text-sm">
                                        <span>Tasa</span><span class="font-semibold"
                                            x-text="rate.toFixed(2) + ' Bs/USD'"></span>
                                    </div>
                                </div>

                                {{-- Mixto inicial --}}
                                <div class="grid md:grid-cols-2 gap-4 mt-2"
                                    x-show="credit.initialEnabled && credit.mode==='mix'">
                                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-3">
                                        <div class="text-xs text-gray-500 mb-1">Inicial USD</div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <select class="h-10 rounded border-gray-300 dark:border-gray-600"
                                                x-model="credit.usd.method">
                                                <option value="efectivo">Efectivo</option>
                                                <option value="zelle">Zelle</option>
                                                <option value="transferencia">Transferencia</option>
                                                <option value="otro">Otro</option>
                                            </select>
                                            <input type="number" step="0.01" min="0"
                                                class="h-10 rounded border-gray-300 dark:border-gray-600"
                                                placeholder="USD" x-model.number="credit.usd.amount"
                                                @input="recalcReceive">
                                            <input type="text"
                                                class="h-10 rounded border-gray-300 dark:border-gray-600 col-span-2"
                                                placeholder="Referencia" x-model="credit.usd.ref"
                                                :disabled="credit.usd.method === 'efectivo'">
                                        </div>
                                    </div>
                                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-3">
                                        <div class="text-xs text-gray-500 mb-1">Inicial Bs</div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <select class="h-10 rounded border-gray-300 dark:border-gray-600"
                                                x-model="credit.ves.method">
                                                <option value="efectivo">Efectivo</option>
                                                <option value="pm">Pago móvil</option>
                                                <option value="transferencia">Transferencia</option>
                                                <option value="otro">Otro</option>
                                            </select>
                                            <input type="number" step="0.01" min="0"
                                                class="h-10 rounded border-gray-300 dark:border-gray-600"
                                                placeholder="Bs" x-model.number="credit.ves.amount"
                                                @input="recalcReceive">
                                            <input type="text"
                                                class="h-10 rounded border-gray-300 dark:border-gray-600 col-span-2"
                                                placeholder="Referencia" x-model="credit.ves.ref"
                                                :disabled="credit.ves.method === 'efectivo'">
                                        </div>
                                    </div>
                                </div>

                                {{-- Resumen crédito --}}
                                <div class="grid md:grid-cols-3 gap-2 mt-3 text-sm">
                                    <div
                                        class="flex justify-between rounded-lg px-3 py-2 bg-gray-50 dark:bg-gray-700/40">
                                        <span>Inicial (USD eq.)</span>
                                        <span class="font-semibold" x-text="creditReceivedUsdEq().toFixed(2)"></span>
                                    </div>
                                    <div
                                        class="flex justify-between rounded-lg px-3 py-2 bg-gray-50 dark:bg-gray-700/40">
                                        <span>Total (USD)</span>
                                        <span class="font-semibold" x-text="total.toFixed(2)"></span>
                                    </div>
                                    <div
                                        class="flex justify-between rounded-lg px-3 py-2 bg-gray-50 dark:bg-gray-700/40">
                                        <span>Saldo (USD)</span>
                                        <span class="font-semibold"
                                            x-text="(total - creditReceivedUsdEq()).toFixed(2)"></span>
                                    </div>
                                </div>
                            </div>

                            {{-- JSON breakdown para backend --}}
                            <input type="hidden" name="payments_breakdown" :value="buildPaymentsJSON()">
                        </div>

                        <div class="flex justify-end gap-2 pt-5">
                            <button type="button"
                                class="h-10 px-4 rounded-xl border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700"
                                @click="newSaleOpen=false">Cancelar</button>
                            <button :disabled="!canCreateSale()"
                                class="h-10 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white disabled:opacity-50">
                                Crear venta
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ===== MODAL: Abonar (responsive scroll) ===== --}}
        <div x-show="payOpen" class="fixed inset-0 z-50 bg-black/40 overflow-y-auto" x-transition.opacity>
            <div class="mx-auto my-4 sm:my-10 w-full max-w-md px-3">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-6 max-h-[90vh] overflow-y-auto"
                    x-transition.scale.origin.top>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-9 h-9 rounded-2xl bg-emerald-600/10 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-emerald-700"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M3 6h18M5 10h14M7 14h10M9 18h6" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Registrar pago</h3>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">
                        Factura: <span class="font-mono font-semibold" x-text="payCode"></span> —
                        Saldo: USD <span class="font-semibold" x-text="payBalance.toFixed(2)"></span>
                    </p>

                    <form :action="`/payments/${payId}/pay`" method="POST" @submit.prevent="savePay">
                        @csrf
                        <div class="grid grid-cols-2 gap-2">
                            <select name="moneda" class="h-10 rounded border-gray-300 dark:border-gray-600"
                                x-model="payCurrency" @change="recalcAbono">
                                <option value="USD">USD</option>
                                <option value="VES">VES</option>
                            </select>
                            <select name="metodo" class="h-10 rounded border-gray-300 dark:border-gray-600"
                                x-model="payMethod" @change="recalcAbono">
                                <option value="efectivo">Efectivo</option>
                                <option value="zelle">Zelle</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="pm">Pago móvil</option>
                                <option value="tdc">TDC</option>
                                <option value="tdd">TDD</option>
                                <option value="bizum">Bizum</option>
                                <option value="otro">Otro</option>
                            </select>
                            <input type="number" step="0.01" min="0.01" name="monto"
                                class="h-10 rounded border-gray-300 dark:border-gray-600" placeholder="Recibí"
                                x-model.number="payAmount" @input="recalcAbono" required>
                            <input type="text" name="referencia"
                                class="h-10 rounded border-gray-300 dark:border-gray-600" placeholder="Referencia"
                                :disabled="payMethod === 'efectivo'">
                        </div>

                        <template x-if="payCurrency==='VES'">
                            <div class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                Tasa usada: <span class="font-semibold" x-text="rate.toFixed(2)"></span> Bs/USD
                                <input type="hidden" name="fx_rate" :value="rate">
                            </div>
                        </template>

                        <div class="mt-3 text-sm text-gray-700 dark:text-gray-200">
                            Quedará saldo: <span class="font-semibold"
                                x-text="Math.max(0, payBalance - abonoUsdEq()).toFixed(2)"></span> USD
                        </div>

                        <div class="flex justify-end gap-2 pt-4">
                            <button type="button"
                                class="h-10 px-4 rounded-xl border border-gray-300 dark:border-gray-600"
                                @click="payOpen=false">Cancelar</button>
                            <button :disabled="abonoUsdEq() <= 0 || abonoUsdEq() > (payBalance + 0.0001)"
                                class="h-10 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white disabled:opacity-50">
                                Guardar
                            </button>
                        </div>

                        <input type="hidden" name="meta_extra" :value="payMetaJson()">
                    </form>
                </div>
            </div>
        </div>

        {{-- Toolbar --}}
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Ventas</h1>
            <button type="button" @click="openNewSale()"
                class="h-10 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="1.5">
                    <path d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nueva venta
            </button>
        </div>

        {{-- Filtros --}}
        <div class="flex items-center gap-3 mb-3">
            <form method="GET" class="flex items-center gap-2">
                <select name="status" class="h-9 rounded border-gray-300 dark:border-gray-600">
                    <option value="">Todas</option>
                    <option value="pendiente" @selected($status === 'pendiente')>Pendientes</option>
                    <option value="pagado" @selected($status === 'pagado')>Pagadas</option>
                    <option value="anulado" @selected($status === 'anulado')>Anuladas</option>
                </select>
                <button
                    class="h-9 px-3 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200">Filtrar</button>
            </form>
        </div>

        {{-- Tabla --}}
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-700 dark:text-gray-100">
                    <tr>
                        <th class="text-left px-3 py-2">Código</th>
                        <th class="text-left px-3 py-2">Fecha</th>
                        <th class="text-left px-3 py-2">Cliente</th>
                        <th class="text-right px-3 py-2">Subtotal (USD)</th>
                        <th class="text-right px-3 py-2">IVA (USD)</th>
                        <th class="text-right px-3 py-2">Total (USD)</th>
                        <th class="text-right px-3 py-2">Pagado (USD)</th>
                        <th class="text-right px-3 py-2">Saldo (USD)</th>
                        <th class="text-center px-3 py-2">Estado</th>
                        <th class="px-3 py-2">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-gray-800 dark:text-gray-100">
                    @forelse ($items as $v)
                        @php
                            $code =
                                $v['codigo'] ??
                                ($v['numero_factura'] ?? ($v['code'] ?? ($v['num'] ?? ($v['id'] ?? '—'))));
                            $st = $v['status'] ?? 'pendiente';
                        @endphp
                        <tr>
                            <td class="px-3 py-2 font-mono">{{ $code }}</td>
                            <td class="px-3 py-2">{{ $v['fecha'] ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $v['cliente_nombre'] ?? '—' }}</td>
                            <td class="px-3 py-2 text-right">
                                {{ number_format((float) ($v['subtotal_usd'] ?? 0), 2, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right">
                                {{ number_format((float) ($v['iva_usd'] ?? 0), 2, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right font-semibold">
                                {{ number_format((float) ($v['total_usd'] ?? 0), 2, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right">
                                {{ number_format((float) ($v['paid_usd_eq'] ?? 0), 2, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right">
                                {{ number_format((float) ($v['balance_usd'] ?? 0), 2, ',', '.') }}</td>
                            <td class="px-3 py-2 text-center">
                                <span
                                    class="px-2 py-0.5 rounded text-xs
                                    {{ $st === 'pagado' ? 'bg-emerald-100 text-emerald-700' : ($st === 'anulado' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                                    {{ ucfirst($st) }}
                                </span>
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex items-center gap-2">
                                    <button class="h-8 px-2 rounded bg-emerald-600 text-white text-xs"
                                        @click="openPay('{{ $v['id'] }}','{{ $code }}', {{ (float) ($v['balance_usd'] ?? 0) }})">
                                        Abonar
                                    </button>
                                    <form action="{{ route('payments.void', $v['id']) }}" method="POST"
                                        onsubmit="return confirm('¿Anular la venta {{ $code }}?')">
                                        @csrf
                                        <button class="h-8 px-2 rounded bg-red-600 text-white text-xs">Anular</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-3 py-8 text-center text-gray-500">Sin ventas aún.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ===== MODAL: Nuevo cliente ===== --}}
        <div x-show="newClientOpen" class="fixed inset-0 z-50 bg-black/40 overflow-y-auto" x-transition.opacity>
            <div class="mx-auto my-4 sm:my-10 w-full max-w-md px-3">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-6 max-h-[90vh] overflow-y-auto"
                    x-transition.scale.origin.top>
                    <h3 class="text-lg font-semibold mb-3 text-gray-900 dark:text-gray-100">Nuevo cliente</h3>
                    <form @submit.prevent="createClient">
                        <div class="space-y-2">
                            <input type="text"
                                class="w-full h-10 rounded border-gray-300 dark:border-gray-600 px-3"
                                placeholder="Nombre *" x-model="nc.nombre" required>
                            <input type="text"
                                class="w-full h-10 rounded border-gray-300 dark:border-gray-600 px-3"
                                placeholder="RIF/Cédula *" x-model="nc.rif_cedula" required>
                            <input type="text"
                                class="w-full h-10 rounded border-gray-300 dark:border-gray-600 px-3"
                                placeholder="Teléfono" x-model="nc.telefono">
                            <input type="text"
                                class="w-full h-10 rounded border-gray-300 dark:border-gray-600 px-3"
                                placeholder="Dirección" x-model="nc.direccion">
                        </div>
                        <div class="flex justify-end gap-2 mt-4">
                            <button type="button"
                                class="h-10 px-4 rounded-xl border border-gray-300 dark:border-gray-600"
                                @click="newClientOpen=false">Cancelar</button>
                            <button
                                class="h-10 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ===== MODAL: Nuevo producto (sin código/stock) ===== --}}
        <div x-show="newProductOpen" class="fixed inset-0 z-50 bg-black/40 overflow-y-auto" x-transition.opacity>
            <div class="mx-auto my-4 sm:my-10 w-full max-w-xl px-3">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-6 max-h-[90vh] overflow-y-auto"
                    x-transition.scale.origin.top>
                    <h3 class="text-lg font-semibold mb-3 text-gray-900 dark:text-gray-100">Nuevo producto</h3>
                    <form @submit.prevent="createProduct">
                        <div class="grid md:grid-cols-2 gap-3">
                            <input type="text" class="h-10 rounded border-gray-300 dark:border-gray-600 px-3"
                                placeholder="Nombre *" x-model="np.nombre" required>
                            <input type="text" class="h-10 rounded border-gray-300 dark:border-gray-600 px-3"
                                placeholder="Marca" x-model="np.marca">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Categoría *</label>
                                <select class="w-full h-10 rounded border-gray-300 dark:border-gray-600 px-2"
                                    x-model="np.categoria_id" required>
                                    <option value="">—</option>
                                    <template x-for="c in categories" :key="c.id">
                                        <option :value="c.id" x-text="c.nombre"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Presentación *</label>
                                <select class="w-full h-10 rounded border-gray-300 dark:border-gray-600 px-2"
                                    x-model="np.presentacion" required>
                                    <option>Unidad</option>
                                    <option>Caja</option>
                                    <option>Pack</option>
                                    <option>Kg</option>
                                    <option>Lt</option>
                                </select>
                            </div>
                            {{-- código y stock removidos --}}
                            <input type="number" step="0.01" min="0"
                                class="h-10 rounded border-gray-300 dark:border-gray-600 px-3"
                                placeholder="Precio USD base *" x-model.number="np.precio_usd_base" required>
                            <input type="number" step="0.01" min="0"
                                class="h-10 rounded border-gray-300 dark:border-gray-600 px-3"
                                :value="(Number(np.precio_usd_base || 0) * rate).toFixed(2)" disabled
                                title="Calculado con BCV">
                        </div>
                        <p class="text-xs text-gray-500 mt-1">El precio en Bs se calcula con la tasa actual al guardar.
                        </p>
                        <div class="flex justify-end gap-2 mt-4">
                            <button type="button"
                                class="h-10 px-4 rounded-xl border border-gray-300 dark:border-gray-600"
                                @click="newProductOpen=false">Cancelar</button>
                            <button
                                class="h-10 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div> {{-- /x-data --}}

    {{-- ===== Alpine data / lógica (MODO SOPA) ===== --}}
    <script>
        function salesPage({
            rate
        }) {
            return {
                /* ===== State ===== */
                rate: Number(rate || 0),
                ivaP: 0.16,

                newSaleOpen: false,
                successOpen: false,
                successTitle: '',
                successMessage: '',

                // Venta
                saleType: 'contado', // 'contado' | 'credito'
                payMode: 'usd', // 'usd' | 'ves' | 'mix'
                changeCurrency: 'USD', // 'USD' | 'VES'

                // Pago contado
                usd: {
                    method: 'efectivo',
                    amount: 0,
                    ref: ''
                },
                ves: {
                    method: 'pm',
                    amount: 0,
                    ref: ''
                },

                // Crédito (inicial opcional)
                credit: {
                    initialEnabled: false,
                    mode: 'usd', // 'usd' | 'ves' | 'mix'
                    usd: {
                        method: 'efectivo',
                        amount: 0,
                        ref: ''
                    },
                    ves: {
                        method: 'pm',
                        amount: 0,
                        ref: ''
                    },
                },

                // Cliente
                client: null,

                // Productos / totales
                items: [],
                subtotal: 0,
                iva: 0,
                total: 0,

                // Abonar (modal aparte)
                payOpen: false,
                payId: null,
                payCode: null,
                payBalance: 0,
                payCurrency: 'USD',
                payMethod: 'efectivo',
                payAmount: null,

                // Quick create
                newClientOpen: false,
                nc: {
                    nombre: '',
                    rif_cedula: '',
                    telefono: '',
                    direccion: ''
                },
                newProductOpen: false,
                np: {
                    nombre: '',
                    marca: '',
                    categoria_id: '',
                    presentacion: 'Unidad',
                    precio_usd_base: null
                },
                categories: [],

                clientsData: [],
                productsData: [],

                /* ===== UI helpers ===== */
                showSuccess(t, m, reload = false) {
                    this.successTitle = t || 'Éxito';
                    this.successMessage = m || '';
                    this.successOpen = true;
                    if (reload) {
                        setTimeout(() => location.reload(), 900);
                    }
                },
                formatMoney(v, cur) {
                    v = Number(v || 0);
                    const o = {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    };
                    return cur === 'VES' ? v.toLocaleString('es-VE', o) + ' Bs' : 'USD ' + v.toLocaleString('en-US', o);
                },

                /* ===== HTTP ===== */
                getAuthToken() {
                    const m = document.querySelector('meta[name="kalyx-auth"]');
                    return m?.content || '';
                },
                authHeaders() {
                    const t = this.getAuthToken();
                    return t ? {
                        'Authorization': `Bearer ${t}`
                    } : {};
                },
                async fetchJSON(url, {
                    method = 'GET',
                    body = null,
                    headers = {}
                } = {}) {
                    const r = await fetch(url, {
                        method,
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            ...this.authHeaders(),
                            ...headers
                        },
                        body,
                        cache: 'no-store',
                        credentials: 'same-origin'
                    });
                    const txt = await r.text();
                    if (!txt) return null;
                    try {
                        return JSON.parse(txt);
                    } catch {
                        return null;
                    }
                },

                /* ===== Boot ===== */
                parseBootstrap() {
                    const el = document.getElementById('bootstrap-payments-json');
                    const raw = el?.textContent?.trim();
                    if (!raw) return null;
                    try {
                        return JSON.parse(raw);
                    } catch {
                        return null;
                    }
                },
                normalizeClients(arr) {
                    return (Array.isArray(arr) ? arr : []).map(c => ({
                        id: c.id ?? c.uid ?? null,
                        nombre: c.nombre ?? '',
                        rif_cedula: c.rif_cedula ?? '',
                        estado: c.estado ?? 'activo'
                    })).filter(x => !!x.id && (x.estado === 'activo' || !x.estado));
                },
                normalizeProducts(arr) {
                    return (Array.isArray(arr) ? arr : []).map(p => ({
                        id: p.id ?? null,
                        nombre: p.nombre ?? '',
                        codigo: p.codigo ?? p.code ?? p.sku ?? '',
                        marca: p.marca ?? '',
                        precio_usd_base: Number(p.precio_usd_base ?? p.precio_usd ?? 0),
                        activo: (typeof p.activo === 'boolean') ? p.activo : true
                    })).filter(x => !!x.id && x.activo !== false);
                },

                async loadDatasetsOnce() {
                    const boot = this.parseBootstrap();
                    if (boot?.clients) this.clientsData = this.normalizeClients(boot.clients);
                    if (boot?.products) this.productsData = this.normalizeProducts(boot.products);
                    if (boot?.categories) this.categories = Array.isArray(boot.categories) ? boot.categories : [];
                    if (this.clientsData.length === 0) {
                        const q = await this.fetchJSON('/api/clients?limit=1000');
                        const list = Array.isArray(q?.items) ? q.items : (Array.isArray(q) ? q : []);
                        this.clientsData = this.normalizeClients(list);
                    }
                    if (this.productsData.length === 0) {
                        const q = await this.fetchJSON('/api/products?limit=1000');
                        const list = Array.isArray(q?.items) ? q.items : (Array.isArray(q) ? q : []);
                        this.productsData = this.normalizeProducts(list);
                    }
                    if (this.categories.length === 0) {
                        const cats = await this.fetchJSON('/api/products/categories/all');
                        this.categories = Array.isArray(cats) ? cats : [];
                    }
                },

                mountClientSelect2() {
                    const parent = $('#saleModal');
                    const $el = $('#clientSelect');
                    $el.off('select2:select select2:clear').empty();
                    $el.append(new Option('— Selección cliente —', ''));
                    for (const c of this.clientsData) {
                        const text = `${c.nombre}${c.rif_cedula?' — '+c.rif_cedula:''}`;
                        $el.append(new Option(text, c.id));
                    }
                    $el.select2({
                            dropdownParent: parent,
                            allowClear: true,
                            width: '100%'
                        })
                        .on('select2:select', (e) => {
                            const id = e.params.data.id;
                            const c = this.clientsData.find(x => x.id === id) || null;
                            this.client = c;
                            this.$refs.clienteId.value = c?.id || '';
                            this.$refs.clienteNombre.value = c?.nombre || '';
                        })
                        .on('select2:clear', () => {
                            this.client = null;
                            this.$refs.clienteId.value = '';
                            this.$refs.clienteNombre.value = '';
                        });
                },
                mountProductSelect2() {
                    const parent = $('#saleModal');
                    const data = this.productsData.map(p => ({
                        id: p.id,
                        text: `${p.nombre}${p.codigo?' — '+p.codigo:''}`,
                        _search: `${p.nombre} ${p.codigo} ${p.marca}`
                    }));
                    const matcher = (params, data) => {
                        if (!params.term || params.term.trim() === '') return data;
                        const q = (params.term || '').toLowerCase();
                        const t = (data.text || '').toLowerCase();
                        const s = (data._search || '').toLowerCase();
                        return (t.includes(q) || s.includes(q)) ? data : null;
                    };
                    const $el = $('#productSelect');
                    $el.off('select2:select').empty().select2({
                            dropdownParent: parent,
                            placeholder: 'Selecciona producto…',
                            data,
                            matcher,
                            width: '100%'
                        })
                        .on('select2:select', (e) => {
                            const id = e.params.data.id;
                            const p = this.productsData.find(x => x.id === id);
                            this.addItem(p);
                            $el.val(null).trigger('change');
                        });
                },

                async init() {
                    await this.loadDatasetsOnce();
                    this.$nextTick(() => {
                        this.mountClientSelect2();
                        this.mountProductSelect2();
                    });
                    // watchers para mantener sincronizados los hidden del pago
                    this.$watch('usd.amount', () => this.syncInitialFieldsToForm());
                    this.$watch('ves.amount', () => this.syncInitialFieldsToForm());
                    this.$watch('payMode', () => this.syncInitialFieldsToForm());
                    this.$watch('saleType', () => this.syncInitialFieldsToForm());
                    this.$watch('total', () => this.syncInitialFieldsToForm());
                },

                /* ===== Items / totales ===== */
                addItem(p) {
                    if (!p) return;
                    const price = Number(p.precio_usd_base || 0);
                    this.items.push({
                        id: p.id,
                        nombre: p.nombre,
                        codigo: p.codigo || '',
                        precio_usd: price,
                        qty: 1
                    });
                    this.recalc();
                },
                removeItem(i) {
                    if (i < 0 || i >= this.items.length) return;
                    this.items.splice(i, 1);
                    this.recalc();
                },
                recalc() {
                    let sub = 0;
                    for (const it of this.items) {
                        sub += Number(it.precio_usd || 0) * Number(it.qty || 0);
                    }
                    this.subtotal = Number(sub.toFixed(2));
                    this.iva = Number((this.subtotal * this.ivaP).toFixed(2));
                    this.total = Number((this.subtotal + this.iva).toFixed(2));
                    this.syncInitialFieldsToForm();
                },

                /* ===== Pago contado / crédito ===== */
                setSaleType(t) {
                    this.saleType = t;
                    if (t === 'contado') {
                        this.payMode = 'usd';
                    }
                    this.syncInitialFieldsToForm();
                },

                // USD equivalente recibido (solo contado)
                receivedUsdEq() {
                    if (this.saleType !== 'contado') return 0;
                    if (this.payMode === 'usd') return Number(this.usd.amount || 0);
                    if (this.payMode === 'ves') return Number(this.ves.amount || 0) / this.rate;
                    return Number((Number(this.usd.amount || 0) + Number(this.ves.amount || 0) / this.rate).toFixed(2));
                },

                // Vuelto (según moneda elegida para devolver)
                changeAmount() {
                    const over = Math.max(0, this.receivedUsdEq() - this.total);
                    if (this.changeCurrency === 'USD') return Number(over.toFixed(2));
                    return Number((over * this.rate).toFixed(2));
                },

                // Habilitar botón crear
                canCreateSale() {
                    if (!this.$refs?.clienteId?.value) return false;
                    if (this.items.length === 0) return false;
                    if (this.total <= 0) return false;
                    if (this.saleType === 'contado') {
                        const recibido = parseFloat(this.receivedUsdEq().toFixed(2));
                        const total = parseFloat((this.total || 0).toFixed(2));
                        return (recibido + 0.001) >= total;
                    }
                    return true;
                },

                /* === Mapeo a hidden: initial_payment[...] ===
                   - Si 'contado': siempre manda initial_payment con lo aplicado EXACTO al total.
                   - Si 'usd'     : aplica min(usd.amount, total)
                   - Si 'ves'     : aplica min(ves.amount, total*rate) y setea fx_rate
                   - Si 'mix'     : aplica en USD eq (usd + ves/rate) CAPEADO a total, referencia combinada
                   - Si 'credito' : solo si credit.initialEnabled (igual lógica por modo)
                */
                syncInitialFieldsToForm() {
                    // Limpia por defecto
                    const clear = () => {
                        if (this.$refs.ipMoneda) this.$refs.ipMoneda.value = '';
                        if (this.$refs.ipMetodo) this.$refs.ipMetodo.value = '';
                        if (this.$refs.ipMonto) this.$refs.ipMonto.value = '';
                        if (this.$refs.ipRef) this.$refs.ipRef.value = '';
                        if (this.$refs.ipFx) this.$refs.ipFx.value = '';
                    };

                    // Decide si debemos enviar initial_payment
                    let mustSend = false,
                        mode = this.payMode;
                    if (this.saleType === 'contado') mustSend = true;
                    if (this.saleType === 'credito' && this.credit.initialEnabled) {
                        mustSend = true;
                        mode = this.credit.mode;
                    }

                    if (!mustSend) {
                        clear();
                        return;
                    }

                    const total = Number(this.total || 0);
                    if (total <= 0) {
                        clear();
                        return;
                    }

                    let moneda = 'USD',
                        metodo = '',
                        monto = 0,
                        ref = '',
                        fx = 1;

                    if (this.saleType === 'contado') {
                        if (mode === 'usd') {
                            moneda = 'USD';
                            const applyUsd = Math.min(Number(this.usd.amount || 0), total);
                            metodo = this.usd.method || 'efectivo';
                            ref = this.usd.ref || '';
                            monto = Number(applyUsd.toFixed(2));
                            fx = 1;
                        } else if (mode === 'ves') {
                            moneda = 'VES';
                            const maxBs = total * this.rate;
                            const applyBs = Math.min(Number(this.ves.amount || 0), maxBs);
                            metodo = this.ves.method || 'pm';
                            ref = this.ves.ref || '';
                            monto = Number(applyBs.toFixed(2)); // en Bs
                            fx = this.rate;
                        } else { // mix
                            moneda = 'USD'; // enviamos en USD eq para que el backend lo tome directo
                            // aplica exactamente el total en USD eq
                            const usdAmt = Number(this.usd.amount || 0);
                            const vesAmt = Number(this.ves.amount || 0);
                            const usdEq = Math.min(total, usdAmt + (vesAmt / this.rate));
                            monto = Number(usdEq.toFixed(2));
                            metodo = this.usd.method || 'efectivo'; // método principal (referencias combinadas abajo)
                            const parts = [];
                            if (this.usd.ref) parts.push(`USD:${this.usd.ref}`);
                            if (this.ves.ref) parts.push(`VES:${this.ves.ref}`);
                            ref = parts.join(' | ') || '';
                            fx = 1;
                        }
                    } else { // credito + inicial
                        if (mode === 'usd') {
                            moneda = 'USD';
                            const applyUsd = Math.min(Number(this.credit.usd.amount || 0), total);
                            metodo = this.credit.usd.method || 'efectivo';
                            ref = this.credit.usd.ref || '';
                            monto = Number(applyUsd.toFixed(2));
                            fx = 1;
                        } else if (mode === 'ves') {
                            moneda = 'VES';
                            const maxBs = total * this.rate;
                            const applyBs = Math.min(Number(this.credit.ves.amount || 0), maxBs);
                            metodo = this.credit.ves.method || 'pm';
                            ref = this.credit.ves.ref || '';
                            monto = Number(applyBs.toFixed(2));
                            fx = this.rate;
                        } else { // mix
                            moneda = 'USD';
                            const usdAmt = Number(this.credit.usd.amount || 0);
                            const vesAmt = Number(this.credit.ves.amount || 0);
                            const usdEq = Math.min(total, usdAmt + (vesAmt / this.rate));
                            monto = Number(usdEq.toFixed(2));
                            metodo = this.credit.usd.method || 'efectivo';
                            const parts = [];
                            if (this.credit.usd.ref) parts.push(`USD:${this.credit.usd.ref}`);
                            if (this.credit.ves.ref) parts.push(`VES:${this.credit.ves.ref}`);
                            ref = parts.join(' | ') || '';
                            fx = 1;
                        }
                    }

                    // set hidden
                    if (this.$refs.ipMoneda) this.$refs.ipMoneda.value = moneda;
                    if (this.$refs.ipMetodo) this.$refs.ipMetodo.value = metodo;
                    if (this.$refs.ipMonto) this.$refs.ipMonto.value = monto; // USD o Bs, según 'moneda'
                    if (this.$refs.ipRef) this.$refs.ipRef.value = ref;
                    if (this.$refs.ipFx) this.$refs.ipFx.value = fx;
                },

                /* ===== Submit ===== */
                beforeSubmit(e) {
                    // Validaciones base
                    if (!this.$refs.clienteId.value) {
                        e.preventDefault();
                        alert('Selecciona un cliente.');
                        return false;
                    }
                    if (!this.items.length) {
                        e.preventDefault();
                        alert('Agrega al menos un producto.');
                        return false;
                    }

                    // Sincroniza el initial_payment antes de enviar
                    this.syncInitialFieldsToForm();

                    if (this.saleType === 'contado') {
                        const recibido = parseFloat(this.receivedUsdEq().toFixed(2));
                        const total = parseFloat((this.total || 0).toFixed(2));
                        if (recibido + 0.001 < total) {
                            e.preventDefault();
                            alert(`El pago no cubre el total de la venta.\nRecibido: ${recibido}\nTotal: ${total}`);
                            return false;
                        }
                    }
                },

                /* ===== Abonar (modal ya existente) ===== */
                openPay(id, code, balance) {
                    this.payId = id;
                    this.payCode = code;
                    this.payBalance = Number(balance || 0);
                    this.payCurrency = 'USD';
                    this.payMethod = 'efectivo';
                    this.payAmount = null;
                    this.payOpen = true;
                },
                abonoUsdEq() {
                    const m = Number(this.payAmount || 0);
                    return this.payCurrency === 'USD' ? m : (m / this.rate);
                },
                recalcAbono() {},
                payMetaJson() {
                    return JSON.stringify({
                        currency: this.payCurrency,
                        method: this.payMethod,
                        amount: Number(this.payAmount || 0),
                        fx_rate: this.payCurrency === 'VES' ? this.rate : 1
                    });
                },
                async savePay(ev) {
                    const form = ev.target;
                    const fd = new FormData(form);
                    if (this.payCurrency === 'VES' && !fd.get('fx_rate')) fd.append('fx_rate', this.rate);
                    fd.append('meta_extra', this.payMetaJson());
                    try {
                        const r = await fetch(form.getAttribute('action'), {
                            method: 'POST',
                            body: fd,
                            headers: {
                                ...this.authHeaders()
                            },
                            credentials: 'same-origin'
                        });
                        if (r.ok) {
                            this.payOpen = false;
                            this.showSuccess('Pago registrado', 'El abono se guardó correctamente.', true);
                        } else {
                            form.submit();
                        }
                    } catch {
                        form.submit();
                    }
                },

                /* ===== New Sale / Quick create ===== */
                openNewSale() {
                    this.resetPaymentUI();
                    this.newSaleOpen = true;
                },
                resetPaymentUI() {
                    this.saleType = 'contado';
                    this.payMode = 'usd';
                    this.changeCurrency = 'USD';
                    this.usd = {
                        method: 'efectivo',
                        amount: 0,
                        ref: ''
                    };
                    this.ves = {
                        method: 'pm',
                        amount: 0,
                        ref: ''
                    };
                    this.credit = {
                        initialEnabled: false,
                        mode: 'usd',
                        usd: {
                            method: 'efectivo',
                            amount: 0,
                            ref: ''
                        },
                        ves: {
                            method: 'pm',
                            amount: 0,
                            ref: ''
                        }
                    };
                    this.syncInitialFieldsToForm();
                },

                openClientCreate() {
                    this.nc = {
                        nombre: '',
                        rif_cedula: '',
                        telefono: '',
                        direccion: ''
                    };
                    this.newClientOpen = true;
                },
                async createClient() {
                    const p = {
                        nombre: (this.nc.nombre || '').trim(),
                        rif_cedula: (this.nc.rif_cedula || '').trim(),
                        telefono: (this.nc.telefono || '').trim() || null,
                        direccion: (this.nc.direccion || '').trim() || null,
                        estado: 'activo'
                    };
                    if (!p.nombre || !p.rif_cedula) {
                        alert('Nombre y RIF/Cédula son requeridos.');
                        return;
                    }
                    const res = await this.fetchJSON('/api/clients', {
                        method: 'POST',
                        body: JSON.stringify(p)
                    });
                    const id = res?.id;
                    if (!id) {
                        alert('No se pudo crear el cliente.');
                        return;
                    }
                    const nuevo = {
                        id,
                        ...p
                    };
                    this.clientsData.unshift(nuevo);
                    this.mountClientSelect2();
                    const $el = $('#clientSelect');
                    $el.val(id).trigger('change');
                    this.client = nuevo;
                    this.$refs.clienteId.value = id;
                    this.$refs.clienteNombre.value = p.nombre;
                    this.newClientOpen = false;
                    this.showSuccess('Cliente creado', 'El cliente fue registrado correctamente.');
                },

                openProductCreate() {
                    this.np = {
                        nombre: '',
                        marca: '',
                        categoria_id: '',
                        presentacion: 'Unidad',
                        precio_usd_base: null
                    };
                    this.newProductOpen = true;
                },
                async createProduct() {
                    const usd = Number(this.np.precio_usd_base || 0);
                    const bs = Number((usd * this.rate).toFixed(2));
                    const payload = {
                        nombre: (this.np.nombre || '').trim(),
                        marca: (this.np.marca || '').trim() || null,
                        categoria_id: this.np.categoria_id,
                        presentacion: this.np.presentacion,
                        precio_usd_base: usd,
                        precio_bs_base: bs,
                        activo: true
                    };
                    if (!payload.nombre || !payload.categoria_id || !payload.presentacion || !(usd > 0)) {
                        alert('Completa los campos requeridos.');
                        return;
                    }
                    const res = await this.fetchJSON('/api/products', {
                        method: 'POST',
                        body: JSON.stringify(payload)
                    });
                    const id = res?.id;
                    if (!id) {
                        alert('No se pudo crear el producto.');
                        return;
                    }
                    const nuevo = {
                        id,
                        ...payload
                    };
                    this.productsData.unshift(nuevo);
                    this.mountProductSelect2();
                    this.addItem({
                        id,
                        nombre: payload.nombre,
                        codigo: null,
                        precio_usd_base: usd
                    });
                    this.newProductOpen = false;
                    this.showSuccess('Producto creado', 'El producto fue registrado correctamente.');
                },
            }
        }
    </script>


</x-layout>
