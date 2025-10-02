{{-- resources/views/components/field.blade.php --}}
@props(['label'])
<div {{ $attributes->merge(['class' => 'space-y-1']) }}>
    <label class="block text-sm text-gray-600 dark:text-gray-300">{{ $label }}</label>
    {{ $slot }}
</div>
