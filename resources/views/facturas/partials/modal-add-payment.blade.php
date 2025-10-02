<div x-show="addPagoOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" x-transition.opacity>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-xl p-6" x-transition.scale>
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-lg font-semibold">Abonar a factura #<span x-text="pago.factura_id"></span></h3>
            <button @click="addPagoOpen=false" class="h-9 px-3 rounded-md border dark:border-gray-600">Cerrar</button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <input type="text" x-model="pago.metodo" class="h-10 px-3 rounded-md border dark:border-gray-600"
                placeholder="Método *">
            <input type="text" x-model="pago.referencia" class="h-10 px-3 rounded-md border dark:border-gray-600"
                placeholder="Referencia">
            <input type="number" step="0.01" x-model.number="pago.monto_usd"
                class="h-10 px-3 rounded-md border dark:border-gray-600" placeholder="Monto USD">
            <input type="number" step="0.01" x-model.number="pago.monto_bs"
                class="h-10 px-3 rounded-md border dark:border-gray-600" placeholder="Monto Bs">
            <input type="number" step="0.0001" x-model.number="pago.tasa_usd"
                class="h-10 px-3 rounded-md border dark:border-gray-600" placeholder="Tasa pago (BCV)">
            <input type="date" x-model="pago.fecha_pago" class="h-10 px-3 rounded-md border dark:border-gray-600">
            <textarea x-model="pago.nota" rows="2" class="md:col-span-2 px-3 py-2 rounded-md border dark:border-gray-600"
                placeholder="Nota"></textarea>
        </div>

        <div class="mt-4 flex justify-end gap-2">
            <button @click="submitAddPago"
                class="px-4 py-2 rounded-md bg-emerald-600 hover:bg-emerald-700 text-white">Guardar pago</button>
        </div>
    </div>
</div>
