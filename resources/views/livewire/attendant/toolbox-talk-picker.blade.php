<div class="min-h-screen bg-zinc-100 px-4 py-8 dark:bg-zinc-950">
    <div class="mx-auto max-w-lg space-y-6">
        <div class="text-center space-y-2">
            <div class="text-xs font-bold uppercase tracking-widest text-zinc-400">{{ __('toolbox_talks.picker_eyebrow') }}</div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ __('toolbox_talks.picker_title') }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('toolbox_talks.picker_subtitle') }}</p>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-xl dark:border-zinc-700 dark:bg-zinc-800 space-y-4">
            <flux:input type="date" wire:model="talkDate" :label="__('toolbox_talks.talk_date')" />

            <p class="text-xs font-bold uppercase tracking-widest text-zinc-400">{{ __('toolbox_talks.choose_park') }}</p>

            <div class="space-y-2">
                @foreach($parks as $park)
                    <flux:button
                        type="button"
                        variant="filled"
                        wire:click="start({{ $park->id }})"
                        class="w-full h-14 text-base font-bold rounded-xl"
                    >
                        {{ $park->name }}
                    </flux:button>
                @endforeach

                <flux:button
                    type="button"
                    variant="ghost"
                    wire:click="start"
                    class="w-full h-12 text-base font-semibold rounded-xl"
                >
                    {{ __('toolbox_talks.core_only') }}
                </flux:button>
            </div>
        </div>

        <div class="text-center">
            <a href="{{ route('attendant.scan') }}"
                class="inline-flex w-full items-center justify-center rounded-xl border border-zinc-200 bg-white px-4 py-3.5 text-base font-bold text-zinc-800 shadow-sm hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700">
                ← {{ __('toolbox_talks.back_to_scan') }}
            </a>
        </div>
    </div>
</div>
