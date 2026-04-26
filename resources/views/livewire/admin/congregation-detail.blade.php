@php
    $card = 'rounded-2xl border border-zinc-200 bg-white p-5 text-zinc-900 shadow-sm dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100 dark:shadow-none sm:p-6';
    $labelCaps = 'text-[11px] font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-400';
    $kpi = 'mt-2 text-2xl font-bold tabular-nums text-zinc-950 dark:text-white sm:text-3xl';
    $muted = 'text-xs leading-relaxed text-zinc-600 dark:text-zinc-400';
@endphp

<div class="mx-auto max-w-6xl space-y-8 pb-10">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <div class="mb-2">
                <a href="{{ route('admin.congregations') }}"
                    class="flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300"
                    wire:navigate>
                    <flux:icon name="arrow-left" class="size-3" />
                    {{ __('congregation_detail.back') }}
                </a>
            </div>
            <flux:heading size="xl">{{ $congregation->name }}</flux:heading>
            <flux:subheading>
                {{ __('congregation_detail.assigned') }}
                {{ $congregation->carPark?->name ?? __('congregation_detail.unassigned') }}
            </flux:subheading>
        </div>
    </div>

    <div class="space-y-3">
        <flux:heading size="lg">{{ __('congregation_detail.section_survey_title') }}</flux:heading>
        @if ($survey['has_response'])
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="{{ $card }}">
                    <p class="{{ $labelCaps }}">{{ __('congregation_detail.stat_tickets_requested') }}</p>
                    <p class="{{ $kpi }}">{{ number_format($survey['expected_tickets']) }}</p>
                </div>
                <div class="{{ $card }}">
                    <p class="{{ $labelCaps }}">{{ __('congregation_detail.stat_disabled_requested') }}</p>
                    @if ($survey['disabled_required'])
                        <p class="{{ $kpi }}">{{ number_format($survey['disabled_requested']) }}</p>
                    @else
                        <p class="{{ $kpi }} text-zinc-400 dark:text-zinc-500">—</p>
                        <p class="mt-2 {{ $muted }}">{{ __('congregation_detail.stat_disabled_not_applicable') }}</p>
                    @endif
                </div>
            </div>
        @else
            <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-5 py-6 text-sm text-zinc-600 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-400">
                {{ __('congregation_detail.survey_no_response') }}
            </div>
        @endif
    </div>

    <div class="space-y-3">
        <flux:heading size="lg">{{ __('congregation_detail.section_parking_title') }}</flux:heading>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="{{ $card }}">
                <p class="{{ $labelCaps }}">{{ __('congregation_detail.stat_parked_now') }}</p>
                <p class="{{ $kpi }}">{{ number_format($parking['parked_count']) }}</p>
            </div>
            <div class="{{ $card }}">
                <p class="{{ $labelCaps }}">{{ __('congregation_detail.stat_expected') }}</p>
                <p class="{{ $kpi }}">{{ number_format($parking['expected_tickets']) }}</p>
            </div>
            <div class="{{ $card }}">
                <p class="{{ $labelCaps }}">{{ __('congregation_detail.stat_remaining') }}</p>
                <p class="{{ $kpi }}">{{ number_format($parking['remaining_vs_survey']) }}</p>
                @if ($parking['expected_tickets'] > 0)
                    <p class="mt-2 {{ $muted }}">{{ number_format($parking['parked_count']) }} / {{ number_format($parking['expected_tickets']) }}</p>
                @endif
            </div>
            <div class="{{ $card }}">
                <p class="{{ $labelCaps }}">{{ __('congregation_detail.stat_disabled_parked') }}</p>
                <p class="{{ $kpi }}">{{ number_format($parking['disabled_parked_count']) }}</p>
            </div>
        </div>
        @if ($parking['expected_tickets'] > 0)
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-600 dark:bg-zinc-900 sm:p-6">
                <div class="flex justify-between text-xs font-medium text-zinc-600 dark:text-zinc-400">
                    <span>{{ __('congregation_detail.progress_label') }}</span>
                    <span class="tabular-nums text-zinc-900 dark:text-zinc-200">{{ number_format($parking['check_in_percent'], 1) }}%</span>
                </div>
                <div class="mt-3 h-3 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-800">
                    <div
                        class="h-full rounded-full bg-indigo-600 dark:bg-indigo-500"
                        style="width: {{ min(100, $parking['check_in_percent']) }}%"
                        role="progressbar"
                        aria-valuenow="{{ (int) round($parking['check_in_percent']) }}"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    ></div>
                </div>
            </div>
        @endif
    </div>

    <div class="space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <flux:heading size="lg">{{ __('congregation_detail.active_vehicles') }}</flux:heading>
            <flux:button wire:click="checkoutAll" variant="danger" icon="arrow-right-start-on-rectangle"
                wire:confirm="{{ __('congregation_detail.checkout_all_confirm') }}"
                class="w-full sm:w-auto">
                {{ __('congregation_detail.checkout_all') }}
            </flux:button>
        </div>

        <div class="-mx-4 overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700 sm:mx-0">
            <table class="w-full min-w-[640px] text-left text-sm text-zinc-500 dark:text-zinc-400">
                <thead class="bg-zinc-50 text-xs uppercase text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
                    <tr>
                        <th class="px-6 py-3">{{ __('congregation_detail.table_reg') }}</th>
                        <th class="px-6 py-3">{{ __('congregation_detail.table_contact') }}</th>
                        <th class="px-6 py-3">{{ __('congregation_detail.table_time_in') }}</th>
                        <th class="px-6 py-3">{{ __('congregation_detail.table_attendant') }}</th>
                        <th class="px-6 py-3 text-end">{{ __('congregation_detail.table_actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                    @forelse ($cars as $pass)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                            <td class="px-6 py-4 font-mono font-medium text-zinc-900 dark:text-white">
                                {{ $pass->vehicle_reg ?? '—' }}
                            </td>
                            <td class="px-6 py-4 font-mono text-sm">
                                {{ $pass->contact_number ?? '—' }}
                            </td>
                            <td class="px-6 py-4">
                                @if ($pass->scanned_at)
                                    {{ $pass->scanned_at->format('H:i') }}
                                    <span class="ml-1 text-xs text-zinc-400">({{ $pass->scanned_at->diffForHumans() }})</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                {{ $pass->scannedBy->name ?? __('congregation_detail.system') }}
                            </td>
                            <td class="px-6 py-4 text-end">
                                <flux:button wire:click="checkout({{ $pass->id }})" size="sm" variant="danger"
                                    icon="arrow-right-start-on-rectangle">{{ __('congregation_detail.checkout') }}</flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-zinc-500">
                                {{ __('congregation_detail.empty_vehicles') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{ $cars->links() }}
        </div>
    </div>
</div>
