<div
    class="fixed inset-0 z-50 flex flex-col bg-zinc-950 text-white"
    x-data
    x-on:keydown.window.right="$wire.next()"
    x-on:keydown.window.left="$wire.previous()"
    x-on:keydown.window.space.prevent="$wire.next()"
>
    <div class="flex items-center justify-between gap-3 border-b border-white/10 px-4 py-3">
        <div class="min-w-0">
            <div class="truncate text-xs font-bold uppercase tracking-widest text-zinc-400">
                {{ __('toolbox_talks.present_eyebrow', ['date' => $talkDate]) }}
                @if($parkName)
                    · {{ $parkName }}
                @endif
            </div>
            @if($slide)
                <div class="mt-1 inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-zinc-200">
                    {{ $slide['section_label'] }}
                </div>
            @endif
        </div>
        <div class="flex shrink-0 items-center gap-2">
            @if($total > 0)
                <span class="text-sm font-mono text-zinc-300">{{ $index + 1 }} / {{ $total }}</span>
            @endif
            <a
                href="{{ route('attendant.scan') }}"
                class="rounded-lg bg-white/10 px-3 py-2 text-sm font-semibold hover:bg-white/20"
            >
                {{ __('toolbox_talks.back_to_scan') }}
            </a>
        </div>
    </div>

    <div class="relative flex flex-1 flex-col overflow-hidden">
        @if($slide === null)
            <div class="flex flex-1 items-center justify-center px-6 text-center">
                <div class="max-w-md space-y-3">
                    <h2 class="text-2xl font-bold">{{ __('toolbox_talks.present_empty_title') }}</h2>
                    <p class="text-zinc-400">{{ __('toolbox_talks.present_empty_body') }}</p>
                    <a href="{{ route('attendant.scan') }}" class="inline-flex rounded-xl bg-white px-4 py-3 text-sm font-bold text-zinc-900">
                        {{ __('toolbox_talks.back_to_scan') }}
                    </a>
                </div>
            </div>
        @else
            <button
                type="button"
                wire:click="previous"
                class="absolute inset-y-0 left-0 z-10 w-1/4 opacity-0"
                aria-label="{{ __('toolbox_talks.previous') }}"
            ></button>
            <button
                type="button"
                wire:click="next"
                class="absolute inset-y-0 right-0 z-10 w-3/4 opacity-0"
                aria-label="{{ __('toolbox_talks.next') }}"
            ></button>

            <div class="relative z-0 flex flex-1 flex-col justify-center gap-6 overflow-y-auto px-6 py-10 sm:px-12">
                <h1 class="text-3xl font-bold leading-tight sm:text-5xl">{{ $slide['title'] }}</h1>
                @if($slide['body'] !== '')
                    <div class="max-w-4xl whitespace-pre-line text-lg leading-relaxed text-zinc-200 sm:text-2xl">
                        {{ $slide['body'] }}
                    </div>
                @endif
            </div>
        @endif
    </div>

    @if($total > 0)
        <div class="flex items-center justify-between gap-3 border-t border-white/10 px-4 py-4">
            <flux:button
                type="button"
                wire:click="previous"
                :disabled="$index <= 0"
                class="h-14 min-w-[7rem] rounded-xl text-base font-bold"
            >
                {{ __('toolbox_talks.previous') }}
            </flux:button>
            <flux:button
                type="button"
                wire:click="next"
                variant="primary"
                :disabled="$index >= $total - 1"
                class="h-14 min-w-[7rem] rounded-xl text-base font-bold"
            >
                {{ __('toolbox_talks.next') }}
            </flux:button>
        </div>
    @endif
</div>
