@props([
    'park',
    'size' => 'sm',
])

@php
    $background = $park->color ?? '#71717a';
    $textColor = $park->contrastingTextColor();
    $sizeClasses = match ($size) {
        'md' => 'text-sm px-2.5 py-1',
        default => 'text-xs px-2 py-0.5',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full font-semibold leading-tight {$sizeClasses}"]) }}
    style="background-color: {{ $background }}; color: {{ $textColor }};">
    {{ $park->name }}
</span>
