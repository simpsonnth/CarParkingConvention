<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('management.parking_incidents.title') }}</flux:heading>
            <flux:subheading>{{ __('management.parking_incidents.subtitle') }}</flux:subheading>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.parking-incidents.export') }}"
                class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700"
                download>
                <flux:icon name="arrow-down-tray" class="size-4" />
                {{ __('management.parking_incidents.export') }}
            </a>
            <select wire:model.live="perPage"
                class="block w-full sm:w-auto rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                <option value="25">25 {{ __('management.parking_incidents.per_page') }}</option>
                <option value="50">50 {{ __('management.parking_incidents.per_page') }}</option>
                <option value="100">100 {{ __('management.parking_incidents.per_page') }}</option>
            </select>
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
                placeholder="{{ __('management.parking_incidents.search') }}" class="w-full min-w-0 sm:min-w-[220px]" />
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900/50">
        {{ __('management.parking_incidents.total', ['count' => $total]) }}
    </div>

    <flux:separator />

    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full min-w-[800px] text-left text-sm">
            <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
                <tr>
                    <th class="px-4 py-3">{{ __('management.parking_incidents.col_submitted') }}</th>
                    <th class="px-4 py-3">{{ __('management.parking_incidents.col_type') }}</th>
                    <th class="px-4 py-3">{{ __('management.parking_incidents.col_occurred') }}</th>
                    <th class="px-4 py-3">{{ __('management.parking_incidents.col_reporter') }}</th>
                    <th class="px-4 py-3">{{ __('management.parking_incidents.col_location') }}</th>
                    <th class="px-4 py-3">{{ __('management.parking_incidents.col_actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($rows as $row)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        <td class="px-4 py-3 whitespace-nowrap">{{ $row->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3">
                            {{ $row->type === 'accident' ? __('management.parking_incidents.type_accident') : __('management.parking_incidents.type_near_miss') }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $row->occurred_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3">{{ $row->reporter_name }}</td>
                        <td class="px-4 py-3">{{ Str::limit($row->location, 40) }}</td>
                        <td class="px-4 py-3">
                            <flux:button size="sm" variant="ghost" wire:click="openDetail({{ $row->id }})">
                                {{ __('management.parking_incidents.view') }}
                            </flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-zinc-500">{{ __('management.parking_incidents.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $rows->links() }}</div>

    <flux:modal wire:model="detailModalOpen" class="max-w-2xl">
        @if($viewing)
            <flux:heading size="lg">{{ __('management.parking_incidents.detail_title') }}</flux:heading>
            <div class="mt-4 space-y-3 text-sm">
                <p><span class="font-semibold">{{ __('management.parking_incidents.col_type') }}:</span>
                    {{ $viewing->type === 'accident' ? __('management.parking_incidents.type_accident') : __('management.parking_incidents.type_near_miss') }}</p>
                <p><span class="font-semibold">{{ __('management.parking_incidents.col_occurred') }}:</span>
                    {{ $viewing->occurred_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</p>
                <p><span class="font-semibold">{{ __('management.parking_incidents.col_location') }}:</span> {{ $viewing->location }}</p>
                @if($viewing->carPark)
                    <p><span class="font-semibold">{{ __('management.parking_incidents.detail_car_park') }}:</span> {{ $viewing->carPark->name }}</p>
                @endif
                <p><span class="font-semibold">{{ __('management.parking_incidents.detail_description') }}:</span></p>
                <p class="whitespace-pre-wrap rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900">{{ $viewing->description }}</p>
                @if($viewing->actions_taken)
                    <p><span class="font-semibold">{{ __('management.parking_incidents.detail_actions_taken') }}:</span></p>
                    <p class="whitespace-pre-wrap rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900">{{ $viewing->actions_taken }}</p>
                @endif
                <p><span class="font-semibold">{{ __('management.parking_incidents.detail_injury') }}:</span>
                    {{ $viewing->injury_reported ? __('management.parking_incidents.yes') : __('management.parking_incidents.no') }}</p>
                @if($viewing->severity)
                    <p><span class="font-semibold">{{ __('management.parking_incidents.detail_severity') }}:</span>
                        {{ __('management.parking_incidents.severity_'.$viewing->severity) }}</p>
                @endif
                <p><span class="font-semibold">{{ __('management.parking_incidents.col_reporter') }}:</span> {{ $viewing->reporter_name }}</p>
                <p><span class="font-semibold">{{ __('management.parking_incidents.detail_email') }}:</span> {{ $viewing->reporter_email }}</p>
                <p><span class="font-semibold">{{ __('management.parking_incidents.detail_phone') }}:</span> {{ $viewing->reporter_phone }}</p>
            </div>
            <div class="mt-6 flex justify-end">
                <flux:button variant="ghost" wire:click="closeDetail">{{ __('management.parking_incidents.close') }}</flux:button>
            </div>
        @endif
    </flux:modal>
</div>
