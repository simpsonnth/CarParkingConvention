<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('management.outbound_emails.title') }}</flux:heading>
            <flux:subheading>{{ __('management.outbound_emails.subtitle') }}</flux:subheading>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <select wire:model.live="perPage"
                class="block w-full sm:w-auto rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                <option value="25">25 {{ __('management.outbound_emails.per_page') }}</option>
                <option value="50">50 {{ __('management.outbound_emails.per_page') }}</option>
                <option value="100">100 {{ __('management.outbound_emails.per_page') }}</option>
            </select>
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
                placeholder="{{ __('management.outbound_emails.search') }}" class="w-full min-w-0 sm:min-w-[220px]" />
        </div>
    </div>

    <div class="flex flex-wrap gap-2">
        <flux:button size="sm" variant="{{ $statusFilter === 'all' ? 'primary' : 'ghost' }}"
            wire:click="setStatusFilter('all')">
            {{ __('management.outbound_emails.filter_all') }}
            <span class="ms-1 tabular-nums opacity-80">({{ $total }})</span>
        </flux:button>
        <flux:button size="sm" variant="{{ $statusFilter === 'pending' ? 'primary' : 'ghost' }}"
            wire:click="setStatusFilter('pending')">
            {{ __('management.outbound_emails.filter_pending') }}
            <span class="ms-1 tabular-nums opacity-80">({{ $pendingCount }})</span>
        </flux:button>
        <flux:button size="sm" variant="{{ $statusFilter === 'sent' ? 'primary' : 'ghost' }}"
            wire:click="setStatusFilter('sent')">
            {{ __('management.outbound_emails.filter_sent') }}
            <span class="ms-1 tabular-nums opacity-80">({{ $sentCount }})</span>
        </flux:button>
        <flux:button size="sm" variant="{{ $statusFilter === 'failed' ? 'primary' : 'ghost' }}"
            wire:click="setStatusFilter('failed')">
            {{ __('management.outbound_emails.filter_failed') }}
            <span class="ms-1 tabular-nums opacity-80">({{ $failedCount }})</span>
        </flux:button>
        <flux:button size="sm" variant="{{ $statusFilter === 'delivered' ? 'primary' : 'ghost' }}"
            wire:click="setStatusFilter('delivered')">
            {{ __('management.outbound_emails.filter_delivered') }}
            <span class="ms-1 tabular-nums opacity-80">({{ $deliveredCount }})</span>
        </flux:button>
        <flux:button size="sm" variant="{{ $statusFilter === 'bounced' ? 'primary' : 'ghost' }}"
            wire:click="setStatusFilter('bounced')">
            {{ __('management.outbound_emails.filter_bounced') }}
            <span class="ms-1 tabular-nums opacity-80">({{ $bouncedCount }})</span>
        </flux:button>
        <flux:button size="sm" variant="{{ $statusFilter === 'complained' ? 'primary' : 'ghost' }}"
            wire:click="setStatusFilter('complained')">
            {{ __('management.outbound_emails.filter_complained') }}
            <span class="ms-1 tabular-nums opacity-80">({{ $complainedCount }})</span>
        </flux:button>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900/50">
        {{ __('management.outbound_emails.webhook_help', ['url' => url('/webhooks/resend')]) }}
    </div>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full min-w-[960px] text-left text-sm">
            <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
                <tr>
                    <th class="px-4 py-3">{{ __('management.outbound_emails.col_when') }}</th>
                    <th class="px-4 py-3">{{ __('management.outbound_emails.col_type') }}</th>
                    <th class="px-4 py-3">{{ __('management.outbound_emails.col_to') }}</th>
                    <th class="px-4 py-3">{{ __('management.outbound_emails.col_status') }}</th>
                    <th class="px-4 py-3">{{ __('management.outbound_emails.col_provider') }}</th>
                    <th class="px-4 py-3">{{ __('management.outbound_emails.col_error') }}</th>
                    <th class="px-4 py-3 text-end">{{ __('management.outbound_emails.col_actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-800">
                @forelse ($rows as $row)
                    <tr wire:key="oe-{{ $row->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        <td class="px-4 py-3 whitespace-nowrap text-zinc-600 dark:text-zinc-300">
                            {{ ($row->sent_at ?? $row->created_at)?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                        </td>
                        <td class="px-4 py-3 text-zinc-800 dark:text-zinc-100">
                            {{ __('management.outbound_emails.type_'.$row->type) }}
                        </td>
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">
                            {{ $row->to_email }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if ($row->status === 'pending')
                                <flux:badge color="amber">{{ __('management.outbound_emails.status_pending') }}</flux:badge>
                            @elseif ($row->status === 'sent')
                                <flux:badge color="sky">{{ __('management.outbound_emails.status_sent') }}</flux:badge>
                            @else
                                <flux:badge color="rose">{{ __('management.outbound_emails.status_failed') }}</flux:badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if ($row->provider_status === 'delivered')
                                <flux:badge color="green">{{ __('management.outbound_emails.provider_delivered') }}</flux:badge>
                            @elseif ($row->provider_status === 'bounced')
                                <flux:badge color="rose">{{ __('management.outbound_emails.provider_bounced') }}</flux:badge>
                            @elseif ($row->provider_status === 'complained')
                                <flux:badge color="orange">{{ __('management.outbound_emails.provider_complained') }}</flux:badge>
                            @elseif ($row->provider_status === 'delayed')
                                <flux:badge color="amber">{{ __('management.outbound_emails.provider_delayed') }}</flux:badge>
                            @elseif ($row->provider_status === 'sent')
                                <flux:badge color="zinc">{{ __('management.outbound_emails.provider_sent') }}</flux:badge>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 max-w-[240px] truncate text-zinc-500 dark:text-zinc-400"
                            title="{{ $row->provider_detail ?: $row->last_error }}">
                            {{ $row->provider_detail ?: ($row->last_error ?: '—') }}
                        </td>
                        <td class="px-4 py-3 text-end">
                            <button type="button" wire:click="openDetail({{ $row->id }})"
                                class="inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-700">
                                {{ __('management.outbound_emails.view') }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-zinc-500 dark:text-zinc-400">
                            {{ __('management.outbound_emails.empty') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $rows->links() }}
    </div>

    <flux:modal wire:model="detailModalOpen" class="w-[calc(100vw-2rem)] max-w-2xl">
        @if ($viewing)
            <div class="space-y-5">
                <div>
                    <flux:heading size="lg">{{ __('management.outbound_emails.detail_title') }}</flux:heading>
                    <flux:subheading>{{ $viewing->to_email }}</flux:subheading>
                </div>

                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-zinc-500">{{ __('management.outbound_emails.col_type') }}</dt>
                        <dd class="font-medium">{{ __('management.outbound_emails.type_'.$viewing->type) }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500">{{ __('management.outbound_emails.col_status') }}</dt>
                        <dd class="font-medium">{{ __('management.outbound_emails.status_'.$viewing->status) }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500">{{ __('management.outbound_emails.col_provider') }}</dt>
                        <dd class="font-medium">{{ $viewing->provider_status ? __('management.outbound_emails.provider_'.$viewing->provider_status) : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500">{{ __('management.outbound_emails.col_attempts') }}</dt>
                        <dd class="font-medium tabular-nums">{{ $viewing->attempts }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500">{{ __('management.outbound_emails.col_sent_at') }}</dt>
                        <dd class="font-medium">{{ $viewing->sent_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s') ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500">{{ __('management.outbound_emails.col_delivered_at') }}</dt>
                        <dd class="font-medium">{{ $viewing->delivered_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s') ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500">{{ __('management.outbound_emails.col_bounced_at') }}</dt>
                        <dd class="font-medium">{{ $viewing->bounced_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s') ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500">{{ __('management.outbound_emails.col_provider_id') }}</dt>
                        <dd class="font-mono text-xs break-all">{{ $viewing->provider_email_id ?: '—' }}</dd>
                    </div>
                </dl>

                @if ($viewing->last_error || $viewing->provider_detail)
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 text-sm dark:border-zinc-700 dark:bg-zinc-900/50">
                        <p class="font-medium text-zinc-800 dark:text-zinc-100">{{ __('management.outbound_emails.col_error') }}</p>
                        <p class="mt-1 whitespace-pre-wrap text-zinc-600 dark:text-zinc-300">{{ $viewing->provider_detail ?: $viewing->last_error }}</p>
                    </div>
                @endif

                <div>
                    <h3 class="mb-2 text-sm font-semibold text-zinc-900 dark:text-white">
                        {{ __('management.outbound_emails.events_heading') }}
                    </h3>
                    @if ($viewing->events->isEmpty())
                        <p class="text-sm text-zinc-500">{{ __('management.outbound_emails.events_empty') }}</p>
                    @else
                        <ul class="space-y-2 text-sm">
                            @foreach ($viewing->events as $event)
                                <li class="rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <span class="font-medium">{{ $event->event_type }}</span>
                                        <span class="text-xs text-zinc-500">
                                            {{ $event->occurred_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s') }}
                                        </span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div class="flex justify-end">
                    <flux:button variant="ghost" wire:click="closeDetail">
                        {{ __('management.outbound_emails.close') }}
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
