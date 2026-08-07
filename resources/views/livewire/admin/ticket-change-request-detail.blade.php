<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('admin.ticket-change-requests') }}" wire:navigate
                class="mb-2 inline-flex items-center gap-1 text-sm font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">
                <flux:icon name="arrow-left" class="size-4" />
                {{ __('management.ticket_change_requests.back_to_list') }}
            </a>
            <flux:heading size="xl">{{ __('management.ticket_change_requests.detail_title') }}</flux:heading>
            <flux:subheading>
                {{ $ticketChangeRequest->created_at?->timezone(config('app.timezone'))->format('d M Y H:i') }}
            </flux:subheading>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <flux:button variant="ghost" wire:click="copyPersonEmailSummary">
                {{ __('management.ticket_change_requests.copy_email_summary') }}
            </flux:button>
            @if ($congregationPeopleCount > 1)
                <flux:button variant="ghost" wire:click="copyCongregationEmailSummary">
                    {{ __('management.ticket_change_requests.copy_congregation_summary') }}
                </flux:button>
            @endif
            @can('ticket-change-requests.manage')
                @if ($ticketChangeRequest->isPending())
                    @if ($ticketChangeRequest->requiresApproval())
                        @if ($canApprove)
                            <flux:button variant="primary" wire:click="openApproveModal">
                                {{ __('management.ticket_change_requests.approve') }}
                            </flux:button>
                        @endif
                        @if (filled($ticketChangeRequest->notification_email))
                            <flux:button variant="danger" wire:click="decline"
                                wire:confirm="{{ __('management.ticket_change_requests.confirm_decline') }}">
                                {{ __('management.ticket_change_requests.decline') }}
                            </flux:button>
                        @endif
                        <flux:button variant="ghost" wire:click="closeWithoutApplying"
                            wire:confirm="{{ __('management.ticket_change_requests.confirm_close_without_applying') }}">
                            {{ __('management.ticket_change_requests.close_without_applying') }}
                        </flux:button>
                    @else
                        <flux:button variant="primary" wire:click="markCompleted"
                            wire:confirm="{{ __('management.ticket_change_requests.confirm_complete') }}">
                            {{ __('management.ticket_change_requests.mark_completed') }}
                        </flux:button>
                        @if (filled($ticketChangeRequest->notification_email))
                            <flux:button variant="danger" wire:click="decline"
                                wire:confirm="{{ __('management.ticket_change_requests.confirm_decline') }}">
                                {{ __('management.ticket_change_requests.decline') }}
                            </flux:button>
                        @endif
                    @endif
                @else
                    <flux:button variant="ghost" wire:click="markPending">
                        {{ __('management.ticket_change_requests.mark_pending') }}
                    </flux:button>
                @endif
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
        <div class="space-y-6 lg:col-span-3">
            @if ($ticketChangeRequest->isPending() && $ticketChangeRequest->requiresApproval() && ! $canApprove)
                <div class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100">
                    {{ __('management.ticket_change_requests.cannot_approve_missing_registration') }}
                    {{ __('management.ticket_change_requests.close_without_applying_hint') }}
                </div>
            @endif

            @if ($sameTicketRequests->isNotEmpty())
                <section class="rounded-xl border border-rose-200 bg-rose-50 p-5 dark:border-rose-900 dark:bg-rose-950/30">
                    <h2 class="text-sm font-semibold text-rose-900 dark:text-rose-100">
                        {{ __('management.ticket_change_requests.same_ticket_heading') }}
                    </h2>
                    <p class="mt-1 text-xs text-rose-800/80 dark:text-rose-200/80">
                        {{ __('management.ticket_change_requests.same_ticket_help') }}
                    </p>
                    <ul class="mt-4 space-y-3">
                        @foreach ($sameTicketRequests as $sibling)
                            <li wire:key="same-ticket-{{ $sibling->id }}" class="rounded-lg border border-rose-100 bg-white px-4 py-3 dark:border-rose-900 dark:bg-zinc-900">
                                <div class="flex flex-wrap items-center gap-2 text-xs text-zinc-500">
                                    <span>#{{ $sibling->id }}</span>
                                    @if ($sibling->isStructured())
                                        <flux:badge size="sm" color="zinc">{{ __('management.ticket_change_requests.type_'.$sibling->request_type) }}</flux:badge>
                                    @endif
                                    @if ($sibling->isPending())
                                        <flux:badge size="sm" color="amber">{{ __('management.ticket_change_requests.status_pending') }}</flux:badge>
                                    @else
                                        <flux:badge size="sm" color="green">{{ __('management.ticket_change_requests.status_completed') }}</flux:badge>
                                    @endif
                                    <span>{{ $sibling->created_at?->timezone(config('app.timezone'))->format('d M Y H:i') }}</span>
                                </div>
                                <p class="mt-2 whitespace-pre-wrap text-sm text-zinc-800 dark:text-zinc-200">{{ $sibling->notes }}</p>
                                <a href="{{ route('admin.ticket-change-requests.show', $sibling) }}" wire:navigate
                                    class="mt-2 inline-block text-sm font-semibold text-indigo-600 hover:underline dark:text-indigo-400">
                                    {{ __('management.ticket_change_requests.open_request') }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                <dl class="space-y-4 text-sm">
                    <div>
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('management.ticket_change_requests.col_status') }}</dt>
                        <dd class="mt-1">
                            @if ($ticketChangeRequest->isPending())
                                <flux:badge color="amber">{{ __('management.ticket_change_requests.status_pending') }}</flux:badge>
                            @else
                                <flux:badge color="green">{{ __('management.ticket_change_requests.status_completed') }}</flux:badge>
                                @if ($ticketChangeRequest->actioned_at)
                                    <div class="mt-1 text-xs text-zinc-500">
                                        {{ $ticketChangeRequest->actioned_at->timezone(config('app.timezone'))->format('d M Y H:i') }}
                                        @if ($ticketChangeRequest->actionedByUser)
                                            · {{ $ticketChangeRequest->actionedByUser->name }}
                                        @elseif ($ticketChangeRequest->wasAutoCompleted())
                                            · {{ __('management.ticket_change_requests.completed_by_auto') }}
                                        @endif
                                    </div>
                                @endif
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('management.ticket_change_requests.col_type') }}</dt>
                        <dd class="mt-1">
                            @if ($ticketChangeRequest->isStructured())
                                <flux:badge color="zinc">{{ __('management.ticket_change_requests.type_'.$ticketChangeRequest->request_type) }}</flux:badge>
                            @else
                                {{ __('management.ticket_change_requests.type_legacy') }}
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('management.ticket_change_requests.col_name') }}</dt>
                        <dd class="mt-1 text-base font-semibold text-zinc-900 dark:text-white">{{ $ticketChangeRequest->name }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('management.ticket_change_requests.col_congregation') }}</dt>
                        <dd class="mt-1 text-zinc-900 dark:text-white">{{ $ticketChangeRequest->congregation }}</dd>
                    </div>
                    @if (filled($ticketChangeRequest->notification_email))
                        <div>
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('management.ticket_change_requests.col_notification_email') }}</dt>
                            <dd class="mt-1 text-zinc-900 dark:text-white">{{ $ticketChangeRequest->notification_email }}</dd>
                        </div>
                    @endif
                    @if ($ticketChangeRequest->parkingRegistration)
                        <div>
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('management.ticket_change_requests.col_ticket') }}</dt>
                            <dd class="mt-1 text-zinc-900 dark:text-white tabular-nums">
                                #{{ $ticketChangeRequest->parkingRegistration->ticketNumber() }}
                                @if (filled($ticketChangeRequest->parkingRegistration->vehicle_registration))
                                    · {{ $ticketChangeRequest->parkingRegistration->vehicle_registration }}
                                @endif
                                @if ($registrationCancelled)
                                    <flux:badge size="sm" color="rose" class="ms-1">{{ __('management.ticket_change_requests.registration_cancelled_badge') }}</flux:badge>
                                @endif
                            </dd>
                        </div>
                    @elseif ($ticketChangeRequest->parking_registration_id)
                        <div>
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('management.ticket_change_requests.col_ticket') }}</dt>
                            <dd class="mt-1">
                                <flux:badge color="rose">{{ __('management.ticket_change_requests.registration_missing_badge') }}</flux:badge>
                            </dd>
                        </div>
                    @endif
                    <div>
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('management.ticket_change_requests.col_notes') }}</dt>
                        <dd class="mt-2 whitespace-pre-wrap rounded-lg bg-zinc-50 p-4 text-zinc-900 dark:bg-zinc-900/60 dark:text-zinc-100">{{ $ticketChangeRequest->notes }}</dd>
                    </div>

                    @if (is_array($ticketChangeRequest->payload) && $ticketChangeRequest->payload !== [])
                        <div>
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('management.ticket_change_requests.payload_heading') }}</dt>
                            <dd class="mt-2 space-y-2 rounded-lg bg-zinc-50 p-4 dark:bg-zinc-900/60">
                                @if (! empty($ticketChangeRequest->payload['changes']) && is_array($ticketChangeRequest->payload['changes']))
                                    @foreach ($ticketChangeRequest->payload['changes'] as $field => $value)
                                        <div class="text-sm text-zinc-800 dark:text-zinc-200">
                                            <span class="font-medium">{{ __('management.ticket_change_requests.field_'.$field) }}:</span>
                                            @if (is_array($ticketChangeRequest->before_snapshot) && array_key_exists($field, $ticketChangeRequest->before_snapshot))
                                                <span class="text-zinc-500 line-through me-1">{{ $ticketChangeRequest->before_snapshot[$field] ?? '—' }}</span>
                                            @endif
                                            <span>{{ is_scalar($value) || $value === null ? ($value ?? '—') : json_encode($value) }}</span>
                                        </div>
                                    @endforeach
                                @endif
                                @if (! empty($ticketChangeRequest->payload['addition']) && is_array($ticketChangeRequest->payload['addition']))
                                    @foreach ($ticketChangeRequest->payload['addition'] as $field => $value)
                                        <div class="text-sm text-zinc-800 dark:text-zinc-200">
                                            <span class="font-medium">{{ __('management.ticket_change_requests.field_'.$field) }}:</span>
                                            @if (is_array($value))
                                                {{ implode(', ', $value) }}
                                            @else
                                                {{ $value ?? '—' }}
                                            @endif
                                        </div>
                                    @endforeach
                                @endif
                                @if (! empty($ticketChangeRequest->payload['approved_car_park_id']))
                                    <div class="text-sm text-zinc-800 dark:text-zinc-200">
                                        <span class="font-medium">{{ __('management.ticket_change_requests.approved_car_park') }}:</span>
                                        {{ $this->carParks->firstWhere('id', $ticketChangeRequest->payload['approved_car_park_id'])?->name
                                            ?? ('#'.$ticketChangeRequest->payload['approved_car_park_id']) }}
                                    </div>
                                @endif
                            </dd>
                        </div>
                    @endif
                </dl>
            </section>

            @can('ticket-change-requests.manage')
                <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800 space-y-3">
                    <div>
                        <label for="adminNotes" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('management.ticket_change_requests.admin_notes') }}
                            <span class="font-normal text-zinc-500">({{ __('management.ticket_change_requests.admin_notes_optional') }})</span>
                        </label>
                        <textarea wire:model="adminNotes" id="adminNotes" rows="5"
                            class="mt-2 block w-full rounded-lg border-zinc-200 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                            placeholder="{{ __('management.ticket_change_requests.admin_notes_placeholder') }}"></textarea>
                        @error('adminNotes')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('management.ticket_change_requests.admin_notes_help') }}</p>
                    </div>
                    <div class="flex justify-end">
                        <flux:button variant="primary" wire:click="saveAdminNotes">
                            {{ __('management.ticket_change_requests.save_admin_notes') }}
                        </flux:button>
                    </div>
                    @error('decline')
                        <p class="text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </section>
            @else
                @if (filled($ticketChangeRequest->admin_notes))
                    <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                        <h2 class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('management.ticket_change_requests.admin_notes') }}</h2>
                        <p class="mt-2 whitespace-pre-wrap text-sm text-zinc-900 dark:text-white">{{ $ticketChangeRequest->admin_notes }}</p>
                    </section>
                @endif
            @endcan
        </div>

        <aside class="space-y-4 lg:col-span-2">
            <section class="rounded-xl border border-sky-200 bg-sky-50 p-5 dark:border-sky-900 dark:bg-sky-950/30">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <h2 class="text-sm font-semibold text-sky-900 dark:text-sky-100">
                            {{ __('management.ticket_change_requests.related_heading', [
                                'count' => $relatedRequests->count() + 1,
                                'pending' => $relatedPendingCount,
                            ]) }}
                        </h2>
                        <p class="mt-1 text-xs text-sky-800/80 dark:text-sky-200/80">
                            {{ __('management.ticket_change_requests.related_page_help') }}
                        </p>
                    </div>
                </div>

                <ul class="mt-4 space-y-3">
                    <li class="rounded-lg border border-sky-100 bg-white px-4 py-3 dark:border-sky-900 dark:bg-zinc-900">
                        <div class="flex flex-wrap items-center gap-2 text-xs text-zinc-500">
                            <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ __('management.ticket_change_requests.related_this_request') }}</span>
                            @if ($ticketChangeRequest->isPending())
                                <flux:badge size="sm" color="amber">{{ __('management.ticket_change_requests.status_pending') }}</flux:badge>
                            @else
                                <flux:badge size="sm" color="green">{{ __('management.ticket_change_requests.status_completed') }}</flux:badge>
                            @endif
                        </div>
                        <p class="mt-2 whitespace-pre-wrap text-sm text-zinc-800 dark:text-zinc-200">{{ $ticketChangeRequest->notes }}</p>
                    </li>

                    @forelse ($relatedRequests as $related)
                        <li wire:key="related-{{ $related->id }}" class="rounded-lg border border-sky-100 bg-white px-4 py-3 dark:border-sky-900 dark:bg-zinc-900">
                            <div class="flex flex-wrap items-center gap-2 text-xs text-zinc-500">
                                <span>{{ $related->created_at?->timezone(config('app.timezone'))->format('d M Y H:i') }}</span>
                                @if ($related->isPending())
                                    <flux:badge size="sm" color="amber">{{ __('management.ticket_change_requests.status_pending') }}</flux:badge>
                                @else
                                    <flux:badge size="sm" color="green">{{ __('management.ticket_change_requests.status_completed') }}</flux:badge>
                                @endif
                            </div>
                            <p class="mt-2 whitespace-pre-wrap text-sm text-zinc-800 dark:text-zinc-200">{{ $related->notes }}</p>
                            @if (filled($related->admin_notes))
                                <p class="mt-2 text-xs text-indigo-700 dark:text-indigo-300">
                                    {{ __('management.ticket_change_requests.admin_notes_preview') }}: {{ $related->admin_notes }}
                                </p>
                            @endif
                            <a href="{{ route('admin.ticket-change-requests.show', $related) }}" wire:navigate
                                class="mt-2 inline-block text-sm font-semibold text-indigo-600 hover:underline dark:text-indigo-400">
                                {{ __('management.ticket_change_requests.open_request') }}
                            </a>
                        </li>
                    @empty
                        <li class="text-sm text-sky-900/70 dark:text-sky-100/70">
                            {{ __('management.ticket_change_requests.related_none_other') }}
                        </li>
                    @endforelse
                </ul>

                @can('ticket-change-requests.manage')
                    @if ($relatedPendingCount > 1)
                        <div class="mt-4">
                            <flux:button variant="primary" wire:click="markAllRelatedPendingCompleted"
                                wire:confirm="{{ __('management.ticket_change_requests.confirm_complete_all', ['count' => $relatedPendingCount]) }}">
                                {{ __('management.ticket_change_requests.mark_all_completed', ['count' => $relatedPendingCount]) }}
                            </flux:button>
                        </div>
                    @endif
                @endcan
            </section>

            @if ($congregationPeopleCount > 1)
                <section class="rounded-xl border border-violet-200 bg-violet-50 p-5 dark:border-violet-900 dark:bg-violet-950/30">
                    <div>
                        <h2 class="text-sm font-semibold text-violet-900 dark:text-violet-100">
                            {{ __('management.ticket_change_requests.congregation_heading', [
                                'congregation' => $ticketChangeRequest->congregation,
                                'people' => $congregationPeopleCount,
                                'pending' => $congregationPendingCount,
                            ]) }}
                        </h2>
                        <p class="mt-1 text-xs text-violet-800/80 dark:text-violet-200/80">
                            {{ __('management.ticket_change_requests.congregation_page_help') }}
                        </p>
                    </div>

                    <ul class="mt-4 space-y-3">
                        @foreach ($congregationRequests as $congRequest)
                            <li wire:key="cong-{{ $congRequest->id }}" class="rounded-lg border border-violet-100 bg-white px-4 py-3 dark:border-violet-900 dark:bg-zinc-900">
                                <div class="flex flex-wrap items-center gap-2 text-xs text-zinc-500">
                                    <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $congRequest->name }}</span>
                                    <span>{{ $congRequest->created_at?->timezone(config('app.timezone'))->format('d M Y H:i') }}</span>
                                    @if ($congRequest->isPending())
                                        <flux:badge size="sm" color="amber">{{ __('management.ticket_change_requests.status_pending') }}</flux:badge>
                                    @else
                                        <flux:badge size="sm" color="green">{{ __('management.ticket_change_requests.status_completed') }}</flux:badge>
                                    @endif
                                </div>
                                <p class="mt-2 whitespace-pre-wrap text-sm text-zinc-800 dark:text-zinc-200">{{ $congRequest->notes }}</p>
                                @if (filled($congRequest->admin_notes))
                                    <p class="mt-2 text-xs text-indigo-700 dark:text-indigo-300">
                                        {{ __('management.ticket_change_requests.admin_notes_preview') }}: {{ $congRequest->admin_notes }}
                                    </p>
                                @endif
                                <a href="{{ route('admin.ticket-change-requests.show', $congRequest) }}" wire:navigate
                                    class="mt-2 inline-block text-sm font-semibold text-indigo-600 hover:underline dark:text-indigo-400">
                                    {{ __('management.ticket_change_requests.open_request') }}
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    @can('ticket-change-requests.manage')
                        @if ($congregationPendingCount > 1)
                            <div class="mt-4">
                                <flux:button variant="primary" wire:click="markAllCongregationPendingCompleted"
                                    wire:confirm="{{ __('management.ticket_change_requests.confirm_complete_congregation', ['count' => $congregationPendingCount]) }}">
                                    {{ __('management.ticket_change_requests.mark_all_congregation_completed', ['count' => $congregationPendingCount]) }}
                                </flux:button>
                            </div>
                        @endif
                    @endcan
                </section>
            @endif
        </aside>
    </div>

    <flux:modal wire:model="approveModalOpen" class="w-[calc(100vw-2rem)] max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('management.ticket_change_requests.approve_title') }}</flux:heading>
                <flux:subheading>
                    @if ($ticketChangeRequest->request_type === 'cancellation')
                        {{ __('management.ticket_change_requests.approve_cancel_help') }}
                    @elseif ($ticketChangeRequest->request_type === 'car_park_change')
                        {{ __('management.ticket_change_requests.approve_car_park_help') }}
                    @else
                        {{ __('management.ticket_change_requests.approve_addition_help') }}
                    @endif
                </flux:subheading>
            </div>

            @if (in_array($ticketChangeRequest->request_type, ['car_park_change', 'addition'], true))
                <div>
                    <label for="approveCarParkId" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('management.ticket_change_requests.approve_car_park') }}
                        <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="approveCarParkId" id="approveCarParkId"
                        class="mt-2 block w-full rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                        <option value="">{{ __('management.ticket_change_requests.select_car_park') }}</option>
                        @foreach ($this->carParks as $park)
                            <option value="{{ $park->id }}">{{ $park->name }}</option>
                        @endforeach
                    </select>
                    @error('approveCarParkId')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
            @endif

            @error('approve')
                <span class="text-red-500 text-xs">{{ $message }}</span>
            @enderror

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="closeApproveModal">
                    {{ __('management.ticket_change_requests.close') }}
                </flux:button>
                <flux:button variant="primary" wire:click="approve"
                    wire:confirm="{{ $ticketChangeRequest->request_type === 'cancellation'
                        ? __('management.ticket_change_requests.confirm_approve_cancel')
                        : __('management.ticket_change_requests.confirm_approve') }}">
                    {{ __('management.ticket_change_requests.approve_confirm') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
