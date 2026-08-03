<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('coaches.title') }}</flux:heading>
            <flux:subheading class="mt-1">{{ __('coaches.subtitle') }}</flux:subheading>
        </div>
        <div class="flex flex-wrap gap-2">
            <flux:button variant="ghost" :href="route('admin.registrations')" wire:navigate icon="clipboard-document-list">
                {{ __('coaches.view_registrations') }}
            </flux:button>
            <flux:button variant="ghost" :href="route('admin.coaches.export')" icon="arrow-down-tray">
                {{ __('coaches.export_button') }}
            </flux:button>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                {{ __('coaches.stat_registrations_total') }}
            </p>
            <p class="mt-2 text-3xl font-bold tabular-nums text-zinc-900 dark:text-white">
                {{ number_format($coachMetrics['registrations_total']) }}
            </p>
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('coaches.stat_registrations_total_hint') }}
            </p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                {{ __('coaches.stat_unique_coaches') }}
            </p>
            <p class="mt-2 text-3xl font-bold tabular-nums text-zinc-900 dark:text-white">
                {{ number_format($coachMetrics['unique_coaches']) }}
            </p>
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('coaches.stat_unique_coaches_hint') }}
            </p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                {{ __('coaches.stat_coverage') }}
            </p>
            <p class="mt-2 text-3xl font-bold tabular-nums text-zinc-900 dark:text-white">
                {{ number_format($coachCoverage['registered_expected']) }}
                <span class="text-lg font-semibold text-zinc-400">/ {{ number_format($coachCoverage['expected_total']) }}</span>
            </p>
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('coaches.stat_coverage_hint', [
                    'registered' => number_format($coachCoverage['registered_expected']),
                    'expected' => number_format($coachCoverage['expected_total']),
                ]) }}
            </p>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div @class([
            'rounded-xl border p-5 shadow-sm',
            'border-amber-300 bg-amber-50 dark:border-amber-700 dark:bg-amber-950/40' => count($coachCoverage['missing']) > 0,
            'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800' => count($coachCoverage['missing']) === 0,
        ])>
            <p class="text-sm font-semibold text-zinc-900 dark:text-white">
                {{ __('coaches.coverage_missing_title') }}
                @if(count($coachCoverage['missing']) > 0)
                    <span class="ms-1 tabular-nums text-amber-700 dark:text-amber-300">({{ count($coachCoverage['missing']) }})</span>
                @endif
            </p>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('coaches.coverage_missing_hint') }}</p>
            @if(count($coachCoverage['missing']) === 0)
                <p class="mt-3 text-sm text-emerald-700 dark:text-emerald-400">{{ __('coaches.coverage_missing_none') }}</p>
            @else
                <ul class="mt-3 max-h-48 space-y-1 overflow-y-auto text-sm text-zinc-800 dark:text-zinc-200">
                    @foreach($coachCoverage['missing'] as $name)
                        <li wire:key="missing-cong-{{ $loop->index }}">{{ $name }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm font-semibold text-zinc-900 dark:text-white">
                {{ __('coaches.coverage_unexpected_title') }}
                @if(count($coachCoverage['unexpected']) > 0)
                    <span class="ms-1 tabular-nums text-zinc-500">({{ count($coachCoverage['unexpected']) }})</span>
                @endif
            </p>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('coaches.coverage_unexpected_hint') }}</p>
            @if(count($coachCoverage['unexpected']) === 0)
                <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">{{ __('coaches.coverage_unexpected_none') }}</p>
            @else
                <ul class="mt-3 max-h-48 space-y-1 overflow-y-auto text-sm text-zinc-800 dark:text-zinc-200">
                    @foreach($coachCoverage['unexpected'] as $name)
                        <li wire:key="unexpected-cong-{{ $loop->index }}">{{ $name }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-center">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
            placeholder="{{ __('coaches.search_placeholder') }}" class="w-full min-w-0 sm:max-w-xs" />
        <select wire:model.live="filterStayingOnSite"
            class="block w-full sm:w-auto rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
            <option value="any">{{ __('coaches.filter_staying') }}: {{ __('coaches.filter_staying_any') }}</option>
            <option value="yes">{{ __('coaches.filter_staying') }}: {{ __('coaches.filter_staying_yes') }}</option>
            <option value="no">{{ __('coaches.filter_staying') }}: {{ __('coaches.filter_staying_no') }}</option>
            <option value="not_set">{{ __('coaches.filter_staying') }}: {{ __('coaches.filter_staying_not_set') }}</option>
        </select>
        <select wire:model.live="perPage"
            class="block w-full sm:w-auto rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
        </select>
    </div>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700 -mx-4 sm:mx-0">
        <table class="w-full min-w-[1100px] text-left text-sm text-zinc-500 dark:text-zinc-400">
            <thead class="bg-zinc-50 text-xs uppercase text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
                <tr>
                    <th class="px-4 py-3 cursor-pointer select-none" wire:click="setSort('congregation')">
                        {{ __('coaches.col_congregation') }}
                        @if($sortBy === 'congregation')<span class="ms-1">{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>@endif
                    </th>
                    <th class="px-4 py-3">{{ __('coaches.col_contact_role') }}</th>
                    <th class="px-4 py-3 cursor-pointer select-none" wire:click="setSort('name')">
                        {{ __('coaches.col_contact') }}
                        @if($sortBy === 'name')<span class="ms-1">{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>@endif
                    </th>
                    <th class="px-4 py-3">{{ __('coaches.col_survey_coach_size') }}</th>
                    <th class="px-4 py-3">{{ __('coaches.col_survey_sharing') }}</th>
                    <th class="px-4 py-3">{{ __('coaches.col_car_park') }}</th>
                    <th class="px-4 py-3">{{ __('coaches.col_days') }}</th>
                    <th class="px-4 py-3">{{ __('coaches.col_vehicle_reg') }}</th>
                    <th class="px-4 py-3">{{ __('coaches.col_sharing_registration') }}</th>
                    <th class="px-4 py-3 cursor-pointer select-none" wire:click="setSort('coach_staying_on_site')">
                        {{ __('coaches.col_staying_on_site') }}
                        @if($sortBy === 'coach_staying_on_site')<span class="ms-1">{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>@endif
                    </th>
                    <th class="px-4 py-3">{{ __('coaches.col_actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700 bg-white dark:bg-zinc-800">
                @forelse($coaches as $reg)
                    @php
                        $survey = $this->surveyResponseForRegistration($reg);
                    @endphp
                    <tr wire:key="coach-row-{{ $reg->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                        <td class="px-4 py-4 font-medium text-zinc-900 dark:text-white">
                            {{ $reg->congregation ?: '—' }}
                        </td>
                        <td class="px-4 py-4">
                            @if($reg->coach_captain_to_be_assigned ?? false)
                                <flux:badge size="sm" color="amber">{{ __('coaches.contact_role_secretary') }}</flux:badge>
                            @else
                                <flux:badge size="sm" color="indigo">{{ __('coaches.contact_role_captain') }}</flux:badge>
                            @endif
                        </td>
                        <td class="px-4 py-4">
                            <div class="font-medium text-zinc-900 dark:text-white">{{ $reg->name }}</div>
                            <div class="mt-0.5 text-xs text-zinc-500">{{ $reg->contact_number }}</div>
                            @if($reg->email)
                                <div class="text-xs text-zinc-400">{{ $reg->email }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-xs">
                            {{ $survey ? $this->coachSizeLabel($survey->coach_size) : __('coaches.survey_unknown') }}
                        </td>
                        <td class="px-4 py-4 text-xs">
                            @php
                                $partnerNames = $this->sharingPartnerNames($survey);
                            @endphp
                            @if($survey && ($survey->sharing_coach_with_others ?? false))
                                <span class="text-indigo-600 dark:text-indigo-400">{{ __('registrations.yes') }}</span>
                                @if(count($partnerNames) > 0)
                                    <div class="mt-0.5 text-zinc-600 dark:text-zinc-300" title="{{ implode(', ', $partnerNames) }}">
                                        {{ implode(', ', $partnerNames) }}
                                    </div>
                                @endif
                            @elseif($survey)
                                <span class="text-zinc-400">{{ __('registrations.no') }}</span>
                            @else
                                <span class="text-zinc-400">{{ __('coaches.survey_unknown') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-4">
                            @if($reg->carPark)
                                <flux:badge size="sm" color="violet">{{ $reg->carPark->name }}</flux:badge>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-xs">
                            {{ is_array($reg->days) ? implode(', ', $reg->days) : '—' }}
                        </td>
                        <td class="px-4 py-4 font-mono text-xs">
                            {{ $reg->vehicle_registration ?? '—' }}
                        </td>
                        <td class="px-4 py-4 text-xs max-w-[120px]">
                            @if($reg->sharing_with_other_congregations ?? false)
                                <span class="font-medium text-indigo-600 dark:text-indigo-400">{{ __('registrations.yes') }}</span>
                                @if(!empty($reg->sharing_congregations_notes))
                                    <div class="mt-0.5 truncate text-zinc-500" title="{{ $reg->sharing_congregations_notes }}">
                                        {{ \Illuminate\Support\Str::limit($reg->sharing_congregations_notes, 36) }}
                                    </div>
                                @endif
                            @else
                                <span class="text-zinc-400">{{ __('registrations.no') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-4">
                            <select
                                wire:change="updateStayingOnSite({{ $reg->id }}, $event.target.value)"
                                class="block w-full min-w-[7rem] rounded-lg border-zinc-200 bg-white px-2 py-1.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
                            >
                                <option value="" @selected($reg->coach_staying_on_site === null)>{{ __('coaches.staying_not_set') }}</option>
                                <option value="1" @selected($reg->coach_staying_on_site === true)>{{ __('coaches.staying_yes') }}</option>
                                <option value="0" @selected($reg->coach_staying_on_site === false)>{{ __('coaches.staying_no') }}</option>
                            </select>
                        </td>
                        <td class="px-4 py-4">
                            <flux:button size="sm" variant="ghost" wire:click="edit({{ $reg->id }})">
                                {{ __('coaches.edit') }}
                            </flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="px-6 py-12 text-center text-zinc-500">
                            {{ __('coaches.no_coaches') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $coaches->links() }}
    </div>

    <flux:modal wire:model="modalOpen" class="w-[calc(100vw-2rem)] max-w-lg">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('coaches.edit_coach') }}</flux:heading>

            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" wire:model.live="coachCaptainToBeAssigned"
                    class="mt-1 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm text-zinc-700 dark:text-zinc-300">
                    <span class="font-medium">{{ __('register.coach_captain_to_be_assigned') }}</span>
                    <span class="block text-xs text-zinc-500 mt-0.5">{{ __('register.coach_captain_to_be_assigned_help') }}</span>
                </span>
            </label>

            <flux:input
                wire:model="name"
                :label="$coachCaptainToBeAssigned ? __('register.secretary_name') : __('register.coach_captain_name')"
            />
            <flux:input
                wire:model="contactNumber"
                :label="$coachCaptainToBeAssigned ? __('register.secretary_contact_number') : __('register.coach_captain_contact_number')"
            />
            <flux:input
                wire:model="email"
                type="email"
                :label="$coachCaptainToBeAssigned ? __('register.secretary_email_address') : __('register.coach_captain_email_address')"
            />

            <div class="space-y-2">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('registrations.sharing_with_other_congregations') }}</label>
                <div class="flex gap-2">
                    <button type="button" wire:click="$set('sharingWithOtherCongregations', '1')" @class([
                        'flex-1 px-3 py-2 rounded-lg text-sm font-medium border transition',
                        'bg-indigo-500 text-white border-indigo-600' => $sharingWithOtherCongregations === '1',
                        'bg-white dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 border-zinc-200 dark:border-zinc-700' => $sharingWithOtherCongregations !== '1',
                    ])>{{ __('registrations.yes') }}</button>
                    <button type="button" wire:click="$set('sharingWithOtherCongregations', '0'); $set('sharingCongregationsNotes', '')" @class([
                        'flex-1 px-3 py-2 rounded-lg text-sm font-medium border transition',
                        'bg-indigo-500 text-white border-indigo-600' => $sharingWithOtherCongregations === '0',
                        'bg-white dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 border-zinc-200 dark:border-zinc-700' => $sharingWithOtherCongregations !== '0',
                    ])>{{ __('registrations.no') }}</button>
                </div>
            </div>
            @if($sharingWithOtherCongregations === '1')
                <div class="space-y-2">
                    <label for="coachSharingNotes" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('registrations.specify_all_congregations') }}</label>
                    <textarea wire:model="sharingCongregationsNotes" id="coachSharingNotes" rows="3"
                        class="block w-full rounded-lg border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 text-sm"
                        placeholder="{{ __('registrations.specify_all_congregations_placeholder') }}"></textarea>
                    @error('sharingCongregationsNotes') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            @endif

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('modalOpen', false)">{{ __('coaches.cancel') }}</flux:button>
                <flux:button variant="primary" wire:click="save">{{ __('coaches.save_changes') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
