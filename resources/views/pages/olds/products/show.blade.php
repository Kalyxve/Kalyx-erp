<x-layout>
    <h1 class="text-2xl font-semibold mb-4 text-gray-800 dark:text-gray-200">{{ __('Product Details') }}</h1>
    <div class="bg-white dark:bg-gray-700 p-4 rounded shadow space-y-2">
        <p><strong>{{ __('Name') }}:</strong> {{ $product['name'] }}</p>
        <p><strong>{{ __('SKU') }}:</strong> {{ $product['sku'] }}</p>
        <p><strong>{{ __('Price USD') }}:</strong> {{ $product['priceUSD'] }}</p>
        <p><strong>{{ __('Price Local') }}:</strong> {{ $product['priceLocal'] ?? '-' }}</p>
        <p><strong>{{ __('Stock') }}:</strong> {{ $product['stock'] ?? '-' }}</p>
        <p><strong>{{ __('Unit') }}:</strong> {{ $product['unit'] ?? '-' }}</p>
        <p><strong>{{ __('Active') }}:</strong> {{ ($product['isActive'] ?? false) ? __('Yes') : __('No') }}</p>
    </div>
    <div class="mt-4 flex space-x-2">
        <a href="{{ route('products.edit', $product['id']) }}" class="px-4 py-2 bg-yellow-500 text-white rounded">{{ __('Edit') }}</a>
        <form action="{{ route('products.destroy', $product['id']) }}" method="POST" onsubmit="return confirm('{{ __('Are you sure?') }}')">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded">{{ __('Delete') }}</button>
        </form>
    </div>
</x-layout>