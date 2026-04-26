@php
    $m = $metrics;
    $congUrl = fn (?string $uuid): ?string => $uuid ? route('admin.congregations.show', $uuid) : null;
    // Opaque surfaces only — translucent /80 on dark caused light-on-washed panels next to body bg.
    $card = 'rounded-2xl border border-zinc-200 bg-white text-zinc-900 shadow-sm dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100 dark:shadow-none';
    $panel = 'rounded-2xl border border-zinc-200 bg-zinc-50 text-zinc-900 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100';
    // Tables: always dark zinc (no bg-white / bg-zinc-100) so Flux cannot leave white header strips in .dark UI.
    $tableShell = 'overflow-hidden rounded-2xl border border-zinc-600 bg-zinc-900 text-zinc-100';
    $tableHead = 'border-b border-zinc-700 bg-zinc-950 px-4 py-3 text-zinc-300';
    $tableColHeadRow = 'border-b border-zinc-700 bg-zinc-950 text-[11px] font-semibold uppercase tracking-wide text-zinc-400';
    $tableBody = 'divide-y divide-zinc-800 bg-zinc-900';
    $rowHover = 'transition-colors hover:bg-zinc-800/90';
    $tableCellMuted = 'text-sm tabular-nums text-zinc-300';
    $tableLink = 'font-semibold text-indigo-300 underline-offset-2 hover:text-indigo-200 hover:underline';
    $sectionTitle = 'text-base font-semibold tracking-tight text-zinc-800 dark:text-zinc-100';
    $kpi = 'text-3xl font-bold tabular-nums text-zinc-950 dark:text-white sm:text-4xl';
    $kpiMd = 'text-2xl font-bold tabular-nums text-zinc-950 dark:text-white';
    $label = 'text-xs font-medium text-zinc-600 dark:text-zinc-400';
    $labelCaps = 'text-[11px] font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-400';
    $labelIndigo = 'text-[11px] font-semibold uppercase tracking-wide text-indigo-700 dark:text-indigo-300';
    $labelAmber = 'text-[11px] font-semibold uppercase tracking-wide text-amber-800 dark:text-amber-300';
    $labelViolet = 'text-[11px] font-semibold uppercase tracking-wide text-violet-700 dark:text-violet-300';
    $name = 'text-base font-semibold text-zinc-900 dark:text-zinc-50';
    $muted = 'text-xs leading-relaxed text-zinc-600 dark:text-zinc-400';
    $link = 'font-semibold text-indigo-700 underline-offset-2 hover:text-indigo-900 hover:underline dark:text-indigo-300 dark:hover:text-indigo-200';
    $tableTitle = 'text-sm font-semibold text-zinc-100';
@endphp

