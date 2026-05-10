<div class="mx-auto max-w-5xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('routes_list.title') }}</flux:heading>
            <flux:subheading class="mt-1">{{ __('routes_list.subtitle') }}</flux:subheading>
        </div>
        <flux:button :href="route('dashboard')" variant="ghost" icon="arrow-left" wire:navigate size="sm">
            {{ __('routes_list.back_dashboard') }}
        </flux:button>
    </div>

    <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('routes_list.intro') }}</p>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <table class="w-full min-w-[640px] text-left text-sm">
            <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase text-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-400">
                <tr>
                    <th class="px-4 py-3 font-medium">{{ __('routes_list.col_page') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('routes_list.col_route_name') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('routes_list.col_path') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('routes_list.col_url') }}</th>
                    <th class="px-4 py-3 text-end font-medium">{{ __('routes_list.open') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse($entries as $row)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/80">
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $row['label'] }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-zinc-600 dark:text-zinc-400">{{ $row['route_name'] }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-zinc-700 dark:text-zinc-300">{{ $row['path'] }}</td>
                        <td class="max-w-[220px] truncate px-4 py-3 font-mono text-xs text-zinc-600 dark:text-zinc-400" title="{{ $row['url'] }}">{{ $row['url'] }}</td>
                        <td class="px-4 py-3 text-end">
                            <flux:link :href="$row['url']" target="_blank" rel="noopener noreferrer" class="text-sm font-semibold">
                                {{ __('routes_list.open') }}
                            </flux:link>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center text-zinc-500">{{ __('routes_list.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('routes_list.locale_note') }}</p>
</div>
