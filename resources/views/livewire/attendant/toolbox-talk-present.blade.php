<div
    class="fixed inset-0 z-50 flex flex-col bg-slate-950 text-white select-none"
    x-data="{
        chromeVisible: true,
        hideTimer: null,
        bumpChrome() {
            this.chromeVisible = true;
            clearTimeout(this.hideTimer);
            this.hideTimer = setTimeout(() => { this.chromeVisible = false }, 2500);
        }
    }"
    x-init="bumpChrome()"
    x-on:mousemove.window="bumpChrome()"
    x-on:touchstart.window="bumpChrome()"
    x-on:keydown.window.right="$wire.next()"
    x-on:keydown.window.left="$wire.previous()"
    x-on:keydown.window.space.prevent="$wire.next()"
    x-on:keydown.window.escape="window.location.href = @js(route('attendant.scan'))"
>
    {{-- Ambient hero backdrop --}}
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <img
            src="{{ asset('images/guest-handout-hero.png') }}"
            alt=""
            class="h-full w-full scale-105 object-cover opacity-40"
        >
        <div class="absolute inset-0 bg-gradient-to-br from-slate-950/92 via-teal-950/80 to-indigo-950/85"></div>
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(45,212,191,0.18),transparent_55%)]"></div>
    </div>

    {{-- Top chrome --}}
    <div
        class="relative z-20 flex items-center justify-between gap-3 px-4 py-3 transition-opacity duration-300 sm:px-6"
        x-bind:class="chromeVisible ? 'opacity-100' : 'opacity-0'"
    >
        <div class="min-w-0">
            <div class="truncate text-[11px] font-bold uppercase tracking-[0.2em] text-teal-200/80">
                {{ __('toolbox_talks.present_eyebrow', ['date' => $talkDate]) }}
                @if($parkName)
                    · {{ $parkName }}
                @endif
            </div>
            @if($slide)
                <div class="mt-1 inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold text-teal-50 backdrop-blur">
                    {{ $slide['section_label'] }}
                </div>
            @endif
        </div>
        <div class="flex shrink-0 items-center gap-2">
            @if($total > 0)
                <span class="rounded-lg bg-black/30 px-3 py-1.5 font-mono text-sm text-zinc-100 backdrop-blur">
                    {{ $index + 1 }} / {{ $total }}
                </span>
            @endif
            <a
                href="{{ route('attendant.scan') }}"
                class="rounded-lg bg-white/10 px-3 py-2 text-sm font-semibold backdrop-blur hover:bg-white/20"
            >
                {{ __('toolbox_talks.back_to_scan') }}
            </a>
        </div>
    </div>

    <div class="relative z-10 flex flex-1 items-center justify-center overflow-hidden px-3 pb-3 pt-1 sm:px-8 sm:pb-6">
        @if($slide === null)
            <div class="max-w-lg rounded-3xl border border-white/10 bg-black/35 p-8 text-center shadow-2xl backdrop-blur-md">
                <h2 class="text-2xl font-bold">{{ __('toolbox_talks.present_empty_title') }}</h2>
                <p class="mt-3 text-zinc-300">{{ __('toolbox_talks.present_empty_body') }}</p>
                <a href="{{ route('attendant.scan') }}" class="mt-6 inline-flex rounded-xl bg-white px-4 py-3 text-sm font-bold text-slate-900">
                    {{ __('toolbox_talks.back_to_scan') }}
                </a>
            </div>
        @else
            <button
                type="button"
                wire:click="previous"
                class="absolute inset-y-0 left-0 z-20 w-[18%] cursor-w-resize opacity-0"
                aria-label="{{ __('toolbox_talks.previous') }}"
            ></button>
            <button
                type="button"
                wire:click="next"
                class="absolute inset-y-0 right-0 z-20 w-[82%] cursor-e-resize opacity-0"
                aria-label="{{ __('toolbox_talks.next') }}"
            ></button>

            {{-- 16:9 presentation stage --}}
            <div
                wire:key="slide-{{ $index }}"
                class="relative flex aspect-video w-full max-w-6xl flex-col overflow-hidden rounded-2xl border border-white/15 shadow-[0_25px_80px_rgba(0,0,0,0.55)] ring-1 ring-teal-300/20"
            >
                <img
                    src="{{ asset('images/guest-handout-hero.png') }}"
                    alt=""
                    class="absolute inset-0 h-full w-full object-cover"
                >
                <div class="absolute inset-0 bg-gradient-to-r from-slate-950/90 via-slate-950/75 to-slate-950/35"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-slate-950/30"></div>

                <div class="relative z-10 flex h-full flex-col justify-center gap-5 p-6 sm:gap-7 sm:p-12 lg:p-16">
                    <div class="inline-flex w-fit items-center gap-2 rounded-full border border-teal-300/30 bg-teal-400/10 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.18em] text-teal-100">
                        <span class="size-1.5 rounded-full bg-teal-300"></span>
                        {{ $slide['section_label'] }}
                    </div>

                    <h1 class="max-w-4xl text-3xl font-black leading-[1.1] tracking-tight text-white drop-shadow-lg sm:text-5xl lg:text-6xl">
                        {{ $slide['title'] }}
                    </h1>

                    @if($slide['body'] !== '')
                        <div class="max-w-3xl space-y-3 text-base leading-relaxed text-slate-100/95 sm:text-xl lg:text-2xl">
                            @foreach(preg_split("/\R/u", $slide['body']) ?: [] as $line)
                                @php $line = trim($line); @endphp
                                @continue($line === '')
                                @if(str_starts_with($line, '•') || str_starts_with($line, '-'))
                                    <div class="flex gap-3">
                                        <span class="mt-2 size-2 shrink-0 rounded-full bg-teal-300 shadow-[0_0_12px_rgba(45,212,191,0.8)]"></span>
                                        <p>{{ ltrim($line, "•-\t ") }}</p>
                                    </div>
                                @else
                                    <p class="text-slate-100/90">{{ $line }}</p>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Progress bar --}}
                @if($total > 0)
                    <div class="absolute inset-x-0 bottom-0 z-10 h-1.5 bg-white/10">
                        <div
                            class="h-full bg-gradient-to-r from-teal-400 to-indigo-400 transition-all duration-300"
                            style="width: {{ round((($index + 1) / max($total, 1)) * 100, 2) }}%"
                        ></div>
                    </div>
                @endif
            </div>
        @endif
    </div>

    @if($total > 0)
        <div
            class="relative z-20 flex items-center justify-between gap-3 px-4 py-4 transition-opacity duration-300 sm:px-8"
            x-bind:class="chromeVisible ? 'opacity-100' : 'opacity-0'"
        >
            <button
                type="button"
                wire:click="previous"
                @disabled($index <= 0)
                class="h-14 min-w-[7.5rem] rounded-xl border border-white/15 bg-white/10 px-5 text-base font-bold backdrop-blur enabled:hover:bg-white/20 disabled:opacity-40"
            >
                {{ __('toolbox_talks.previous') }}
            </button>

            <div class="hidden items-center gap-1.5 sm:flex">
                @for($i = 0; $i < $total; $i++)
                    <button
                        type="button"
                        wire:click="goTo({{ $i }})"
                        @class([
                            'h-2.5 rounded-full transition-all',
                            'w-8 bg-teal-300' => $i === $index,
                            'w-2.5 bg-white/30 hover:bg-white/50' => $i !== $index,
                        ])
                        aria-label="{{ __('toolbox_talks.slide_n', ['n' => $i + 1]) }}"
                    ></button>
                @endfor
            </div>

            <button
                type="button"
                wire:click="next"
                @disabled($index >= $total - 1)
                class="h-14 min-w-[7.5rem] rounded-xl bg-teal-500 px-5 text-base font-bold text-slate-950 enabled:hover:bg-teal-400 disabled:opacity-40"
            >
                {{ __('toolbox_talks.next') }}
            </button>
        </div>
    @endif
</div>
