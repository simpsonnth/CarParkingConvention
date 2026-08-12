@props([
    'googleUrl',
    'appleUrl',
    'appearance' => 'button',
])

@php
    $isLink = $appearance === 'link';
@endphp

<div x-data="{ open: false }" {{ $attributes }}>
    <button
        type="button"
        @click="open = true"
        @class([
            'inline-flex items-center justify-center gap-2 font-bold',
            'w-full h-12 rounded-xl bg-indigo-600 px-4 text-base text-white shadow-sm hover:bg-indigo-500' => ! $isLink,
            'gap-1.5 text-sm font-semibold text-indigo-600 hover:underline dark:text-indigo-400' => $isLink,
        ])
    >
        <flux:icon name="map-pin" class="{{ $isLink ? 'size-4' : 'size-5' }}" />
        Find my car
    </button>

    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            class="fixed inset-0 z-[80] flex items-end justify-center sm:items-center p-4"
            role="dialog"
            aria-modal="true"
            aria-label="Choose maps app"
        >
            <div
                x-show="open"
                x-transition.opacity
                class="absolute inset-0 bg-zinc-950/50"
                @click="open = false"
            ></div>

            <div
                x-show="open"
                x-transition
                class="relative w-full max-w-sm rounded-2xl border border-zinc-200 bg-white p-4 shadow-xl dark:border-zinc-700 dark:bg-zinc-900 space-y-3"
            >
                <div class="text-center space-y-1 pb-1">
                    <div class="text-sm font-bold text-zinc-900 dark:text-white">Find my car</div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Open the check-in location in your maps app</p>
                </div>

                <a
                    href="{{ $appleUrl }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex w-full items-center justify-center gap-2 h-12 rounded-xl border border-zinc-200 bg-zinc-50 px-4 text-base font-bold text-zinc-900 hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:hover:bg-zinc-700"
                    @click="open = false"
                >
                    Apple Maps
                </a>

                <a
                    href="{{ $googleUrl }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex w-full items-center justify-center gap-2 h-12 rounded-xl bg-indigo-600 px-4 text-base font-bold text-white shadow-sm hover:bg-indigo-500"
                    @click="open = false"
                >
                    Google Maps
                </a>

                <button
                    type="button"
                    class="inline-flex w-full items-center justify-center h-11 rounded-xl text-sm font-semibold text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200"
                    @click="open = false"
                >
                    Cancel
                </button>
            </div>
        </div>
    </template>
</div>
