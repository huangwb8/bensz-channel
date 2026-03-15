@props([
    'user',
    'alt' => null,
])

@php
    $avatar = app(\App\Support\AvatarPresenter::class)->forUser($user);
    $label = $alt ?: ($user->name.' 的头像');
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center justify-center overflow-hidden rounded-full']) }}>
    @if(($avatar['type'] ?? null) === 'image' && filled($avatar['url'] ?? null))
        <img src="{{ $avatar['url'] }}" alt="{{ $label }}" class="h-full w-full object-cover">
    @else
        {!! $avatar['svg'] ?? '' !!}
    @endif
</span>
