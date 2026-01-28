<div class="space-y-6 max-w-2xl">
    <div>
        <flux:heading size="xl">Global Settings</flux:heading>
        <flux:subheading>Configure default convention details and ticket branding.</flux:subheading>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div
            class="bg-white dark:bg-zinc-800 p-6 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm space-y-6">
            <flux:input wire:model="conventionName" label="Convention Name"
                placeholder="e.g. Convention of Jehovah's Witness" />

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="conventionYear" label="Year" placeholder="e.g. 2025" />
                <flux:input wire:model="conventionLocation" label="Location" placeholder="e.g. Twickenham" />
            </div>

            <div class="space-y-3">
                <flux:heading size="sm">Ticket Logo / Image</flux:heading>

                @if ($existingLogo && !$ticketLogo)
                    <div class="mb-4">
                        <img src="{{ $existingLogo }}" alt="Ticket Logo"
                            class="h-24 w-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                    </div>
                @endif

                @if ($ticketLogo)
                    <div class="mb-4">
                        <img src="{{ $ticketLogo->temporaryUrl() }}" alt="Preview"
                            class="h-24 w-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                    </div>
                @endif

                <flux:input type="file" wire:model="ticketLogo" />
                <p class="text-xs text-zinc-500">Recommended: Transparent PNG, max 1MB.</p>
            </div>
        </div>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary">Save Settings</flux:button>
        </div>
    </form>
</div>