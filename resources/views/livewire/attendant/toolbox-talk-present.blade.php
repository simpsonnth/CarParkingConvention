<div
    class="fixed inset-0 z-50 flex h-[100dvh] flex-col overflow-hidden bg-slate-950 text-white select-none"
    x-data="{
        chromeVisible: true,
        hideTimer: null,
        bumpChrome() {
            this.chromeVisible = true;
            clearTimeout(this.hideTimer);
            this.hideTimer = setTimeout(() => { this.chromeVisible = false }, 2800);
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
    {{-- Full-bleed hero --}}
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <img
            src="{{ asset('images/guest-handout-hero.png') }}"
            alt=""
            class="h-full w-full object-cover"
        >
        <div class="absolute inset-0 bg-gradient-to-b from-slate-950/75 via-slate-950/70 to-slate-950/90"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-teal-950/40 via-transparent to-indigo-950/35"></div>
    </div>

    {{-- Top bar --}}
    <div
        class="relative z-30 flex shrink-0 items-center justify-between gap-3 px-3 py-2.5 transition-opacity duration-300 sm:px-5 sm:py-3"
        x-bind:class="chromeVisible ? 'opacity-100' : 'opacity-0 pointer-events-none'"
    >
        <div class="min-w-0">
            <div class="truncate text-[10px] font-bold uppercase tracking-[0.18em] text-teal-100/85 sm:text-[11px]">
                {{ __('toolbox_talks.present_eyebrow', ['date' => $talkDate]) }}
                @if($parkName)
                    · {{ $parkName }}
                @endif
            </div>
            @if($slide)
                <div class="mt-1 inline-flex rounded-full border border-white/20 bg-black/25 px-2.5 py-0.5 text-[10px] font-semibold text-teal-50 backdrop-blur sm:text-xs">
                    {{ $slide['section_label'] }}
                </div>
            @endif
        </div>
        <div class="flex shrink-0 items-center gap-2">
            @if($total > 0)
                <span class="rounded-lg bg-black/35 px-2.5 py-1 font-mono text-xs text-zinc-100 backdrop-blur sm:text-sm">
                    {{ $index + 1 }} / {{ $total }}
                </span>
            @endif
            <a
                href="{{ route('attendant.scan') }}"
                class="rounded-lg bg-white/15 px-2.5 py-1.5 text-xs font-semibold backdrop-blur hover:bg-white/25 sm:px-3 sm:py-2 sm:text-sm"
            >
                {{ __('toolbox_talks.back_to_scan') }}
            </a>
        </div>
    </div>

    {{-- Slide content: fills remaining viewport --}}
    <div class="relative z-10 flex min-h-0 flex-1 flex-col">
        @if($slide === null)
            <div class="flex flex-1 items-center justify-center px-5 text-center">
                <div class="max-w-md space-y-3 rounded-2xl border border-white/10 bg-black/40 p-6 backdrop-blur">
                    <h2 class="text-2xl font-bold">{{ __('toolbox_talks.present_empty_title') }}</h2>
                    <p class="text-zinc-300">{{ __('toolbox_talks.present_empty_body') }}</p>
                    <a href="{{ route('attendant.scan') }}" class="inline-flex rounded-xl bg-white px-4 py-3 text-sm font-bold text-slate-900">
                        {{ __('toolbox_talks.back_to_scan') }}
                    </a>
                </div>
            </div>
        @else
            <button
                type="button"
                wire:click="previous"
                class="absolute inset-y-0 left-0 z-20 w-[15%] opacity-0"
                aria-label="{{ __('toolbox_talks.previous') }}"
            ></button>
            <button
                type="button"
                wire:click="next"
                class="absolute inset-y-0 right-0 z-20 w-[85%] opacity-0"
                aria-label="{{ __('toolbox_talks.next') }}"
            ></button>

            <div
                wire:key="slide-{{ $index }}"
                class="relative flex min-h-0 flex-1 flex-col px-4 pb-2 pt-1 sm:px-8 sm:pb-3 sm:pt-2 lg:px-14"
            >
                <div class="flex min-h-0 flex-1 flex-col justify-center gap-3 overflow-y-auto overscroll-contain sm:gap-5">
                    <div class="inline-flex w-fit items-center gap-2 rounded-full border border-teal-300/35 bg-teal-400/15 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-teal-50 sm:text-[11px]">
                        <span class="size-1.5 rounded-full bg-teal-300"></span>
                        {{ $slide['section_label'] }}
                    </div>

                    <h1 class="text-[clamp(1.5rem,5.5vw,3.75rem)] font-black leading-[1.12] tracking-tight text-white drop-shadow-lg">
                        {{ $slide['title'] }}
                    </h1>

                    @if($slide['body'] !== '')
                        <div class="space-y-2.5 text-[clamp(0.95rem,2.8vw,1.65rem)] leading-snug text-slate-50/95 sm:space-y-3 sm:leading-relaxed">
                            @foreach(preg_split("/\R/u", $slide['body']) ?: [] as $line)
                                @php $line = trim($line); @endphp
                                @continue($line === '')
                                @if(str_starts_with($line, '•') || str_starts_with($line, '-'))
                                    <div class="flex gap-2.5 sm:gap-3">
                                        <span class="mt-[0.55em] size-2 shrink-0 rounded-full bg-teal-300 shadow-[0_0_10px_rgba(45,212,191,0.75)]"></span>
                                        <p>{{ ltrim($line, "•-\t ") }}</p>
                                    </div>
                                @else
                                    <p>{{ $line }}</p>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>

                @if($total > 0)
                    <div class="mt-2 h-1.5 w-full shrink-0 overflow-hidden rounded-full bg-white/15">
                        <div
                            class="h-full rounded-full bg-gradient-to-r from-teal-400 to-indigo-400 transition-all duration-300"
                            style="width: {{ round((($index + 1) / max($total, 1)) * 100, 2) }}%"
                        ></div>
                    </div>
                @endif
            </div>
        @endif
    </div>

    @if($total > 0)
        <div
            class="relative z-30 flex shrink-0 items-center justify-between gap-2 px-3 py-3 transition-opacity duration-300 sm:gap-3 sm:px-5 sm:py-4"
            x-bind:class="chromeVisible ? 'opacity-100' : 'opacity-0 pointer-events-none'"
        >
            <button
                type="button"
                wire:click="previous"
                @disabled($index <= 0)
                class="h-12 min-w-[5.5rem] rounded-xl border border-white/20 bg-black/35 px-4 text-sm font-bold backdrop-blur enabled:hover:bg-white/15 disabled:opacity-35 sm:h-14 sm:min-w-[7rem] sm:text-base"
            >
                {{ __('toolbox_talks.previous') }}
            </button>

            <div class="hidden max-w-[45%] flex-wrap items-center justify-center gap-1.5 sm:flex">
                @for($i = 0; $i < $total; $i++)
                    <button
                        type="button"
                        wire:click="goTo({{ $i }})"
                        @class([
                            'h-2.5 rounded-full transition-all',
                            'w-7 bg-teal-300' => $i === $index,
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
                class="h-12 min-w-[5.5rem] rounded-xl bg-teal-500 px-4 text-sm font-bold text-slate-950 enabled:hover:bg-teal-400 disabled:opacity-35 sm:h-14 sm:min-w-[7rem] sm:text-base"
            >
                {{ __('toolbox_talks.next') }}
            </button>
        </div>
    @endif
</div>
