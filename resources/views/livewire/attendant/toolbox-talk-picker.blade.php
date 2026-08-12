<div class="min-h-screen bg-zinc-100 px-4 py-8 dark:bg-zinc-950">
    <div class="mx-auto max-w-lg space-y-6">
        <div class="space-y-2 text-center">
            <div class="text-xs font-bold uppercase tracking-widest text-zinc-400">{{ __('toolbox_talks.picker_eyebrow') }}</div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ __('toolbox_talks.picker_title') }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('toolbox_talks.picker_subtitle') }}</p>
        </div>

        <div class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-5 shadow-xl dark:border-zinc-700 dark:bg-zinc-800">
            <flux:input type="date" wire:model="talkDate" :label="__('toolbox_talks.talk_date')" />

            <p class="text-xs font-bold uppercase tracking-widest text-zinc-400">{{ __('toolbox_talks.choose_park') }}</p>

            <div class="grid gap-3">
                @foreach($parks as $park)
                    <button
                        type="button"
                        wire:click="start({{ $park->id }})"
                        class="group relative h-28 w-full overflow-hidden rounded-2xl text-left shadow-md ring-1 ring-black/10 transition hover:ring-2 hover:ring-teal-400/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-400"
                    >
                        <img
                            src="{{ $parkCovers[$park->id] ?? $defaultCover }}"
                            alt=""
                            class="absolute inset-0 h-full w-full object-cover transition duration-300 group-hover:scale-105"
                        >
                        <div class="absolute inset-0 bg-gradient-to-r from-slate-950/85 via-slate-950/55 to-slate-950/25"></div>
                        <div class="relative flex h-full items-end px-4 py-3">
                            <span class="text-lg font-black tracking-tight text-white drop-shadow">{{ $park->name }}</span>
                        </div>
                    </button>
                @endforeach

                <flux:button
                    type="button"
                    variant="ghost"
                    wire:click="start"
                    class="h-12 w-full rounded-xl text-base font-semibold"
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
