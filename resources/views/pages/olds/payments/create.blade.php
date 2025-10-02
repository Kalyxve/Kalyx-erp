<x-layout>
    <h1 class="text-2xl font-semibold mb-4 text-gray-800 dark:text-gray-200">{{ __('Create Payment') }}</h1>
    <form action="{{ route('payments.store') }}" method="POST" class="space-y-4 bg-white dark:bg-gray-700 p-4 rounded shadow">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Client</label>
            <select name="clientId" class="mt-1 block w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" required>
                <option value="">Select client</option>
                @foreach ($clients as $client)
                    <option value="{{ $client['id'] }}">{{ $client['name'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Amount</label>
                <input type="number" name="amount" step="0.01" class="mt-1 block w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Currency</label>
                <input type="text" name="currency" value="USD" class="mt-1 block w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" required>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Method</label>
                <input type="text" name="method" class="mt-1 block w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date</label>
                <input type="date" name="date" value="{{ date('Y-m-d') }}" class="mt-1 block w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" required>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
            <textarea name="notes" class="mt-1 block w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" rows="3"></textarea>
        </div>
        <div class="flex justify-end">
            <button type="submit" class="px-4 py-2 bg-kalyx-primary text-white rounded">Save</button>
        </div>
    </form>
</x-layout>