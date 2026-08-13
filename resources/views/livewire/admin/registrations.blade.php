<div class="space-y-6">
    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-200" role="alert">
            {{ session('error') }}
        </div>
    @endif
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('registrations.title') }}</flux:heading>
            <flux:subheading>{{ __('registrations.subtitle') }}</flux:subheading>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <flux:button variant="primary" wire:click="create" icon="plus">
                {{ __('registrations.add_registration') }}
            </flux:button>
            @can('registrations.manage')
                <flux:button variant="filled" wire:click="openBroadcastModal" icon="envelope">
                    {{ __('registrations.email_guests') }}
                </flux:button>
            @endcan
            <a href="{{ $this->exportUrl }}" class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700" download>
                <flux:icon name="arrow-down-tray" class="size-4" />
                {{ ($this->getAppliedFiltersCount() > 0 || $search !== '') ? __('registrations.export_excel_filtered') : __('registrations.export_excel') }}
            </a>
            <a href="{{ route('admin.registrations.trash') }}" wire:navigate class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700">
                <flux:icon name="trash" class="size-4" />
                {{ __('registrations.recycle_bin') }}
            </a>
            @if(count($selectedIds) > 0)
                <span class="text-sm font-medium text-amber-700 dark:text-amber-300">{{ count($selectedIds) }} {{ __('registrations.selected') }}</span>
                <button type="button" wire:click="$set('selectedIds', [])"
                    class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700">
                    {{ __('registrations.cancel') }}
                </button>
            @else
                <button type="button" disabled class="inline-flex cursor-not-allowed items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm font-medium text-zinc-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-500">
                    <flux:icon name="trash" class="size-4" />
                    {{ __('registrations.delete_selected') }}
                </button>
            @endif
            <button type="button" wire:click="openFilterPanel"
                class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700">
                <flux:icon name="funnel" class="size-4" />
                {{ __('registrations.filters') }}
                @if($this->getAppliedFiltersCount() > 0)
                    <span class="inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-200">{{ $this->getAppliedFiltersCount() }}</span>
                @endif
            </button>
            <select wire:model.live="perPage"
                class="block w-full sm:w-auto min-w-0 rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                <option value="25">25 {{ __('registrations.per_page') }}</option>
                <option value="50">50 {{ __('registrations.per_page') }}</option>
                <option value="100">100 {{ __('registrations.per_page') }}</option>
            </select>
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('registrations.search') }}" class="w-full min-w-0 sm:min-w-[180px]" />
        </div>
    </div>

    @php
        $rr = $registrationReportSummary;
    @endphp
    <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900/50 dark:text-zinc-300">
        <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('registrations.vs_expected_title') }}</p>
        <p class="mt-2 flex flex-wrap items-baseline gap-x-4 gap-y-1">
            <span><span class="font-semibold tabular-nums text-zinc-900 dark:text-white">{{ number_format($rr['registration_total']) }}</span> {{ __('registrations.vs_expected_completed') }}</span>
            <span class="text-zinc-400">/</span>
            <span><span class="tabular-nums font-semibold text-zinc-900 dark:text-white">{{ number_format($rr['expected_total']) }}</span> {{ __('registrations.vs_expected_from_report') }}</span>
        </p>
        <p class="mt-2 text-zinc-600 dark:text-zinc-400">
            {{ __('registrations.vs_expected_difference') }}:
            <span class="tabular-nums font-semibold {{ $rr['difference'] > 0 ? 'text-amber-800 dark:text-amber-300' : ($rr['difference'] < 0 ? 'text-emerald-800 dark:text-emerald-300' : 'text-zinc-800 dark:text-zinc-200') }}">{{ number_format($rr['difference']) }}</span>
        </p>
        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('registrations.vs_expected_difference_hint') }}</p>
        @if ($rr['expected_total'] > 0)
            <div class="mt-3 space-y-2">
                <div class="flex justify-between text-xs font-medium text-zinc-600 dark:text-zinc-400">
                    <span>{{ __('registrations.vs_expected_progress') }}</span>
                    <span class="tabular-nums text-zinc-900 dark:text-zinc-200">{{ number_format($rr['completion_percent'], 1) }}%</span>
                </div>
                <div class="h-3 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-800">
                    <div
                        class="h-full rounded-full bg-indigo-600 dark:bg-indigo-500"
                        style="width: {{ min(100, $rr['completion_percent']) }}%"
                        role="progressbar"
                        aria-valuenow="{{ (int) round($rr['completion_percent']) }}"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    ></div>
                </div>
            </div>
        @endif
        <p class="mt-3">
            <flux:link :href="route('admin.reports')" wire:navigate class="text-sm font-semibold">{{ __('registrations.vs_expected_view_report') }}</flux:link>
        </p>
    </div>

    @php $abd = $attendanceByDay; @endphp
    <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900/50 dark:text-zinc-300">
        <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('registrations.attendance_by_day_embed_title') }}</p>
        <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">{{ __('registrations.attendance_by_day_embed_source_note') }}</p>
        <p class="mt-2 text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ __('registrations.attendance_by_day_sum_rule') }}</p>
        <ul class="mt-3 list-none space-y-2 p-0">
            @foreach ($abd['ordered_days'] as $day)
                @php
                    $dayLabel = __('registrations.attendance_day_'.$day);
                    $total = (int) ($abd['counts_by_day'][$day] ?? 0);
                    $cars = (int) ($abd['cars_by_day'][$day] ?? 0);
                    $coaches = (int) ($abd['coaches_by_day'][$day] ?? 0);
                    $co = (int) ($abd['circuit_overseers_by_day'][$day] ?? 0);
                    $ei = (int) ($abd['disabled_by_day'][$day] ?? 0);
                @endphp
                <li class="text-zinc-800 dark:text-zinc-200">
                    <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $dayLabel }}</span>
                    <span class="mx-1 text-zinc-400">—</span>
                    <span class="tabular-nums">{{ __('registrations.attendance_by_day_embed_vehicle_line', ['total' => number_format($total), 'cars' => number_format($cars), 'coaches' => number_format($coaches)]) }}</span>
                    <span class="text-zinc-400">·</span>
                    <span class="tabular-nums">{{ __('registrations.attendance_by_day_embed_overlap_line', ['co' => number_format($co), 'ei' => number_format($ei)]) }}</span>
                </li>
            @endforeach
        </ul>
        @if ($abd['missing_days_count'] > 0)
            <p class="mt-2 text-xs font-medium text-amber-800 dark:text-amber-300">
                {{ __('registrations.attendance_by_day_missing_days', ['count' => number_format($abd['missing_days_count'])]) }}
            </p>
        @endif
        <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('registrations.attendance_by_day_overlap_note') }}</p>
        <p class="mt-3">
            <flux:link :href="route('admin.registrations.attendance-by-day')" wire:navigate class="text-sm font-semibold">{{ __('registrations.attendance_by_day_full_report_link') }}</flux:link>
        </p>
    </div>

    <div class="rounded-xl border border-red-200 bg-red-50/90 px-4 py-4 dark:border-red-900/60 dark:bg-red-950/40">
        <p class="font-semibold text-red-900 dark:text-red-100">{{ __('registrations.bulk_delete_danger_title') }}</p>
        <p class="mt-1 text-sm text-red-800 dark:text-red-200/95">{{ __('registrations.bulk_delete_danger_hint') }}</p>
        <div class="mt-4 flex flex-wrap gap-3">
            <button type="button" wire:click="bulkDeleteStandardCarRegistrations"
                wire:confirm="{{ __('registrations.bulk_delete_standard_cars_confirm') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-red-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-800 dark:bg-red-600 dark:hover:bg-red-500">
                <flux:icon name="trash" class="size-4" />
                {{ __('registrations.bulk_delete_standard_cars_button') }}
            </button>
            <button type="button" wire:click="bulkDeleteDisabledRegistrations"
                wire:confirm="{{ __('registrations.bulk_delete_disabled_confirm') }}"
                class="inline-flex items-center gap-2 rounded-lg border border-red-300 bg-white px-3 py-2 text-sm font-semibold text-red-800 shadow-sm hover:bg-red-100 dark:border-red-800 dark:bg-red-900/50 dark:text-red-100 dark:hover:bg-red-900/70">
                <flux:icon name="trash" class="size-4" />
                {{ __('registrations.bulk_delete_disabled_button') }}
            </button>
        </div>
    </div>

    @if($this->getAppliedFiltersCount() > 0)
        <div class="flex flex-wrap items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800/50">
            <button type="button" wire:click="openFilterPanel" class="inline-flex items-center gap-1.5 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:text-indigo-600 dark:hover:text-indigo-400">
                <flux:icon name="chevron-down" class="size-4 transition-transform" />
                {{ __('registrations.applied_filters', ['count' => $this->getAppliedFiltersCount()]) }}
            </button>
            <button type="button" wire:click="clearFilters" class="text-xs font-medium text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                {{ __('registrations.clear_filters') }}
            </button>
        </div>
    @endif

    @if(count($selectedIds) > 0)
        <div class="rounded-lg border-2 border-amber-400 bg-amber-50 px-4 py-3 dark:border-amber-600 dark:bg-amber-900/30" role="region" aria-label="Bulk actions">
            <p class="text-sm font-semibold text-amber-900 dark:text-amber-100 mb-3">{{ count($selectedIds) }} {{ __('registrations.selected') }} — bulk actions</p>
            <div class="flex flex-wrap items-center gap-3">
                <span class="text-xs font-medium text-amber-800 dark:text-amber-200 uppercase tracking-wide">Elderly &amp; Infirm:</span>
                <button type="button" wire:click="bulkSetElderlyInfirm('1')"
                    class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-amber-700">
                    {{ __('registrations.yes') }}
                </button>
                <button type="button" wire:click="bulkSetElderlyInfirm('0')"
                    class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600">
                    {{ __('registrations.no') }}
                </button>
                <span class="text-zinc-300 dark:text-zinc-600 mx-1">|</span>
                <span class="text-xs font-medium text-amber-800 dark:text-amber-200 uppercase tracking-wide">{{ __('registrations.bulk_assign_congregation_car_park') }}:</span>
                <select wire:model="bulkAssignCarParkId"
                    class="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200">
                    <option value="">{{ __('registrations.car_park') }}...</option>
                    @foreach($carParks ?? [] as $park)
                        <option value="{{ $park->id }}">{{ $park->name }}</option>
                    @endforeach
                </select>
                <button type="button" wire:click="bulkAssignCongregationToCarPark"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">
                    <flux:icon name="building-office-2" class="size-4" />
                    Assign Default
                </button>
                <span class="text-zinc-300 dark:text-zinc-600 mx-1">|</span>
                <span class="text-xs font-medium text-amber-800 dark:text-amber-200 uppercase tracking-wide">{{ __('registrations.bulk_assign_individual_car_park') }}:</span>
                <select wire:model="bulkAssignIndividualCarParkId"
                    class="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200">
                    <option value="">{{ __('registrations.car_park') }}...</option>
                    @foreach($carParks ?? [] as $park)
                        <option value="{{ $park->id }}">{{ $park->name }}</option>
                    @endforeach
                </select>
                <button type="button" wire:click="bulkAssignSelectedToCarPark"
                    class="inline-flex items-center gap-2 rounded-lg bg-violet-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-violet-700">
                    <flux:icon name="user" class="size-4" />
                    Assign Selected
                </button>
                <span class="text-zinc-300 dark:text-zinc-600 mx-1">|</span>
                <button type="button" wire:click="openDownloadMasterPassesModal"
                    class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-emerald-700">
                    <flux:icon name="arrow-down-tray" class="size-4" />
                    {{ __('registrations.download_master_passes_zip') }}
                </button>
                <button type="button" wire:click="bulkMarkTicketSent"
                    class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-teal-700">
                    <flux:icon name="check" class="size-4" />
                    {{ __('registrations.mark_ticket_sent') }}
                </button>
                <button type="button" wire:click="bulkClearTicketSent"
                    class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600">
                    <flux:icon name="x-mark" class="size-4" />
                    {{ __('registrations.clear_ticket_sent') }}
                </button>
                @can('registrations.print')
                    <button type="button" wire:click="openSendTicketsModal"
                        class="inline-flex items-center gap-2 rounded-lg bg-sky-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-sky-700">
                        <flux:icon name="envelope" class="size-4" />
                        {{ __('registrations.send_car_park_tickets') }}
                    </button>
                @endcan
                <span class="text-zinc-300 dark:text-zinc-600 mx-1">|</span>
                <button type="button" wire:click="bulkDelete" wire:confirm="{{ __('registrations.bulk_delete_confirm') }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-red-700">
                    <flux:icon name="trash" class="size-4" />
                    {{ __('registrations.delete_selected') }}
                </button>
                <button type="button" wire:click="$set('selectedIds', [])"
                    class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600">
                    {{ __('registrations.cancel') }}
                </button>
            </div>
        </div>
    @endif

    <flux:separator />

    {{-- Filter fly-out panel (right drawer) --}}
    @if($filterOpen)
        <div class="fixed inset-0 z-50" aria-modal="true" role="dialog">
            <div class="fixed inset-0 bg-zinc-900/50 dark:bg-zinc-950/70 transition-opacity" wire:click="cancelFilters" aria-hidden="true"></div>
            <div class="fixed inset-y-0 right-0 w-full max-w-sm bg-white dark:bg-zinc-900 shadow-xl flex flex-col border-l border-zinc-200 dark:border-zinc-700 animate-in slide-in-from-right duration-200">
                <div class="flex items-center justify-between px-4 py-4 border-b border-zinc-200 dark:border-zinc-700">
                    <div class="flex items-center gap-2">
                        <flux:icon name="bars-3" class="size-5 text-zinc-500" />
                        <flux:heading size="lg">{{ __('registrations.apply_filters') }}</flux:heading>
                    </div>
                    <button type="button" wire:click="cancelFilters" class="rounded-lg p-2 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-300">
                        <flux:icon name="x-mark" class="size-5" />
                        <span class="sr-only">{{ __('registrations.close') }}</span>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-4 py-4 space-y-6">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400 mb-2">{{ __('registrations.congregation') }}</p>
                        <flux:input wire:model.live.debounce.300ms="filterDraftCongregationSearch" icon="magnifying-glass" placeholder="{{ __('registrations.filter_congregation_search') }}" class="w-full mb-2" />
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            @forelse($this->congregationsFilterOptions as $c)
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model="filterDraftCongregations" value="{{ $c }}"
                                        class="rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $c }}</span>
                                </label>
                            @empty
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('registrations.filter_congregation_no_match') }}</p>
                            @endforelse
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400 mb-2">{{ __('registrations.car_park') }}</p>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            @foreach($carParks ?? [] as $park)
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model="filterDraftCarParks" value="{{ $park->id }}"
                                        class="rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="inline-block size-3 shrink-0 rounded-full ring-1 ring-zinc-300/80 dark:ring-zinc-600"
                                        style="background-color: {{ $park->color ?? '#71717a' }}"></span>
                                    <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $park->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        <label class="mt-3 flex items-start gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="filterDraftUnassignedCarPark"
                                class="mt-1 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                            <span>
                                <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ __('registrations.filter_unassigned_car_park_only') }}</span>
                                <span class="block text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ __('registrations.filter_unassigned_car_park_hint') }}</span>
                            </span>
                        </label>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400 mb-2">{{ __('registrations.type') }}</p>
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model="filterDraftVehicleType" value="car"
                                    class="rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('registrations.car') }}</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model="filterDraftVehicleType" value="coach"
                                    class="rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('registrations.coach') }}</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400 mb-2">{{ __('registrations.days') }}</p>
                        <div class="space-y-2">
                            @foreach(\App\Support\ConventionDay::singleDayKeys() as $day)
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model="filterDraftDays" value="{{ $day }}"
                                        class="rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $day }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('registrations.filter_days_hint') }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400 mb-2">{{ __('registrations.elderly_infirm') }}</p>
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" wire:model="filterDraftElderlyInfirm" value="any"
                                    class="border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('registrations.filter_any') }}</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" wire:model="filterDraftElderlyInfirm" value="1"
                                    class="border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('registrations.yes') }}</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" wire:model="filterDraftElderlyInfirm" value="0"
                                    class="border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('registrations.no') }}</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400 mb-2">{{ __('registrations.ticket_sent') }}</p>
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" wire:model="filterDraftTicketSent" value="any"
                                    class="border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('registrations.filter_any') }}</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" wire:model="filterDraftTicketSent" value="1"
                                    class="border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('registrations.ticket_sent_yes') }}</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" wire:model="filterDraftTicketSent" value="0"
                                    class="border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('registrations.ticket_sent_no') }}</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400 mb-2">{{ __('registrations.filter_duplicates_section') }}</p>
                        <label class="flex items-start gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="filterDraftDuplicatesOnly"
                                class="mt-1 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                            <span>
                                <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ __('registrations.filter_duplicates_only') }}</span>
                                <span class="block text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ __('registrations.filter_duplicates_hint') }}</span>
                            </span>
                        </label>
                    </div>
                </div>
                <div class="flex gap-3 px-4 py-4 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:button variant="ghost" class="flex-1" wire:click="cancelFilters">{{ __('registrations.cancel') }}</flux:button>
                    <flux:button variant="primary" class="flex-1" wire:click="applyFilters">{{ __('registrations.apply') }}</flux:button>
                </div>
            </div>
        </div>
    @endif

    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700 -mx-4 sm:mx-0">
        <table class="w-full min-w-[800px] text-left text-sm">
            <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
                <tr>
                    <th class="w-10 px-4 py-3">
                        <input type="checkbox" wire:click="toggleSelectAll"
                            {{ count($registrations->items()) > 0 && count(array_intersect($selectedIds, $registrations->pluck('id')->all())) === count($registrations->items()) ? 'checked' : '' }}
                            class="rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                    </th>
                    <th class="px-6 py-3">
                        <button type="button" wire:click="setSort('created_at')" class="inline-flex items-center gap-1 font-medium hover:text-zinc-700 dark:hover:text-zinc-300">
                            {{ __('registrations.date') }}
                            @if($sortBy === 'created_at')
                                <flux:icon name="{{ $sortDir === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                            @endif
                        </button>
                    </th>
                    <th class="px-6 py-3">
                        <button type="button" wire:click="setSort('ticket_number')" class="inline-flex items-center gap-1 font-medium hover:text-zinc-700 dark:hover:text-zinc-300">
                            {{ __('registrations.ticket_number') }}
                            @if($sortBy === 'ticket_number')
                                <flux:icon name="{{ $sortDir === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                            @endif
                        </button>
                    </th>
                    <th class="px-6 py-3">
                        <button type="button" wire:click="setSort('name')" class="inline-flex items-center gap-1 font-medium hover:text-zinc-700 dark:hover:text-zinc-300">
                            {{ __('registrations.name') }}
                            @if($sortBy === 'name')
                                <flux:icon name="{{ $sortDir === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                            @endif
                        </button>
                    </th>
                    <th class="px-6 py-3">
                        <button type="button" wire:click="setSort('congregation')" class="inline-flex items-center gap-1 font-medium hover:text-zinc-700 dark:hover:text-zinc-300">
                            {{ __('registrations.congregation') }}
                            @if($sortBy === 'congregation')
                                <flux:icon name="{{ $sortDir === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                            @endif
                        </button>
                    </th>
                    <th class="px-6 py-3">{{ __('registrations.car_park') }}</th>
                    <th class="px-6 py-3">{{ __('registrations.type') }}</th>
                    <th class="px-6 py-3">{{ __('registrations.sharing') }}</th>
                    <th class="px-6 py-3">{{ __('registrations.vehicle_reg') }}</th>
                    <th class="px-6 py-3">{{ __('registrations.contact') }}</th>
                    <th class="px-6 py-3">{{ __('registrations.elderly_infirm') }}</th>
                    <th class="px-6 py-3">{{ __('registrations.days') }}</th>
                    <th class="px-6 py-3">{{ __('registrations.ticket_sent') }}</th>
                    <th class="px-6 py-3 text-end">{{ __('registrations.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700 bg-white dark:bg-zinc-800">
                @forelse($registrations as $reg)
                    <tr wire:key="registration-row-{{ $reg->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
                        <td class="w-10 px-4 py-4">
                            <input type="checkbox" wire:model.live="selectedIds" value="{{ $reg->id }}"
                                class="rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                        </td>
                        <td class="px-6 py-4 text-zinc-500 whitespace-nowrap">
                            {{ $reg->created_at->format('d M H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-mono text-sm font-semibold tabular-nums text-zinc-800 dark:text-zinc-200">{{ $reg->ticketNumber() }}</span>
                        </td>
                        <td class="px-6 py-4 font-medium text-zinc-900 dark:text-white">
                            {{ $reg->name }}
                            @if($reg->is_circuit_overseer ?? false)
                                <div class="mt-1">
                                    <flux:badge size="sm" color="sky">{{ __('registrations.circuit_overseer') }}</flux:badge>
                                </div>
                            @endif
                            @if(($reg->vehicle_type ?? 'car') === 'coach' && ($reg->coach_captain_to_be_assigned ?? false))
                                <div class="mt-1">
                                    <flux:badge size="sm" color="amber">{{ __('registrations.secretary_acting_for_captain_badge') }}</flux:badge>
                                </div>
                            @endif
                            @if(isset($recentlyChangedIds[$reg->id]))
                                <div class="mt-1">
                                    <flux:badge size="sm" color="lime">{{ __('registrations.recently_changed_badge') }}</flux:badge>
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-zinc-600 dark:text-zinc-300">
                            {{ $reg->congregation ?: '—' }}
                        </td>
                        <td class="px-6 py-4 text-xs">
                            @php
                                $effectiveCarPark = $reg->carPark
                                    ?? ($congregationCarParkByName[trim($reg->congregation ?? '')] ?? null);
                            @endphp
                            @if($effectiveCarPark)
                                <x-car-park-badge :park="$effectiveCarPark" />
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <flux:badge color="{{ ($reg->vehicle_type ?? 'car') === 'coach' ? 'purple' : 'zinc' }}">
                                {{ ($reg->vehicle_type ?? 'car') === 'coach' ? __('registrations.coach') : __('registrations.car') }}
                            </flux:badge>
                        </td>
                        <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400 text-xs max-w-[140px]">
                            @if(($reg->vehicle_type ?? 'car') === 'coach')
                                @if($reg->sharing_with_other_congregations ?? false)
                                    <span class="font-medium text-indigo-600 dark:text-indigo-400">{{ __('registrations.yes') }}</span>
                                    @if(!empty($reg->sharing_congregations_notes))
                                        <div class="mt-0.5 text-zinc-500 dark:text-zinc-500 truncate" title="{{ $reg->sharing_congregations_notes }}">{{ \Illuminate\Support\Str::limit($reg->sharing_congregations_notes, 40) }}</div>
                                    @endif
                                @else
                                    <span class="text-zinc-400">{{ __('registrations.no') }}</span>
                                @endif
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 font-mono text-zinc-600 dark:text-zinc-300">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="bg-zinc-100 px-2 py-1 rounded text-xs dark:bg-zinc-700 font-bold tracking-wider">{{ $reg->vehicle_registration ?? '—' }}</span>
                                @if($reg->vehicle_registration && isset($duplicateVehicleRegKeys[$reg->vehicle_registration]))
                                    <flux:badge size="sm" color="amber">{{ __('registrations.duplicate_vehicle_reg_badge') }}</flux:badge>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 text-zinc-500">
                            {{ $reg->contact_number }}
                            <div class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-zinc-400">
                                <span>{{ $reg->email }}</span>
                                @php
                                    $emailNorm = strtolower(trim((string) ($reg->email ?? '')));
                                @endphp
                                @if($emailNorm !== '' && isset($duplicateEmailKeys[$emailNorm]))
                                    <flux:badge size="sm" color="amber">{{ __('registrations.duplicate_email_badge') }}</flux:badge>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($reg->elderly_infirm_parking ?? false)
                                <flux:badge color="amber">{{ __('registrations.yes') }}</flux:badge>
                            @else
                                <span class="text-zinc-400 text-xs">{{ __('registrations.no') }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex gap-1 flex-wrap">
                                @foreach($reg->days ?? [] as $day)
                                    <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10 dark:bg-indigo-400/10 dark:text-indigo-400 dark:ring-indigo-400/30">{{ substr($day, 0, 3) }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($reg->ticket_sent_at)
                                <span class="inline-flex items-center gap-1.5 text-emerald-700 dark:text-emerald-400" title="{{ $reg->ticket_sent_at->format('d M Y H:i') }}">
                                    <flux:icon name="check-circle" class="size-5" />
                                    <span class="text-xs font-semibold">{{ __('registrations.ticket_sent_yes') }}</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 text-zinc-400 dark:text-zinc-500">
                                    <span class="text-lg leading-none" aria-hidden="true">—</span>
                                    <span class="text-xs">{{ __('registrations.ticket_sent_no') }}</span>
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-end">
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />

                                <flux:menu>
                                    <flux:menu.item wire:click="edit({{ $reg->id }})" icon="pencil">{{ __('registrations.edit') }}</flux:menu.item>
                                    <flux:menu.item href="{{ route('admin.registrations.print', $reg->id) }}" target="_blank" icon="qr-code">{{ __('registrations.view_master_pass') }}</flux:menu.item>
                                    @can('registrations.print')
                                        <flux:menu.item wire:click="openResendTicketModal({{ $reg->id }})" icon="envelope">{{ __('registrations.resend') }}</flux:menu.item>
                                    @endcan
                                    <flux:menu.item wire:click="delete({{ $reg->id }})"
                                        wire:confirm="{{ __('registrations.delete_confirm') }}" icon="trash"
                                        variant="danger">{{ __('registrations.delete') }}</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="14" class="px-6 py-12 text-center text-zinc-500">
                            <div class="flex flex-col items-center justify-center">
                                <flux:icon name="clipboard-document-list" class="size-10 text-zinc-300 mb-2" />
                                <p>{{ __('registrations.no_registrations') }}</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $registrations->links() }}
    </div>

    {{-- Create / Edit Modal --}}
    <flux:modal wire:model="modalOpen" class="w-[calc(100vw-2rem)] max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ $editingRegistration ? __('registrations.edit_modal_title') : __('registrations.create_modal_title') }}
                </flux:heading>
                <flux:subheading>
                    {{ $editingRegistration ? __('registrations.edit_modal_subtitle') : __('registrations.create_modal_subtitle') }}
                </flux:subheading>
            </div>

            <flux:input wire:model="name" label="{{ __('registrations.name') }}" placeholder="{{ __('registrations.full_name') }}" />

            @if($editingRegistration && ($editingRegistration->is_circuit_overseer ?? false))
                <flux:input wire:model="congregation" label="{{ __('registrations.congregation') }}" placeholder="{{ __('registrations.circuit_overseer') }}" />
            @else
                <flux:select wire:model="congregation" label="{{ __('registrations.congregation') }}" placeholder="{{ __('registrations.select_congregation') }}">
                    @foreach($congregations as $name)
                        <option value="{{ $name }}">{{ $name }}</option>
                    @endforeach
                </flux:select>
            @endif

            <div class="space-y-2">
                <label for="carParkId" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('registrations.car_park') }} ({{ __('registrations.optional_individual') }})</label>
                <select wire:model="carParkId" id="carParkId"
                    class="block w-full rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                    <option value="">— {{ __('registrations.no_car_park') }}</option>
                    @foreach($carParks ?? [] as $park)
                        <option value="{{ $park->id }}">{{ $park->name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('registrations.car_park_individual_hint') }}</p>
            </div>

            <div class="space-y-2">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('registrations.vehicle_type') }}</label>
                <div class="flex gap-2">
                    <button type="button" wire:click="$set('vehicleType', 'car'); $set('sharingWithOtherCongregations', '0'); $set('sharingCongregationsNotes', '')" @class([
                        'flex-1 px-3 py-2 rounded-lg text-sm font-medium border transition',
                        'bg-indigo-500 text-white border-indigo-600' => $vehicleType === 'car',
                        'bg-white dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700' => $vehicleType !== 'car',
                    ])>{{ __('registrations.car') }}</button>
                    <button type="button" wire:click="$set('vehicleType', 'coach')" @class([
                        'flex-1 px-3 py-2 rounded-lg text-sm font-medium border transition',
                        'bg-indigo-500 text-white border-indigo-600' => $vehicleType === 'coach',
                        'bg-white dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700' => $vehicleType !== 'coach',
                    ])>{{ __('registrations.coach') }}</button>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <flux:input wire:model="vehicleReg" label="{{ __('registrations.vehicle_reg') }}" placeholder="{{ __('registrations.registration') }}" />
                <flux:input wire:model="contactNumber" label="{{ __('registrations.contact_number') }}" placeholder="" />
            </div>

            <flux:input wire:model="email" label="{{ __('registrations.contact') }}" type="email" placeholder="{{ __('registrations.email_optional') }}" />

            <div class="space-y-2">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('registrations.elderly_infirm_parking') }}</label>
                <div class="flex gap-2">
                    <button type="button" wire:click="$set('elderlyInfirmParking', '1')" @class([
                        'flex-1 px-3 py-2 rounded-lg text-sm font-medium border transition',
                        'bg-indigo-500 text-white border-indigo-600' => $elderlyInfirmParking === '1' || $elderlyInfirmParking === true,
                        'bg-white dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700' => $elderlyInfirmParking !== '1' && $elderlyInfirmParking !== true,
                    ])>{{ __('registrations.yes') }}</button>
                    <button type="button" wire:click="$set('elderlyInfirmParking', '0')" @class([
                        'flex-1 px-3 py-2 rounded-lg text-sm font-medium border transition',
                        'bg-indigo-500 text-white border-indigo-600' => $elderlyInfirmParking === '0' || $elderlyInfirmParking === false,
                        'bg-white dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700' => $elderlyInfirmParking !== '0' && $elderlyInfirmParking !== false,
                    ])>{{ __('registrations.no') }}</button>
                </div>
            </div>

            @if($vehicleType === 'coach')
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" wire:model.live="coachCaptainToBeAssigned"
                    class="mt-1 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm text-zinc-700 dark:text-zinc-300">
                    <span class="font-medium">{{ __('register.coach_captain_to_be_assigned') }}</span>
                    <span class="block text-xs text-zinc-500 mt-0.5">{{ __('register.coach_captain_to_be_assigned_help') }}</span>
                </span>
            </label>
            <div class="space-y-2">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('registrations.sharing_with_other_congregations') }}</label>
                <div class="flex gap-2">
                    <button type="button" wire:click="$set('sharingWithOtherCongregations', '1')" @class([
                        'flex-1 px-3 py-2 rounded-lg text-sm font-medium border transition',
                        'bg-indigo-500 text-white border-indigo-600' => $sharingWithOtherCongregations === '1',
                        'bg-white dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700' => $sharingWithOtherCongregations !== '1',
                    ])>{{ __('registrations.yes') }}</button>
                    <button type="button" wire:click="$set('sharingWithOtherCongregations', '0'); $set('sharingCongregationsNotes', '')" @class([
                        'flex-1 px-3 py-2 rounded-lg text-sm font-medium border transition',
                        'bg-indigo-500 text-white border-indigo-600' => $sharingWithOtherCongregations === '0',
                        'bg-white dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700' => $sharingWithOtherCongregations !== '0',
                    ])>{{ __('registrations.no') }}</button>
                </div>
            </div>
            @if($sharingWithOtherCongregations === '1')
            <div class="space-y-2">
                <label for="sharingCongregationsNotes" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('registrations.specify_all_congregations') }}</label>
                <textarea wire:model="sharingCongregationsNotes" id="sharingCongregationsNotes" rows="3"
                    class="block w-full rounded-lg border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                    placeholder="{{ __('registrations.specify_all_congregations_placeholder') }}"></textarea>
                @error('sharingCongregationsNotes') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>
            @endif
            @endif

            <div class="space-y-2">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('registrations.days_attending') }}</label>
                <div class="flex gap-2">
                    @foreach(['Friday', 'Saturday', 'Sunday'] as $day)
                        <button type="button" wire:click="toggleDay('{{ $day }}')" @class([
                            'px-3 py-1.5 rounded-lg text-sm font-medium transition-all border',
                            'bg-indigo-500 text-white border-indigo-600 shadow-sm' => in_array($day, $days),
                            'bg-white dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700' => !in_array($day, $days),
                        ])>
                            {{ $day }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('modalOpen', false)">{{ __('registrations.cancel') }}</flux:button>
                <flux:button variant="primary" wire:click="save">
                    {{ $editingRegistration ? __('registrations.save_changes') : __('registrations.create_button') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Bulk assign congregation to car park modal --}}
    <flux:modal wire:model="bulkAssignCarParkModalOpen" class="w-[calc(100vw-2rem)] max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('registrations.bulk_assign_congregation_car_park') }}</flux:heading>
                <flux:subheading>Assign the congregation(s) of {{ count($selectedIds) }} selected registration(s) to a car park. Congregations are matched by name.</flux:subheading>
            </div>
            <div class="space-y-2">
                <label for="bulkAssignCarParkId" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('registrations.car_park') }}</label>
                <select wire:model="bulkAssignCarParkId" id="bulkAssignCarParkId"
                    class="block w-full rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                    <option value="">Select a car park</option>
                    @foreach ($carParks ?? [] as $park)
                        <option value="{{ $park->id }}">{{ $park->name }} (Cap: {{ $park->capacity }})</option>
                    @endforeach
                </select>
            </div>
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('bulkAssignCarParkModalOpen', false)">{{ __('registrations.cancel') }}</flux:button>
                <flux:button variant="primary" wire:click="bulkAssignCongregationToCarPark">{{ __('registrations.save_changes') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Download master passes: format (single selection) + mark-as-sent --}}
    <flux:modal wire:model="downloadPassesModalOpen" class="w-[calc(100vw-2rem)] max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('registrations.download_passes_modal_title') }}</flux:heading>
                <flux:subheading>{{ __('registrations.download_passes_modal_body', ['count' => count($selectedIds)]) }}</flux:subheading>
            </div>
            @if (count($selectedIds) === 1)
                <div>
                    <flux:text class="mb-2 font-medium">{{ __('registrations.download_format_label') }}</flux:text>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <flux:button
                            type="button"
                            size="sm"
                            variant="{{ $downloadFormat === 'zip' ? 'primary' : 'ghost' }}"
                            wire:click="$set('downloadFormat', 'zip')"
                        >
                            {{ __('registrations.download_format_zip') }}
                        </flux:button>
                        <flux:button
                            type="button"
                            size="sm"
                            variant="{{ $downloadFormat === 'pdf' ? 'primary' : 'ghost' }}"
                            wire:click="$set('downloadFormat', 'pdf')"
                        >
                            {{ __('registrations.download_format_pdf') }}
                        </flux:button>
                    </div>
                    <flux:text class="mt-2 text-sm text-zinc-500">{{ __('registrations.download_format_help') }}</flux:text>
                </div>
            @endif
            <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                <flux:button variant="ghost" wire:click="$set('downloadPassesModalOpen', false)">{{ __('registrations.cancel') }}</flux:button>
                <flux:button variant="ghost" wire:click="confirmDownloadMasterPassesZip(false)">
                    {{ __('registrations.download_passes_only') }}
                </flux:button>
                <flux:button variant="primary" wire:click="confirmDownloadMasterPassesZip(true)">
                    {{ __('registrations.download_passes_and_mark_sent') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Send car park tickets email modal --}}
    @can('registrations.manage')
        <flux:modal wire:model="broadcastModalOpen" class="w-[calc(100vw-2rem)] max-w-xl">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('registrations.email_guests') }}</flux:heading>
                    <flux:subheading>{{ __('registrations.email_guests_help') }}</flux:subheading>
                </div>

                <div class="space-y-2">
                    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('registrations.broadcast_scope') }}</p>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <label class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700">
                            <input type="radio" wire:model.live="broadcastScope" value="filters" class="text-indigo-600 focus:ring-indigo-500">
                            <span>{{ __('registrations.broadcast_scope_filters') }}</span>
                        </label>
                        <label @class([
                            'inline-flex items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700',
                            'opacity-50' => count($selectedIds) === 0,
                        ])>
                            <input type="radio" wire:model.live="broadcastScope" value="selected" @disabled(count($selectedIds) === 0) class="text-indigo-600 focus:ring-indigo-500">
                            <span>{{ __('registrations.broadcast_scope_selected') }}</span>
                        </label>
                    </div>
                    @error('broadcastScope')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="text-sm text-zinc-600 dark:text-zinc-300">
                        {{ __('registrations.broadcast_recipient_count', ['count' => $this->broadcastRecipientCount]) }}
                    </p>
                </div>

                <div class="space-y-2">
                    <flux:input
                        wire:model="broadcastSubject"
                        label="{{ __('registrations.broadcast_subject') }}"
                        maxlength="200"
                    />
                    @error('broadcastSubject')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <flux:textarea
                        wire:model="broadcastBody"
                        label="{{ __('registrations.broadcast_body') }}"
                        rows="8"
                        maxlength="10000"
                    />
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('registrations.broadcast_body_hint') }}</p>
                    @error('broadcastBody')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" wire:click="$set('broadcastModalOpen', false)" wire:loading.attr="disabled">
                        {{ __('registrations.cancel') }}
                    </flux:button>
                    <flux:button
                        variant="primary"
                        wire:click="sendRegistrationBroadcast"
                        wire:confirm="{{ __('registrations.broadcast_confirm', ['count' => $this->broadcastRecipientCount]) }}"
                        wire:loading.attr="disabled"
                        wire:target="sendRegistrationBroadcast"
                        :disabled="$this->broadcastRecipientCount < 1"
                    >
                        <span wire:loading.remove wire:target="sendRegistrationBroadcast">{{ __('registrations.broadcast_send') }}</span>
                        <span wire:loading wire:target="sendRegistrationBroadcast">{{ __('registrations.broadcast_sending') }}</span>
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endcan

    <flux:modal wire:model="sendTicketsModalOpen" class="w-[calc(100vw-2rem)] max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('registrations.send_car_park_tickets') }}</flux:heading>
                <flux:subheading>{{ __('registrations.send_car_park_tickets_help', ['count' => count($selectedIds)]) }}</flux:subheading>
            </div>
            <div class="space-y-2">
                {{-- type=text + autofill ignores: Bitwarden/1Password break type=email inside Flux modals --}}
                <flux:input
                    wire:model="ticketEmailTo"
                    id="ticketEmailTo"
                    type="text"
                    inputmode="email"
                    label="{{ __('registrations.ticket_email_to') }}"
                    placeholder="congregation@example.com"
                    autocomplete="off"
                    data-1p-ignore
                    data-lpignore="true"
                    data-bwignore
                    data-form-type="other"
                />
                @error('ticketEmailTo')
                    <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('registrations.ticket_email_cc_hint') }}</p>
            </div>
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('sendTicketsModalOpen', false)" wire:loading.attr="disabled">{{ __('registrations.cancel') }}</flux:button>
                <flux:button variant="primary" wire:click="sendCarParkTickets" wire:loading.attr="disabled" wire:target="sendCarParkTickets">
                    <span wire:loading.remove wire:target="sendCarParkTickets">{{ __('registrations.send_tickets_button') }}</span>
                    <span wire:loading wire:target="sendCarParkTickets">{{ __('registrations.sending_tickets') }}</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Resend single registration ticket --}}
    <flux:modal wire:model="resendModalOpen" class="w-[calc(100vw-2rem)] max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('registrations.resend_title') }}</flux:heading>
                <flux:subheading>
                    {{ __('registrations.resend_help', ['name' => $resendRegistrationName !== '' ? $resendRegistrationName : '—']) }}
                </flux:subheading>
            </div>

            @if ($resendOriginalEmail !== '')
                <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900/50">
                    <p class="text-zinc-600 dark:text-zinc-300">
                        {{ __('registrations.resend_original_label') }}:
                        <span class="font-medium text-zinc-900 dark:text-white">{{ $resendOriginalEmail }}</span>
                    </p>
                    <button type="button" wire:click="useResendOriginalEmail"
                        class="mt-2 text-sm font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">
                        {{ __('registrations.resend_use_original') }}
                    </button>
                </div>
            @endif

            <div class="space-y-2">
                <flux:input
                    wire:model="resendEmailTo"
                    id="registrationResendEmailTo"
                    type="text"
                    inputmode="email"
                    label="{{ __('registrations.resend_email_label') }}"
                    placeholder="guest@example.com"
                    autocomplete="off"
                    data-1p-ignore
                    data-lpignore="true"
                    data-bwignore
                    data-form-type="other"
                />
                @error('resendEmailTo')
                    <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-2">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <label for="registrationResendNote" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('registrations.resend_note_label') }}
                        <span class="font-normal text-zinc-500">({{ __('registrations.resend_note_optional') }})</span>
                    </label>
                    <button type="button" wire:click="applyResendNoteTemplate"
                        class="text-sm font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">
                        {{ __('registrations.resend_note_use_template') }}
                    </button>
                </div>
                <textarea
                    id="registrationResendNote"
                    wire:model="resendNote"
                    rows="10"
                    class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                    placeholder="{{ __('registrations.resend_note_placeholder') }}"
                ></textarea>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('registrations.resend_note_help') }}</p>
                @error('resendNote')
                    <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            @if (count($this->ticketEmailCcs) > 0)
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('registrations.resend_cc_label') }}:
                    <span class="font-medium text-zinc-700 dark:text-zinc-200">{{ implode(', ', $this->ticketEmailCcs) }}</span>
                </p>
            @endif

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="closeResendTicketModal" wire:loading.attr="disabled">
                    {{ __('registrations.cancel') }}
                </flux:button>
                <flux:button variant="primary" wire:click="resendTicket" wire:loading.attr="disabled" wire:target="resendTicket">
                    <span wire:loading.remove wire:target="resendTicket">{{ __('registrations.resend_send') }}</span>
                    <span wire:loading wire:target="resendTicket">{{ __('registrations.resend_sending') }}</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="ticketsSentSuccessOpen" class="w-[calc(100vw-2rem)] max-w-md">
        <div class="space-y-6">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                    <flux:icon name="check" class="size-5" />
                </div>
                <div>
                    <flux:heading size="lg">{{ __('registrations.tickets_sent_success_title') }}</flux:heading>
                    <flux:subheading>{{ $ticketsSentSuccessMessage }}</flux:subheading>
                </div>
            </div>
            <div class="flex justify-end">
                <flux:button variant="primary" wire:click="$set('ticketsSentSuccessOpen', false)">{{ __('registrations.ok') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
