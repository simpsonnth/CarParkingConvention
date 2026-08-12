<div class="mx-auto max-w-5xl space-y-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('parking_qr.title') }}</flux:heading>
            <flux:subheading class="mt-1">{{ __('parking_qr.subtitle') }}</flux:subheading>
        </div>
        <flux:button :href="route('admin.dashboard')" variant="ghost" icon="arrow-left" wire:navigate size="sm">
            {{ __('parking_qr.back_dashboard') }}
        </flux:button>
    </div>

    <div class="rounded-2xl border border-indigo-200 bg-white p-6 shadow-sm dark:border-indigo-800 dark:bg-zinc-900">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="flex-1 space-y-3">
                <flux:heading size="lg">{{ __('parking_qr.walk_in_heading') }}</flux:heading>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('parking_qr.walk_in_description') }}</p>
                <p class="text-xs font-mono text-zinc-500 break-all">{{ $walkInScanUrl }}</p>
                <div class="flex flex-wrap gap-2">
                    <flux:button
                        variant="primary"
                        icon="printer"
                        onclick="window.open('{{ route('admin.parking-qr-codes.print-walk-in') }}', '_blank')">
                        {{ __('parking_qr.print_poster') }}
                    </flux:button>
                    <flux:button
                        variant="ghost"
                        icon="arrow-top-right-on-square"
                        :href="route('attendant.scan.walk-in')"
                        target="_blank">
                        {{ __('parking_qr.open_scanner') }}
                    </flux:button>
                </div>
            </div>
            <div class="flex flex-col items-center rounded-xl border border-zinc-200 bg-zinc-50 p-6 dark:border-zinc-700 dark:bg-zinc-950">
                @if($ticketLogo)
                    <img src="{{ asset($ticketLogo) }}" alt="Logo" class="mb-4 h-10 w-auto">
                @endif
                <div class="mb-2 text-center text-[10px] font-bold uppercase tracking-widest text-zinc-400">{{ $convName }}</div>
                <div class="mb-4 text-center text-lg font-black text-zinc-900 dark:text-white">{{ $convLoc }} {{ $convYear }}</div>
                <img
                    src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data={{ urlencode($walkInScanUrl) }}"
                    alt="{{ __('parking_qr.walk_in_qr_alt') }}"
                    class="h-auto w-full max-w-[200px]"
                />
                <div class="mt-4 text-center text-xs font-bold uppercase tracking-widest text-indigo-600 dark:text-indigo-400">
                    {{ __('parking_qr.walk_in_label') }}
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-amber-200 bg-white p-6 shadow-sm dark:border-amber-800 dark:bg-zinc-900">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="flex-1 space-y-3">
                <flux:heading size="lg">{{ __('parking_qr.coach_walk_in_heading') }}</flux:heading>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('parking_qr.coach_walk_in_description') }}</p>
                <p class="text-xs font-mono text-zinc-500 break-all">{{ $coachWalkInScanUrl }}</p>
                <div class="flex flex-wrap gap-2">
                    <flux:button
                        variant="primary"
                        icon="printer"
                        onclick="window.open('{{ route('admin.parking-qr-codes.print-walk-in-coach') }}', '_blank')">
                        {{ __('parking_qr.print_coach_poster') }}
                    </flux:button>
                    <flux:button
                        variant="ghost"
                        icon="arrow-top-right-on-square"
                        :href="route('attendant.scan.walk-in.coach')"
                        target="_blank">
                        {{ __('parking_qr.open_coach_scanner') }}
                    </flux:button>
                </div>
            </div>
            <div class="flex flex-col items-center rounded-xl border border-amber-200 bg-amber-50 p-6 dark:border-amber-800 dark:bg-amber-950/30">
                @if($ticketLogo)
                    <img src="{{ asset($ticketLogo) }}" alt="Logo" class="mb-4 h-10 w-auto">
                @endif
                <div class="mb-2 text-center text-[10px] font-bold uppercase tracking-widest text-zinc-400">{{ $convName }}</div>
                <div class="mb-4 text-center text-lg font-black text-zinc-900 dark:text-white">{{ $convLoc }} {{ $convYear }}</div>
                <img
                    src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data={{ urlencode($coachWalkInScanUrl) }}"
                    alt="{{ __('parking_qr.coach_walk_in_qr_alt') }}"
                    class="h-auto w-full max-w-[200px]"
                />
                <div class="mt-4 text-center text-xs font-bold uppercase tracking-widest text-amber-700 dark:text-amber-300">
                    {{ __('parking_qr.coach_walk_in_label') }}
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-teal-200 bg-white p-6 shadow-sm dark:border-teal-800 dark:bg-zinc-900">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="flex-1 space-y-4">
                <div class="space-y-3">
                    <flux:heading size="lg">{{ __('parking_qr.guest_heading') }}</flux:heading>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('parking_qr.guest_description') }}</p>
                </div>

                @if($carParks->isEmpty())
                    <p class="text-sm text-amber-700 dark:text-amber-300">{{ __('parking_qr.guest_no_parks') }}</p>
                @else
                    <div class="max-w-sm space-y-1">
                        <label for="guestCarParkId" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('parking_qr.guest_park_label') }}
                        </label>
                        <select
                            id="guestCarParkId"
                            wire:model.live="guestCarParkId"
                            class="block w-full rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
                        >
                            @foreach($carParks as $park)
                                <option value="{{ $park->id }}">{{ $park->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if($guestPark)
                        <ul class="space-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                            <li>
                                @if($guestPark->map_image_path)
                                    <span class="text-teal-700 dark:text-teal-300">{{ __('parking_qr.guest_map_ready') }}</span>
                                @else
                                    <span class="text-amber-700 dark:text-amber-300">{{ __('parking_qr.guest_map_missing') }}</span>
                                @endif
                            </li>
                            <li>
                                @if($guestNavUrl)
                                    <span class="text-teal-700 dark:text-teal-300">{{ __('parking_qr.guest_coords_ready') }}</span>
                                @else
                                    <span class="text-amber-700 dark:text-amber-300">{{ __('parking_qr.guest_coords_missing') }}</span>
                                @endif
                            </li>
                        </ul>

                        <div class="flex flex-wrap gap-2">
                            <flux:button
                                variant="primary"
                                icon="printer"
                                onclick="window.open('{{ $guestPrintUrl }}', '_blank')">
                                {{ __('parking_qr.print_guest_handout') }}
                            </flux:button>
                            @if($guestNavUrl)
                                <flux:button
                                    variant="ghost"
                                    icon="arrow-top-right-on-square"
                                    :href="$guestNavUrl"
                                    target="_blank">
                                    {{ __('parking_qr.open_maps') }}
                                </flux:button>
                            @endif
                        </div>
                    @endif
                @endif
            </div>

            <div class="flex flex-col items-center rounded-xl border border-teal-200 bg-teal-50 p-6 dark:border-teal-800 dark:bg-teal-950/30">
                @if($ticketLogo)
                    <img src="{{ asset($ticketLogo) }}" alt="Logo" class="mb-4 h-10 w-auto">
                @endif
                <div class="mb-2 text-center text-[10px] font-bold uppercase tracking-widest text-zinc-400">{{ $convName }}</div>
                <div class="mb-1 text-center text-lg font-black text-zinc-900 dark:text-white">{{ $convLoc }} {{ $convYear }}</div>
                <div class="mb-4 text-center text-sm font-bold text-teal-800 dark:text-teal-200">
                    {{ __('parking_qr.guest_label') }}
                    @if($guestPark)
                        · {{ $guestPark->name }}
                    @endif
                </div>
                @if($guestNavUrl)
                    <img
                        src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data={{ urlencode($guestNavUrl) }}"
                        alt="{{ __('parking_qr.guest_nav_qr_alt') }}"
                        class="h-auto w-full max-w-[200px]"
                    />
                    <div class="mt-4 text-center text-xs font-bold uppercase tracking-widest text-teal-700 dark:text-teal-300">
                        {{ __('parking_qr.guest_nav_label') }}
                    </div>
                @else
                    <div class="flex h-[200px] w-full max-w-[200px] items-center justify-center rounded-lg border border-dashed border-teal-300 bg-white/60 p-4 text-center text-xs text-teal-800 dark:border-teal-700 dark:bg-zinc-900/40 dark:text-teal-200">
                        {{ __('parking_qr.guest_coords_missing') }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-rose-200 bg-white p-6 shadow-sm dark:border-rose-800 dark:bg-zinc-900">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="flex-1 space-y-3">
                <flux:heading size="lg">{{ __('parking_qr.radisson_heading') }}</flux:heading>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('parking_qr.radisson_description') }}</p>
                <p class="text-xs font-mono text-zinc-500 break-all">{{ route('management.radisson-parking-check') }}</p>
                <div class="flex flex-wrap gap-2">
                    <flux:button
                        variant="primary"
                        icon="printer"
                        onclick="window.open('{{ route('admin.parking-qr-codes.print-radisson-info') }}', '_blank')">
                        {{ __('parking_qr.print_radisson_info') }}
                    </flux:button>
                    <flux:button
                        variant="ghost"
                        icon="arrow-top-right-on-square"
                        :href="route('management.radisson-parking-check')"
                        target="_blank">
                        {{ __('parking_qr.open_radisson_check') }}
                    </flux:button>
                </div>
            </div>
            <div class="flex flex-col items-center rounded-xl border border-rose-200 bg-rose-50 p-6 dark:border-rose-800 dark:bg-rose-950/30">
                @if($ticketLogo)
                    <img src="{{ asset($ticketLogo) }}" alt="Logo" class="mb-4 h-10 w-auto">
                @endif
                <div class="mb-2 text-center text-[10px] font-bold uppercase tracking-widest text-zinc-400">{{ $convName }}</div>
                <div class="mb-4 text-center text-lg font-black text-zinc-900 dark:text-white">{{ $convLoc }} {{ $convYear }}</div>
                <img
                    src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data={{ urlencode(route('management.radisson-parking-check')) }}"
                    alt="{{ __('parking_qr.radisson_qr_alt') }}"
                    class="h-auto w-full max-w-[200px]"
                />
                <div class="mt-4 text-center text-xs font-bold uppercase tracking-widest text-rose-700 dark:text-rose-300">
                    {{ __('parking_qr.radisson_label') }}
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <div>
            <flux:heading size="lg">{{ __('parking_qr.ticket_heading') }}</flux:heading>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('parking_qr.ticket_description') }}</p>
        </div>

        <div class="space-y-4">
            <flux:heading size="lg">{{ __('parking_qr.congregation_reference_heading') }}</flux:heading>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('parking_qr.congregation_reference_description') }}</p>
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('parking_qr.search_placeholder') }}" icon="magnifying-glass" />
        </div>

        <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase text-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3 font-medium">{{ __('parking_qr.col_congregation') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('parking_qr.col_code') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('parking_qr.col_car_park') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('parking_qr.col_scan_url') }}</th>
                        <th class="px-4 py-3 text-end font-medium">{{ __('parking_qr.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse($congregations as $congregation)
                        @php
                            $scanUrl = route('attendant.scan', ['code' => $congregation->uuid]);
                        @endphp
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/80" wire:key="cong-qr-{{ $congregation->id }}">
                            <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $congregation->name }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-zinc-600 dark:text-zinc-400">{{ $congregation->uuid }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $congregation->carPark?->name ?? '—' }}</td>
                            <td class="max-w-[200px] truncate px-4 py-3 font-mono text-xs text-zinc-600 dark:text-zinc-400" title="{{ $scanUrl }}">{{ $scanUrl }}</td>
                            <td class="px-4 py-3 text-end">
                                <div class="flex justify-end gap-2">
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        icon="printer"
                                        onclick="window.open('{{ route('admin.congregations.print', $congregation) }}', '_blank')">
                                        {{ __('parking_qr.print') }}
                                    </flux:button>
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        icon="arrow-top-right-on-square"
                                        :href="$scanUrl"
                                        target="_blank">
                                        {{ __('parking_qr.open') }}
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-zinc-500">{{ __('parking_qr.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
