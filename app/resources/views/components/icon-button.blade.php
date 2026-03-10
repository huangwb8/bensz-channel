@props([
    'href' => null,
    'icon',
    'label',
    'title' => null,
    'ariaLabel' => null,
    'variant' => 'default',
    'type' => 'button',
])

@php
    $variantClasses = match ($variant) {
        'primary' => 'icon-action icon-action-primary',
        'danger' => 'icon-action icon-action-danger',
        default => 'icon-action',
    };

    $tooltip = $title ?? $label;
    $accessibleLabel = $ariaLabel ?? $attributes->get('aria-label') ?? $label;
    $buttonAttributes = $attributes->except('aria-label');
@endphp

@if($href)
    <a
        href="{{ $href }}"
        title="{{ $tooltip }}"
        data-tooltip="{{ $tooltip }}"
        aria-label="{{ $accessibleLabel }}"
        {{ $buttonAttributes->class([$variantClasses]) }}
    >
        <x-icon :name="$icon" class="h-5 w-5" />
        <span class="sr-only">{{ $label }}</span>
    </a>
@else
    <button
        type="{{ $type }}"
        title="{{ $tooltip }}"
        data-tooltip="{{ $tooltip }}"
        aria-label="{{ $accessibleLabel }}"
        {{ $buttonAttributes->class([$variantClasses]) }}
    >
        <x-icon :name="$icon" class="h-5 w-5" />
        <span class="sr-only">{{ $label }}</span>
    </button>
@endif
