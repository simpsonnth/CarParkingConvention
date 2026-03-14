<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('registrations.trash_title') }}</flux:heading>
            <flux:subheading>{{ __('registrations.trash_subtitle') }}</flux:subheading>
        </div>
        <a href="{{ route('admin.registrations') }}" wire:navigate class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700">
            <flux:icon name="arrow-left" class="size-4" />
            {{ __('registrations.back_to_registrations') }}
        </a>
    </div>

    @if(count($selectedIds) > 0)
        <div class="flex flex-wrap items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800/50">
            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300 basis-full sm:basis-auto">{{ count($selectedIds) }} {{ __('registrations.selected') }}</span>
            <flux:button variant="primary" size="sm" wire:click="restoreSelected">
                {{ __('registrations.restore_selected') }}
            </flux:button>
            <flux:button variant="danger" size="sm" wire:click="forceDeleteSelected" wire:confirm="{{ __('registrations.confirm_permanent_delete_selected') }}">
                {{ __('registrations.permanent_delete_selected') }}
            </flux:button>
            <flux:button variant="ghost" size="sm" wire:click="$set('selectedIds', [])">{{ __('registrations.cancel') }}</flux:button>
        </div>
    @endif

    <flux:separator />

    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700 -mx-4 sm:mx-0">
        <table class="w-full min-w-[640px] text-left text-sm">
            <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
                <tr>
                    <th class="w-10 px-4 py-3">
                        <input type="checkbox" wire:click="toggleSelectAll"
                            class="rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                    </th>
                    <th class="px-6 py-3">{{ __('registrations.deleted_at') }}</th>
                    <th class="px-6 py-3">{{ __('registrations.name') }}</th>
                    <th class="px-6 py-3">{{ __('registrations.congregation') }}</th>
                    <th class="px-6 py-3">{{ __('registrations.type') }}</th>
                    <th class="px-6 py-3">{{ __('registrations.vehicle_reg') }}</th>
                    <th class="px-6 py-3 text-end">{{ __('registrations.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700 bg-white dark:bg-zinc-800">
                @forelse($registrations as $reg)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
                        <td class="w-10 px-4 py-4">
                            <input type="checkbox" wire:model="selectedIds" value="{{ $reg->id }}"
                                class="rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                        </td>
                        <td class="px-6 py-4 text-zinc-500 whitespace-nowrap">
                            {{ $reg->deleted_at?->format('d M Y H:i') }}
                        </td>
                        <td class="px-6 py-4 font-medium text-zinc-900 dark:text-white">{{ $reg->name }}</td>
                        <td class="px-6 py-4 text-zinc-600 dark:text-zinc-300">{{ $reg->congregation }}</td>
                        <td class="px-6 py-4">
                            <flux:badge color="zinc">{{ ucfirst($reg->vehicle_type ?? 'car') }}</flux:badge>
                        </td>
                        <td class="px-6 py-4 font-mono text-zinc-600 dark:text-zinc-300">{{ $reg->vehicle_registration ?? '—' }}</td>
                        <td class="px-6 py-4 text-end">
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                <flux:menu>
                                    <flux:menu.item wire:click="restore({{ $reg->id }})" icon="arrow-path">{{ __('registrations.restore') }}</flux:menu.item>
                                    <flux:menu.item wire:click="forceDelete({{ $reg->id }})" wire:confirm="{{ __('registrations.confirm_permanent_delete_one') }}" icon="trash" variant="danger">{{ __('registrations.permanent_delete') }}</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-zinc-500">
                            <div class="flex flex-col items-center justify-center">
                                <flux:icon name="trash" class="size-10 text-zinc-300 mb-2" />
                                <p>{{ __('registrations.no_trashed') }}</p>
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
</div>
