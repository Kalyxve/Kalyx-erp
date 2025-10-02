<x-layout>
    <h1 class="text-2xl font-semibold mb-4 text-gray-800 dark:text-gray-200">{{ __('Create Product') }}</h1>
    <form action="{{ route('products.store') }}" method="POST" class="space-y-4 bg-white dark:bg-gray-700 p-4 rounded shadow">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Name') }}</label>
            <input type="text" name="name" class="mt-1 block w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" required>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('SKU') }}</label>
            <input type="text" name="sku" class="mt-1 block w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" required>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Price USD') }}</label>
                <input type="number" step="0.01" name="priceUSD" class="mt-1 block w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Price Local') }}</label>
                <input type="number" step="0.01" name="priceLocal" class="mt-1 block w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Stock') }}</label>
                <input type="number" name="stock" class="mt-1 block w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Unit') }}</label>
                <input type="text" name="unit" class="mt-1 block w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
            </div>
        </div>
        <div class="flex items-center">
            <input type="checkbox" name="isActive" id="isActive" class="mr-2" checked>
            <label for="isActive" class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Active') }}</label>
        </div>
        <div class="flex justify-end">
            <button type="submit" class="px-4 py-2 bg-kalyx-primary text-white rounded">{{ __('Save') }}</button>
        </div>
    </form>
</x-layout>