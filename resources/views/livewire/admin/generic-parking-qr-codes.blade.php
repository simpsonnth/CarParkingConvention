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
