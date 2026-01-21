{{--
    Auth Logo Component

    Usage: <x-auth.logo />

    Displays the application logo with status badge
--}}

@props([
    'showAppName' => true,
    'size' => 'default' // 'small', 'default', 'large'
])

@php
    $sizes = [
        'small' => [
            'container' => 'w-10 h-10',
            'text' => 'text-base',
            'badge' => 'w-3 h-3',
            'badgeIcon' => 'w-2 h-2',
            'appName' => 'text-xl'
        ],
        'default' => [
            'container' => 'w-12 h-12',
            'text' => 'text-lg',
            'badge' => 'w-4 h-4',
            'badgeIcon' => 'w-2.5 h-2.5',
            'appName' => 'text-2xl'
        ],
        'large' => [
            'container' => 'w-16 h-16',
            'text' => 'text-2xl',
            'badge' => 'w-5 h-5',
            'badgeIcon' => 'w-3 h-3',
            'appName' => 'text-3xl'
        ],
    ];
    $s = $sizes[$size] ?? $sizes['default'];
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex items-center space-x-3']) }}>
    <div class="relative">
        <div class="{{ $s['container'] }} flex items-center justify-center">
            <img src="{{ asset('icon.png') }}" alt="{{ config('app.name', 'EasyVente') }}" class="w-full h-full object-contain rounded-xl">
        </div>
    </div>
    @if($showAppName)
        <span class="{{ $s['appName'] }} font-bold text-white">{{ config('app.name', 'EasyVente') }}</span>
    @endif
</div>