<div class="reports-metrics mx-auto max-w-6xl space-y-14 pb-10">
    <header class="flex flex-col gap-6 border-b border-zinc-200 pb-8 dark:border-zinc-600 lg:flex-row lg:items-end lg:justify-between">
        <div class="min-w-0 space-y-2">
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white sm:text-3xl">{{ __('reports.page_title') }}</h1>
            <p class="max-w-2xl text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">{{ __('reports.page_subtitle') }}</p>
        </div>
        <div class="flex shrink-0 flex-wrap gap-2">
            <flux:button :href="route('admin.congregation-numbers')" variant="outline" icon="table-cells" wire:navigate size="sm">
                {{ __('reports.link_congregation_numbers') }}
            </flux:button>
            <flux:button :href="route('admin.circuit-overseer-parking')" variant="outline" icon="identification" wire:navigate size="sm">
                {{ __('reports.link_circuit_overseer') }}
            </flux:button>
        </div>
    </header>

    {{-- Survey completion --}}
    <section class="space-y-3" aria-labelledby="reports-completion-heading">
        <h2 id="reports-completion-heading" class="{{ $sectionTitle }}">{{ __('reports.section_submission_title') }}</h2>
        <div class="{{ $panel }} p-6 sm:p-8">
            <div class="flex flex-col gap-8 lg:flex-row lg:items-center lg:gap-12">
                <div class="shrink-0">
                    <p class="text-4xl font-bold tabular-nums tracking-tight text-zinc-950 dark:text-white sm:text-5xl">
                        {{ number_format($m['submission_rate_percent'], 0) }}<span class="text-2xl font-semibold text-zinc-500 dark:text-zinc-400">%</span>
                    </p>
                    <p class="mt-2 text-sm text-zinc-700 dark:text-zinc-300">
                        <span class="font-semibold tabular-nums text-zinc-900 dark:text-white">{{ number_format($m['congregations_submitted']) }}</span>
                        <span class="text-zinc-500 dark:text-zinc-500"> / </span>
                        <span class="tabular-nums text-zinc-700 dark:text-zinc-300">{{ number_format($m['congregations_total']) }}</span>
                        <span class="text-zinc-600 dark:text-zinc-400"> {{ __('reports.completion_congregations_suffix') }}</span>
                    </p>
                    <p class="mt-2 text-sm font-medium text-amber-800 dark:text-amber-300">
                        {{ __('reports.submission_missing_line', ['count' => number_format($m['congregations_missing'])]) }}
                    </p>
                </div>
                <div class="min-w-0 flex-1 space-y-3">
                    <div class="flex justify-between text-xs font-medium text-zinc-600 dark:text-zinc-400">
                        <span>{{ __('reports.submission_rate_label') }}</span>
                        <span class="tabular-nums text-zinc-900 dark:text-zinc-200">{{ number_format($m['submission_rate_percent'], 1) }}%</span>
                    </div>
                    <div class="h-4 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-800">
                        <div
                            class="h-full rounded-full bg-indigo-600 dark:bg-indigo-500"
                            style="width: {{ min(100, $m['submission_rate_percent']) }}%"
                            role="progressbar"
                            aria-valuenow="{{ (int) round($m['submission_rate_percent']) }}"
                            aria-valuemin="0"
                            aria-valuemax="100"
                        ></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Totals + highest single requests: tighter vertical grouping --}}
    <div class="space-y-6">
    <section class="space-y-4" aria-labelledby="reports-totals-heading">
        <h2 id="reports-totals-heading" class="{{ $sectionTitle }}">{{ __('reports.section_totals_title') }}</h2>

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="{{ $card }} p-6 sm:p-7">
                <p class="{{ $labelCaps }}">{{ __('reports.stat_combined_car_park_tickets') }}</p>
                <p class="mt-2 {{ $kpi }}">{{ number_format($m['combined_total_car_park_tickets']) }}</p>
                <div class="mt-6 flex flex-col gap-4 border-t border-zinc-200 pt-6 dark:border-zinc-700 sm:flex-row sm:divide-x sm:divide-zinc-200 sm:dark:divide-zinc-700">
                    <div class="min-w-0 flex-1 sm:pe-6">
                        <p class="{{ $label }}">{{ __('reports.stat_breakdown_congregations') }}</p>
                        <p class="mt-1 text-xl font-semibold tabular-nums text-zinc-950 dark:text-white">{{ number_format($m['total_car_park_tickets']) }}</p>
                    </div>
                    <div class="min-w-0 flex-1 sm:ps-6">
                        <p class="{{ $label }}">{{ __('reports.stat_breakdown_co') }}</p>
                        <p class="mt-1 text-xl font-semibold tabular-nums text-zinc-950 dark:text-white">{{ number_format($m['co_total_car_park_tickets']) }}</p>
                    </div>
                </div>
                <p class="mt-5 border-t border-zinc-200 pt-4 {{ $muted }} dark:border-zinc-700">{{ __('reports.stat_avg_tickets_hint', ['avg' => number_format($m['average_car_park_tickets_per_response'], 1)]) }}</p>
            </div>

            <div class="{{ $card }} p-6 sm:p-7">
                <p class="{{ $labelCaps }}">{{ __('reports.stat_combined_disabled_demand') }}</p>
                <p class="mt-2 {{ $kpi }}">{{ number_format($m['combined_disabled_demand']) }}</p>
                <div class="mt-6 flex flex-col gap-4 border-t border-zinc-200 pt-6 dark:border-zinc-700 sm:flex-row sm:divide-x sm:divide-zinc-200 sm:dark:divide-zinc-700">
                    <div class="min-w-0 flex-1 sm:pe-6">
                        <p class="{{ $label }}">{{ __('reports.stat_breakdown_cong_disabled_spaces') }}</p>
                        <p class="mt-1 text-xl font-semibold tabular-nums text-zinc-950 dark:text-white">{{ number_format($m['total_disabled_spaces']) }}</p>
                    </div>
                    <div class="min-w-0 flex-1 sm:ps-6">
                        <p class="{{ $label }}">{{ __('reports.stat_breakdown_co_disabled_people') }}</p>
                        <p class="mt-1 text-xl font-semibold tabular-nums text-zinc-950 dark:text-white">{{ number_format($m['co_disabled_required_count']) }}</p>
                    </div>
                </div>
                <p class="mt-5 border-t border-zinc-200 pt-4 {{ $muted }} dark:border-zinc-700">{{ __('reports.stat_combined_disabled_footnote') }}</p>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="{{ $card }} p-5 sm:p-6">
                <p class="{{ $labelCaps }}">{{ __('reports.stat_responses_count') }}</p>
                <p class="mt-2 {{ $kpiMd }}">{{ number_format($m['responses_count']) }}</p>
                <p class="mt-3 {{ $muted }}">{{ __('reports.stat_responses_hint') }}</p>
            </div>
            <div class="{{ $card }} p-5 sm:p-6">
                <p class="{{ $labelCaps }}">{{ __('reports.stat_co_entries') }}</p>
                <p class="mt-2 {{ $kpiMd }}">{{ number_format($m['co_row_count']) }}</p>
                <p class="mt-3 {{ $muted }}">{{ __('reports.stat_co_entries_hint') }}</p>
            </div>
            <div class="{{ $card }} p-5 sm:p-6">
                <p class="{{ $labelCaps }}">{{ __('reports.section_coach_title') }}</p>
                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div>
                        <p class="{{ $label }}">{{ __('reports.stat_coach_organizers_raw_short') }}</p>
                        <p class="mt-1 {{ $kpiMd }}">{{ number_format($m['coach_organizers_raw']) }}</p>
                    </div>
                    <div>
                        <p class="{{ $label }}">{{ __('reports.stat_coach_organizers_deduped_short') }}</p>
                        <p class="mt-1 {{ $kpiMd }}">{{ number_format($m['coach_organizers_deduped_components']) }}</p>
                    </div>
                </div>
                <p class="mt-4 border-t border-zinc-200 pt-3 {{ $muted }} dark:border-zinc-700">{{ __('reports.section_coach_help') }}</p>
            </div>
        </div>
    </section>

    @if ($m['highlight_max_tickets'] || $m['highlight_max_disabled'] || $m['highlight_co_max_tickets'])
        <section class="space-y-3" aria-labelledby="reports-highlights-heading">
            <h2 id="reports-highlights-heading" class="{{ $sectionTitle }}">{{ __('reports.section_highlights_title') }}</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @if ($m['highlight_max_tickets'])
                    @php $h = $m['highlight_max_tickets']; $url = $congUrl($h['congregation_uuid']); @endphp
                    <article class="{{ $card }} flex min-h-[168px] flex-col p-6">
                        <p class="{{ $labelIndigo }}">{{ __('reports.highlight_most_tickets_label') }}</p>
                        <p class="mt-5 {{ $kpi }}">{{ number_format($h['tickets']) }}</p>
                        <p class="mt-3 {{ $name }} leading-snug">
                            @if ($url)
                                <a href="{{ $url }}" wire:navigate class="{{ $link }}">{{ $h['name'] }}</a>
                            @else
                                {{ $h['name'] }}
                            @endif
                        </p>
                        <p class="mt-auto pt-4 {{ $muted }}">{{ __('reports.highlight_congregation_source') }}</p>
                    </article>
                @endif
                @if ($m['highlight_max_disabled'])
                    @php $h = $m['highlight_max_disabled']; $url = $congUrl($h['congregation_uuid']); @endphp
                    <article class="{{ $card }} flex min-h-[168px] flex-col p-6">
                        <p class="{{ $labelAmber }}">{{ __('reports.highlight_most_disabled_label') }}</p>
                        <p class="mt-5 {{ $kpi }}">{{ number_format($h['spaces']) }}</p>
                        <p class="mt-3 {{ $name }} leading-snug">
                            @if ($url)
                                <a href="{{ $url }}" wire:navigate class="{{ $link }}">{{ $h['name'] }}</a>
                            @else
                                {{ $h['name'] }}
                            @endif
                        </p>
                        <p class="mt-auto pt-4 {{ $muted }}">{{ __('reports.highlight_congregation_source') }}</p>
                    </article>
                @endif
                @if ($m['highlight_co_max_tickets'])
                    @php $h = $m['highlight_co_max_tickets']; @endphp
                    <article class="{{ $card }} flex min-h-[168px] flex-col p-6">
                        <p class="{{ $labelViolet }}">{{ __('reports.highlight_co_most_tickets_label') }}</p>
                        <p class="mt-5 {{ $kpi }}">{{ number_format($h['tickets']) }}</p>
                        <p class="mt-3 {{ $name }}">{{ $h['name'] }}</p>
                        <p class="mt-auto pt-4 {{ $muted }}">{{ __('reports.highlight_co_source_hint') }}</p>
                    </article>
                @endif
            </div>
        </section>
    @endif
    </div>

    <section class="space-y-3" aria-labelledby="reports-rankings-heading">
        <h2 id="reports-rankings-heading" class="{{ $sectionTitle }}">{{ __('reports.section_rankings_congregations_title') }}</h2>
        <div class="grid gap-4 lg:grid-cols-2">
            <div class="{{ $tableShell }}">
                <div class="{{ $tableHead }}">
                    <h3 class="{{ $tableTitle }}">{{ __('reports.section_top_tickets_title') }}</h3>
                </div>
                <table class="w-full text-left text-sm">
                    <thead class="sr-only">
                        <tr>
                            <th>{{ __('reports.table_col_congregation') }}</th>
                            <th>{{ __('reports.table_col_tickets') }}</th>
                        </tr>
                    </thead>
                    <tbody class="{{ $tableBody }}">
                        @forelse ($m['top_by_car_park_tickets'] as $row)
                            @php $url = $congUrl($row['congregation_uuid']); @endphp
                            <tr class="{{ $rowHover }}">
                                <td class="px-4 py-3.5 text-sm font-semibold text-zinc-100">
                                    @if ($url)
                                        <a href="{{ $url }}" wire:navigate class="{{ $tableLink }}">{{ $row['name'] }}</a>
                                    @else
                                        {{ $row['name'] }}
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-end {{ $tableCellMuted }}">{{ number_format($row['tickets']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-4 py-10 text-center text-sm text-zinc-400">{{ __('reports.table_empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="{{ $tableShell }}">
                <div class="{{ $tableHead }}">
                    <h3 class="{{ $tableTitle }}">{{ __('reports.section_top_disabled_title') }}</h3>
                </div>
                <table class="w-full text-left text-sm">
                    <thead class="sr-only">
                        <tr>
                            <th>{{ __('reports.table_col_congregation') }}</th>
                            <th>{{ __('reports.table_col_spaces') }}</th>
                        </tr>
                    </thead>
                    <tbody class="{{ $tableBody }}">
                        @forelse ($m['top_by_disabled_spaces'] as $row)
                            @php $url = $congUrl($row['congregation_uuid']); @endphp
                            <tr class="{{ $rowHover }}">
                                <td class="px-4 py-3.5 text-sm font-semibold text-zinc-100">
                                    @if ($url)
                                        <a href="{{ $url }}" wire:navigate class="{{ $tableLink }}">{{ $row['name'] }}</a>
                                    @else
                                        {{ $row['name'] }}
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-end {{ $tableCellMuted }}">{{ number_format($row['spaces']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-4 py-10 text-center text-sm text-zinc-400">{{ __('reports.table_empty_disabled') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="space-y-3" aria-labelledby="reports-co-heading">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 id="reports-co-heading" class="{{ $sectionTitle }}">{{ __('reports.section_co_block_title') }}</h2>
            <flux:link :href="route('admin.circuit-overseer-parking')" wire:navigate class="shrink-0 text-sm font-medium text-indigo-700 dark:text-indigo-300">
                {{ __('reports.link_manage_co') }} →
            </flux:link>
        </div>
        <div @class(['grid gap-4', 'lg:grid-cols-2' => count($m['co_recent']) > 0])>
            <div class="{{ $tableShell }}">
                <div class="{{ $tableHead }}">
                    <h3 class="{{ $tableTitle }}">{{ __('reports.section_co_top_title') }}</h3>
                </div>
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="{{ $tableColHeadRow }}">
                            <th class="px-4 py-2.5 text-start font-semibold text-zinc-400">{{ __('reports.table_col_co_name') }}</th>
                            <th class="px-4 py-2.5 text-end font-semibold text-zinc-400">{{ __('reports.table_col_tickets') }}</th>
                            <th class="px-4 py-2.5 text-end font-semibold text-zinc-400">{{ __('reports.table_col_disabled_flag') }}</th>
                        </tr>
                    </thead>
                    <tbody class="{{ $tableBody }}">
                        @forelse ($m['co_top_by_car_park_tickets'] as $row)
                            <tr class="{{ $rowHover }}">
                                <td class="px-4 py-3.5 text-sm font-semibold text-zinc-100">{{ $row['name'] }}</td>
                                <td class="px-4 py-3.5 text-end {{ $tableCellMuted }}">{{ number_format($row['tickets']) }}</td>
                                <td class="px-4 py-3.5 text-end">
                                    @if ($row['disabled_required'])
                                        <flux:badge color="amber" size="sm">{{ __('reports.co_yes') }}</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="sm">{{ __('reports.co_no') }}</flux:badge>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-10 text-center text-sm text-zinc-400">{{ __('reports.table_co_empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if (count($m['co_recent']) > 0)
                <div class="{{ $tableShell }}">
                    <div class="{{ $tableHead }}">
                        <h3 class="{{ $tableTitle }}">{{ __('reports.section_co_recent_title') }}</h3>
                    </div>
                    <ul class="{{ $tableBody }}">
                        @foreach ($m['co_recent'] as $row)
                            <li class="{{ $rowHover }} flex flex-col gap-1 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between">
                                <span class="text-sm font-semibold text-zinc-100">{{ $row['name'] }}</span>
                                <time class="text-xs tabular-nums text-zinc-400" @if ($row['updated_at'] !== '') datetime="{{ $row['updated_at'] }}" @endif>
                                    {{ $row['updated_at_display'] }}
                                </time>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </section>

    @if (count($m['recent_submissions']) > 0)
        <section class="space-y-3" aria-labelledby="reports-recent-cong-heading">
            <h2 id="reports-recent-cong-heading" class="{{ $sectionTitle }}">{{ __('reports.section_recent_title') }}</h2>
            <div class="{{ $tableShell }}">
                <ul class="{{ $tableBody }}">
                    @foreach ($m['recent_submissions'] as $row)
                        @php $url = $congUrl($row['congregation_uuid']); @endphp
                        <li class="{{ $rowHover }} flex flex-col gap-1 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between">
                            <span class="text-sm font-semibold text-zinc-100">
                                @if ($url)
                                    <a href="{{ $url }}" wire:navigate class="{{ $tableLink }}">{{ $row['name'] }}</a>
                                @else
                                    {{ $row['name'] }}
                                @endif
                            </span>
                            <time class="text-xs tabular-nums text-zinc-400" @if ($row['updated_at'] !== '') datetime="{{ $row['updated_at'] }}" @endif>
                                {{ $row['updated_at_display'] }}
                            </time>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif
</div>
