@assets
@vite(['resources/js/attendant-scan.js'])
@endassets

@script
<script>
    const start = () => {
        if (typeof window.initAttendantScan === 'function') {
            window.initAttendantScan($wire);
        }
    };
    start();
    document.addEventListener('attendant-scan:ready', start, { once: true });

    $wire.on('attendant-scan-ready-for-next', () => {
        document.dispatchEvent(new CustomEvent('attendant-scan:ready-for-next'));
        if (typeof window.releaseAttendantScanLock === 'function') {
            window.releaseAttendantScanLock();
        }
        if (typeof window.clearAttendantScanMemory === 'function') {
            window.clearAttendantScanMemory();
        }
        if (typeof window.resumeAttendantScannerUi === 'function') {
            window.resumeAttendantScannerUi();
        }
    });
</script>
@endscript

<div class="flex flex-col gap-8 max-w-lg mx-auto py-4">
    <div class="text-center space-y-3">
        <div class="inline-flex items-center justify-center p-3 bg-indigo-500/10 rounded-2xl mb-2">
            <flux:icon name="qr-code" class="size-8 text-indigo-500" />
        </div>
        <flux:heading size="xl" class="tracking-tight">Vehicle Check-in</flux:heading>
        <p class="text-zinc-400 text-sm">
            @if($walkInMode && $walkInVehicleType === 'coach')
                Coach walk-in check-in — no ticket required
            @elseif($walkInMode)
                Walk-in check-in — no ticket required
            @elseif($step === 'confirm' && $quickCheckIn)
                Ticket scanned — confirm and clock in
            @elseif($step === 'confirm')
                Please verify the vehicle details below
            @else
                Scan a ticket or congregation pass to check in a vehicle
            @endif
        </p>
    </div>

    @unless($walkInMode)
        {{-- Keep camera DOM mounted across scan/confirm so the stream survives Livewire morphs --}}
        <div
            data-attendant-camera-card
            @class([
                'bg-white dark:bg-zinc-800 p-1 rounded-2xl border border-zinc-200 dark:border-zinc-700 shadow-xl overflow-hidden',
                'hidden' => $step === 'confirm',
            ])
        >
            <div data-attendant-reader-shell>
                <div id="reader" wire:ignore class="w-full bg-black rounded-t-xl overflow-hidden" style="min-height: 300px; display: none;"></div>
            </div>

            <div class="p-6 space-y-4">
                <div wire:ignore>
                    <button
                        type="button"
                        id="toggle-camera"
                        data-attendant-scan-toggle
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm font-semibold text-zinc-800 shadow-sm hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700"
                    >
                        <flux:icon name="camera" class="size-5 shrink-0" />
                        <span data-attendant-scan-label>Scan with Camera</span>
                    </button>
                </div>

                @if($step === 'scan')
                    <form wire:submit.prevent="scan" class="space-y-4">
                        <div class="relative">
                            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                                <div class="w-full border-t border-zinc-200 dark:border-zinc-700"></div>
                            </div>
                            <div class="relative flex justify-center text-sm font-medium leading-6">
                                <span class="bg-white dark:bg-zinc-800 px-4 text-zinc-500">or enter code</span>
                            </div>
                        </div>

                        <flux:input
                            wire:model="uuid"
                            placeholder="Type code here..."
                            autofocus
                            autocomplete="off"
                            class="text-center text-xl h-14 bg-zinc-50 dark:bg-zinc-900 border-none rounded-xl focus:ring-2 focus:ring-indigo-500"
                        />
                        <flux:button type="submit" variant="primary" class="w-full h-14 text-lg font-bold rounded-xl shadow-lg shadow-indigo-500/20">
                            CHECK CODE
                        </flux:button>
                    </form>
                @endif
            </div>
        </div>
    @endunless

    @if($step === 'scan')
        <div class="bg-white dark:bg-zinc-800 p-6 rounded-2xl border border-zinc-200 dark:border-zinc-700 shadow-xl space-y-4">
            <div class="text-center space-y-1">
                <div class="text-xs font-bold uppercase tracking-widest text-zinc-400">Find vehicle</div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    Look up where a car should park by plate or ticket number
                </p>
            </div>

            <form wire:submit.prevent="lookup" class="space-y-4">
                <flux:input
                    wire:model="lookupQuery"
                    placeholder="Plate or ticket number..."
                    autocomplete="off"
                    class="text-center text-xl h-14 bg-zinc-50 dark:bg-zinc-900 border-none rounded-xl font-mono tracking-wider uppercase focus:ring-2 focus:ring-indigo-500"
                />
                @error('lookupQuery')
                    <span class="text-red-500 text-sm block text-center">{{ $message }}</span>
                @enderror
                <flux:button type="submit" variant="filled" class="w-full h-12 text-base font-bold rounded-xl">
                    LOOK UP
                </flux:button>
            </form>

            @if($lookupError)
                <div class="rounded-xl border border-red-500/30 bg-red-500/5 p-4 text-center">
                    <div class="text-sm font-semibold text-red-500">{{ $lookupError }}</div>
                </div>
            @endif

            @foreach($lookupResults as $result)
                <div class="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/50 p-5 space-y-4">
                    <div class="text-center">
                        @if(! empty($result['car_park_name']))
                            <div class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-indigo-500/10 text-indigo-500 font-bold text-base">
                                <flux:icon name="map-pin" class="size-4" />
                                {{ $result['car_park_name'] }}
                                @if(! empty($result['car_park_is_individual']))
                                    <span class="text-xs font-normal opacity-90">(individual)</span>
                                @endif
                            </div>
                        @else
                            <div class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400 font-bold text-sm">
                                <flux:icon name="exclamation-triangle" class="size-4" />
                                Not assigned to a car park
                            </div>
                        @endif
                    </div>

                    <div class="space-y-2 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-zinc-500 uppercase tracking-wider text-xs font-bold">Name</span>
                            <span class="font-semibold text-zinc-900 dark:text-white text-right">{{ $result['name'] ?: '—' }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-zinc-500 uppercase tracking-wider text-xs font-bold">Phone</span>
                            @if(! empty($result['contact_number']))
                                <a href="tel:{{ $result['contact_number'] }}" class="font-mono font-semibold text-indigo-500 hover:underline">
                                    {{ $result['contact_number'] }}
                                </a>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-zinc-500 uppercase tracking-wider text-xs font-bold">Email</span>
                            @if(! empty($result['email']))
                                <a href="mailto:{{ $result['email'] }}" class="font-semibold text-indigo-500 hover:underline text-right break-all">
                                    {{ $result['email'] }}
                                </a>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-zinc-500 uppercase tracking-wider text-xs font-bold">Plate</span>
                            <span class="font-mono font-bold text-zinc-900 dark:text-white tracking-wider">
                                {{ $result['vehicle_registration'] ?: '—' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-zinc-500 uppercase tracking-wider text-xs font-bold">Ticket</span>
                            <span class="font-mono text-zinc-900 dark:text-white">{{ $result['ticket_number'] }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-zinc-500 uppercase tracking-wider text-xs font-bold">Congregation</span>
                            <span class="font-semibold text-zinc-900 dark:text-white text-right">{{ $result['congregation'] }}</span>
                        </div>
                    </div>

                    @if(! empty($result['is_circuit_overseer']))
                        <p class="text-center text-xs text-amber-600 dark:text-amber-400">
                            Circuit overseer ticket — use walk-in check-in if needed.
                        </p>
                    @elseif(! empty($result['can_check_in']))
                        <flux:button
                            type="button"
                            variant="primary"
                            wire:click="checkInFromLookup({{ (int) $result['id'] }})"
                            class="w-full h-12 text-base font-bold rounded-xl"
                        >
                            Check in this vehicle
                        </flux:button>
                    @endif
                </div>
            @endforeach

            @if($lookupSearched || $lookupResults !== [] || $lookupError)
                <div class="text-center">
                    <button
                        type="button"
                        wire:click="clearLookup"
                        class="text-sm font-semibold text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200"
                    >
                        Clear lookup
                    </button>
                </div>
            @endif
        </div>

        <div class="space-y-6">
            {{-- Result Card --}}
            @if ($lastScanResult)
                <div @class([
                    'relative overflow-hidden p-6 rounded-2xl border-2 transition-all animate-in fade-in zoom-in duration-300',
                    'bg-green-500/5 border-green-500/30' => $lastScanResult === 'success',
                    'bg-red-500/5 border-red-500/30' => $lastScanResult === 'error',
                    'bg-yellow-500/5 border-yellow-500/30' => $lastScanResult === 'warning',
                ])>
                    {{-- Status Icon Background --}}
                    <div class="absolute -right-4 -bottom-4 opacity-10">
                        @if($lastScanResult === 'success') <flux:icon name="check-circle" class="size-32 text-green-500" />
                        @elseif($lastScanResult === 'error') <flux:icon name="x-circle" class="size-32 text-red-500" />
                        @else <flux:icon name="exclamation-circle" class="size-32 text-yellow-500" />
                        @endif
                    </div>

                    <div class="flex items-center gap-4 relative z-10">
                        <div @class([
                            'p-3 rounded-full flex-shrink-0',
                            'bg-green-500 text-white shadow-lg shadow-green-500/30' => $lastScanResult === 'success',
                            'bg-red-500 text-white shadow-lg shadow-red-500/30' => $lastScanResult === 'error',
                            'bg-yellow-500 text-white shadow-lg shadow-yellow-500/30' => $lastScanResult === 'warning',
                        ])>
                            @if($lastScanResult === 'success') <flux:icon name="check" class="size-6" />
                            @elseif($lastScanResult === 'error') <flux:icon name="x-mark" class="size-6" />
                            @else <flux:icon name="exclamation-triangle" class="size-6" />
                            @endif
                        </div>

                        <div class="flex-1">
                            <div @class([
                                'text-xs font-bold uppercase tracking-widest',
                                'text-green-500' => $lastScanResult === 'success',
                                'text-red-500' => $lastScanResult === 'error',
                                'text-yellow-500' => $lastScanResult === 'warning',
                            ])>
                                {{ $lastScanMessage }}
                            </div>
                            
                            @if($lastScanPass)
                                <div class="text-zinc-900 dark:text-white font-bold text-lg mt-0.5">
                                    {{ $lastScanPass->congregation->name ?? 'Unknown' }}
                                </div>
                                <div class="text-zinc-500 dark:text-zinc-400 text-sm flex items-center gap-1.5 mt-1">
                                    <flux:icon name="map-pin" class="size-3.5" />
                                    {{ $lastScanPass->carPark?->name ?? $lastScanPass->congregation->carPark?->name ?? 'Unassigned' }}
                                </div>
                                @if($lastScanPass->vehicle_reg)
                                    <div class="mt-3 inline-block px-3 py-1 bg-white/20 dark:bg-zinc-900/50 backdrop-blur-sm border border-white/30 dark:border-zinc-700/50 rounded-lg text-lg font-mono tracking-wider text-zinc-900 dark:text-white">
                                        {{ $lastScanPass->vehicle_reg }}
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>

    @elseif($step === 'confirm')
        <div data-attendant-scan-confirm class="space-y-6">
        @if($lastScanResult === 'success' && $lastScanPass)
            <div class="relative overflow-hidden p-6 rounded-2xl border-2 bg-green-500/5 border-green-500/30">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-full bg-green-500 text-white shadow-lg shadow-green-500/30">
                        <flux:icon name="check" class="size-6" />
                    </div>
                    <div class="flex-1">
                        <div class="text-xs font-bold uppercase tracking-widest text-green-500">{{ $lastScanMessage }}</div>
                        <div class="text-zinc-900 dark:text-white font-bold text-lg mt-0.5">{{ $lastScanPass->congregation->name ?? 'Unknown' }}</div>
                        <div class="text-zinc-500 dark:text-zinc-400 text-sm flex items-center gap-1.5 mt-1">
                            <flux:icon name="map-pin" class="size-3.5" />
                            {{ $lastScanPass->carPark?->name ?? $lastScanPass->congregation->carPark?->name ?? 'Unassigned' }}
                        </div>
                        @if($lastScanPass->vehicle_reg)
                            <div class="mt-3 inline-block px-3 py-1 bg-white/20 dark:bg-zinc-900/50 border border-white/30 dark:border-zinc-700/50 rounded-lg text-lg font-mono tracking-wider text-zinc-900 dark:text-white">
                                {{ $lastScanPass->vehicle_reg }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        @if($quickCheckIn && $scannedRegistration)
            <div class="bg-white dark:bg-zinc-800 p-4 sm:p-8 rounded-3xl border border-zinc-200 dark:border-zinc-700 shadow-2xl space-y-8 animate-in slide-in-from-bottom-4 duration-300">
                <div class="text-center">
                    <div class="text-xs font-bold text-green-500 uppercase tracking-widest mb-1">Ticket Verified</div>
                    <flux:heading size="xl" class="text-3xl">{{ $scannedCongregation->name }}</flux:heading>
                    @if($effectiveCarPark ?? null)
                        <div class="mt-3 inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full bg-indigo-500/10 text-indigo-500 font-bold text-sm">
                            <flux:icon name="map-pin" class="size-4" />
                            {{ $effectiveCarPark->name }}
                            @if($foundRegistration?->car_park_id)
                                <span class="text-xs font-normal opacity-90">(individual)</span>
                            @endif
                        </div>
                    @endif
                </div>

                @if($editingDetails)
                    <form wire:submit.prevent="saveRegistrationDetails" class="space-y-6">
                        <div class="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/50 p-6 space-y-5">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="text-xs font-bold text-zinc-400 uppercase tracking-widest">Ticket Number</div>
                                    <div class="text-xl font-mono font-bold text-zinc-900 dark:text-white mt-0.5">
                                        {{ str_pad((string) $scannedRegistration->id, 6, '0', STR_PAD_LEFT) }}
                                    </div>
                                </div>
                                <div class="text-xs font-bold uppercase tracking-widest text-indigo-500">Edit details</div>
                            </div>

                            <div class="space-y-3">
                                <label class="block text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                    Vehicle registration <span class="text-red-500">*</span>
                                </label>
                                <flux:input
                                    wire:model="vehicleReg"
                                    class="uppercase text-center text-xl h-14 bg-white dark:bg-zinc-900 border-none rounded-xl font-mono tracking-wider"
                                />
                                @error('vehicleReg')
                                    <span class="text-red-500 text-sm block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="space-y-3">
                                <label class="block text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                    Name
                                </label>
                                <flux:input
                                    wire:model="name"
                                    class="text-center text-lg h-12 bg-white dark:bg-zinc-900 border-none rounded-xl"
                                />
                                @error('name')
                                    <span class="text-red-500 text-sm block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="space-y-3">
                                <label class="block text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                    Contact number <span class="text-red-500">*</span>
                                </label>
                                <flux:input
                                    wire:model="contactNumber"
                                    type="tel"
                                    class="text-center text-xl h-14 bg-white dark:bg-zinc-900 border-none rounded-xl"
                                />
                                @error('contactNumber')
                                    <span class="text-red-500 text-sm block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="space-y-3">
                                <label class="block text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                    Email
                                </label>
                                <flux:input
                                    wire:model="email"
                                    type="email"
                                    class="text-center text-lg h-12 bg-white dark:bg-zinc-900 border-none rounded-xl"
                                />
                                @error('email')
                                    <span class="text-red-500 text-sm block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="space-y-3">
                                <label class="block text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                    Days attending
                                </label>
                                <div class="flex flex-wrap justify-center gap-2">
                                    @foreach(['Friday', 'Saturday', 'Sunday'] as $day)
                                        <button type="button"
                                            wire:click="toggleDay('{{ $day }}')"
                                            @class([
                                                'px-4 py-2 rounded-lg text-sm font-medium transition-all border',
                                                'bg-indigo-500 text-white border-indigo-600 shadow-md' => in_array($day, $days),
                                                'bg-white dark:bg-zinc-900 text-zinc-500 dark:text-zinc-400 border-zinc-200 dark:border-zinc-700' => ! in_array($day, $days),
                                            ])
                                        >
                                            {{ $day }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            @if(($scannedRegistration->vehicle_type ?? 'car') !== 'coach')
                                <label class="flex items-center gap-3 cursor-pointer rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-4 py-3">
                                    <flux:checkbox wire:model="elderlyInfirmParking" />
                                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Elderly &amp; Infirm parking</span>
                                </label>
                            @endif
                        </div>

                        <div class="flex flex-col gap-3">
                            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" class="w-full h-14 text-lg font-bold rounded-xl">
                                <span wire:loading.remove wire:target="saveRegistrationDetails">Save details</span>
                                <span wire:loading wire:target="saveRegistrationDetails">Saving...</span>
                            </flux:button>
                            <flux:button type="button" variant="ghost" wire:click="cancelEditingDetails" class="w-full h-12">
                                Cancel
                            </flux:button>
                        </div>
                    </form>
                @else
                <form wire:submit.prevent="confirm" class="space-y-6">
                    @if($lastScanResult === 'error' && $lastScanMessage)
                        <div class="p-4 bg-red-500/10 border border-red-500/30 rounded-xl flex items-center gap-3 text-red-600">
                            <flux:icon name="exclamation-circle" class="size-5 shrink-0" />
                            <span class="text-sm font-bold uppercase tracking-wide">{{ $lastScanMessage }}</span>
                        </div>
                    @endif

                    <div class="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/50 p-6 space-y-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="text-center flex-1">
                                <div class="text-xs font-bold text-zinc-400 uppercase tracking-widest">Ticket Number</div>
                                <div class="text-2xl font-mono font-bold text-zinc-900 dark:text-white mt-1">
                                    {{ str_pad((string) $scannedRegistration->id, 6, '0', STR_PAD_LEFT) }}
                                </div>
                            </div>
                            <flux:button type="button" size="sm" variant="ghost" wire:click="startEditingDetails" icon="pencil-square" class="shrink-0">
                                Edit
                            </flux:button>
                        </div>

                        <div class="grid gap-3 text-sm">
                            <div class="flex justify-between gap-4">
                                <span class="text-zinc-500">Name</span>
                                <span class="font-semibold text-zinc-900 dark:text-white text-end">{{ $name }}</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-zinc-500">Vehicle</span>
                                <span class="font-mono font-bold text-zinc-900 dark:text-white">{{ $vehicleReg }}</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-zinc-500">Contact</span>
                                <span class="font-mono text-zinc-900 dark:text-white">{{ $contactNumber }}</span>
                            </div>
                            @if($email)
                                <div class="flex justify-between gap-4">
                                    <span class="text-zinc-500">Email</span>
                                    <span class="text-zinc-900 dark:text-white text-end">{{ $email }}</span>
                                </div>
                            @endif
                            @if(count($days) > 0)
                                <div class="flex justify-between gap-4 items-start">
                                    <span class="text-zinc-500">Days</span>
                                    <span class="flex flex-wrap gap-1 justify-end">
                                        @foreach($days as $day)
                                            <span class="px-2 py-0.5 rounded bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 text-xs font-medium">{{ substr($day, 0, 3) }}</span>
                                        @endforeach
                                    </span>
                                </div>
                            @endif
                        </div>

                        @if(($scannedRegistration->vehicle_type ?? 'car') === 'coach')
                            <div class="rounded-lg bg-amber-500/10 border border-amber-500/30 px-3 py-2 text-center text-sm font-semibold text-amber-700 dark:text-amber-300">
                                Coach space required
                            </div>
                        @endif
                        @if($elderlyInfirmParking)
                            <div class="rounded-lg bg-sky-500/10 border border-sky-500/30 px-3 py-2 text-center text-sm font-semibold text-sky-700 dark:text-sky-300">
                                Elderly &amp; Infirm parking
                            </div>
                        @endif
                    </div>

                    @if(auth()->check() && $existingParkedPass)
                        <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50/50 dark:bg-amber-900/20 p-4 space-y-3">
                            <div class="flex items-center gap-2 text-sm font-bold text-amber-800 dark:text-amber-200">
                                <flux:icon name="truck" class="size-5" />
                                Already parked
                            </div>
                            <button type="button"
                                wire:click="clockOut({{ $existingParkedPass->id }})"
                                wire:confirm="Clock out this vehicle?"
                                class="inline-flex items-center gap-2 rounded-lg border border-amber-300 dark:border-amber-600 px-3 py-2 text-sm font-medium text-amber-700 dark:text-amber-300">
                                <flux:icon name="arrow-right-start-on-rectangle" class="size-4" />
                                Clock out
                            </button>
                        </div>
                    @endif

                    <div class="flex flex-col gap-3">
                        @if($existingParkedPass)
                            <flux:button type="button" variant="primary" disabled class="w-full h-14 text-lg font-bold rounded-xl opacity-60 cursor-not-allowed">
                                CLOCK IN / PARK CAR
                            </flux:button>
                        @else
                            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" class="w-full h-14 text-lg font-bold rounded-xl shadow-lg shadow-indigo-500/20">
                                <span wire:loading.remove>CLOCK IN / PARK CAR</span>
                                <span wire:loading>PROCESSING...</span>
                            </flux:button>
                        @endif
                        <flux:button type="button" variant="ghost" wire:click="cancel" class="w-full h-12">
                            Abort Scan
                        </flux:button>
                    </div>
                </form>
                @endif
            </div>
        @else
        <div class="bg-white dark:bg-zinc-800 p-4 sm:p-8 rounded-3xl border border-zinc-200 dark:border-zinc-700 shadow-2xl space-y-8 animate-in slide-in-from-bottom-4 duration-300">
            <div class="text-center">
                @if($walkInMode && $walkInVehicleType === 'coach')
                    <div class="text-xs font-bold text-amber-600 uppercase tracking-widest mb-1">Coach Walk-in Check-in</div>
                    <flux:heading size="xl" class="text-2xl">No ticket — enter coach details</flux:heading>
                    <div class="mt-3 inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full bg-amber-500/10 text-amber-700 dark:text-amber-300 font-bold text-sm">
                        <flux:icon name="truck" class="size-4" />
                        Coach space
                    </div>
                @elseif($walkInMode)
                    <div class="text-xs font-bold text-amber-500 uppercase tracking-widest mb-1">Walk-in Check-in</div>
                    <flux:heading size="xl" class="text-2xl">No ticket — enter details</flux:heading>
                @else
                    <div class="text-xs font-bold text-indigo-500 uppercase tracking-widest mb-1">Pass Authorized</div>
                    <flux:heading size="xl" class="text-3xl">{{ $scannedCongregation?->name }}</flux:heading>
                @endif
                @if($effectiveCarPark ?? null)
                    <div class="mt-3 inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full bg-indigo-500/10 text-indigo-500 font-bold text-sm">
                        <flux:icon name="map-pin" class="size-4" />
                        {{ $effectiveCarPark->name }}
                        @if($foundRegistration?->car_park_id)
                            <span class="text-xs font-normal opacity-90">(individual)</span>
                        @endif
                    </div>
                @elseif($walkInMode && $selectedCongregationId)
                    <div class="mt-3 inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400 font-bold text-sm">
                        Not assigned to a car park
                    </div>
                @elseif(!$walkInMode)
                    <div class="mt-3 inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400 font-bold text-sm">
                        Not assigned to a car park
                    </div>
                @endif
            </div>

            <form wire:submit.prevent="confirm" class="space-y-6">
                @if($lastScanResult === 'error' && $lastScanMessage)
                    <div class="p-4 bg-red-500/10 border border-red-500/30 rounded-xl flex items-center gap-3 text-red-600 animate-in fade-in slide-in-from-top-2">
                        <flux:icon name="exclamation-circle" class="size-5 shrink-0" />
                        <span class="text-sm font-bold uppercase tracking-wide">{{ $lastScanMessage }}</span>
                    </div>
                @endif

                <div class="space-y-5">
                    @if($walkInMode)
                        <div class="space-y-3">
                            <label class="block text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Congregation <span class="text-red-500">*</span>
                            </label>
                            <flux:select wire:model.live="selectedCongregationId" placeholder="Select congregation...">
                                <flux:select.option value="">Select congregation...</flux:select.option>
                                @foreach($this->congregations as $congregation)
                                    <flux:select.option value="{{ $congregation->id }}">{{ $congregation->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            @error('selectedCongregationId')
                                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                            @enderror
                        </div>
                    @endif

                     <div class="space-y-3">
                        <label class="block text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            @if($walkInMode && $walkInVehicleType === 'coach')
                                Coach Registration
                                <span class="text-zinc-400 font-normal normal-case">(Optional)</span>
                            @else
                                Vehicle Plate Number <span class="text-red-500">*</span>
                            @endif
                        </label>
                        <flux:input 
                            wire:model.live.debounce.500ms="vehicleReg" 
                            placeholder="{{ ($walkInMode && $walkInVehicleType === 'coach') ? 'Enter coach plate if displayed...' : 'Enter Registration...' }}"
                            class="uppercase text-center text-xl h-14 bg-zinc-50 dark:bg-zinc-900 border-none rounded-xl font-mono tracking-wider" 
                            autofocus
                        />
                        @error('vehicleReg') 
                            <span class="text-red-500 text-sm text-center block mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    @if(auth()->check() && $existingParkedPass)
                        <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50/50 dark:bg-amber-900/20 p-4 space-y-3">
                            <div class="flex items-center gap-2 text-sm font-bold text-amber-800 dark:text-amber-200">
                                <flux:icon name="truck" class="size-5" />
                                Currently parked (1)
                            </div>
                            <div class="text-sm font-mono font-semibold text-zinc-900 dark:text-white">{{ $existingParkedPass->vehicle_reg ?? '–' }}</div>
                            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ $existingParkedPass->name ?? '–' }}</div>
                            <div class="text-sm text-zinc-500 dark:text-zinc-500 font-mono">{{ $existingParkedPass->contact_number ?? '–' }}</div>
                            @if($existingParkedPass->notes)
                                <div class="text-xs text-amber-700 dark:text-amber-300">({{ $existingParkedPass->notes }})</div>
                            @endif
                            <button type="button"
                                wire:click="clockOut({{ $existingParkedPass->id }})"
                                wire:confirm="Clock out this vehicle?"
                                class="mt-3 inline-flex items-center gap-2 rounded-lg border border-amber-300 dark:border-amber-600 bg-transparent px-3 py-2 text-sm font-medium text-amber-700 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/40 transition">
                                <flux:icon name="arrow-right-start-on-rectangle" class="size-4" />
                                Clock out
                            </button>
                        </div>
                    @endif

                    @if($foundRegistration)
                        <div class="bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-500/30 p-4 rounded-xl animate-in fade-in slide-in-from-top-2">
                             <div class="flex items-center gap-3 mb-2">
                                <flux:icon name="check-circle" class="size-5 text-indigo-600 dark:text-indigo-400" />
                                <h3 class="font-bold text-indigo-900 dark:text-indigo-300">Registration Found</h3>
                            </div>
                            <div class="space-y-1 text-sm text-indigo-800 dark:text-indigo-200">
                                <p><span class="opacity-70">Name:</span> <strong>{{ $foundRegistration->name }}</strong></p>
                                <p><span class="opacity-70">Congregation:</span> <strong>{{ $foundRegistration->congregation }}</strong></p>
                                <p><span class="opacity-70">Days:</span> 
                                    @foreach($foundRegistration->days as $d)
                                        <span class="px-1.5 py-0.5 bg-white/50 dark:bg-black/20 rounded text-xs font-medium">{{ substr($d, 0, 3) }}</span>
                                    @endforeach
                                </p>
                            </div>
                        </div>
                    @endif

                    <div class="space-y-3">
                        <label class="block text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Name <span class="text-zinc-400 font-normal normal-case">(Optional)</span>
                        </label>
                        <div class="relative">
                            @if($foundRegistration)
                                <input type="text" value="{{ $name }}"
                                    readonly
                                    class="w-full text-center text-lg h-12 bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-600 rounded-xl text-zinc-700 dark:text-zinc-300 cursor-not-allowed"
                                />
                                <div class="absolute right-3 top-1/2 -translate-y-1/2 text-green-500 pointer-events-none">
                                    <flux:icon name="lock-closed" class="size-4" />
                                </div>
                            @else
                                <flux:input 
                                    wire:model="name" 
                                    placeholder="Driver's Name" 
                                    class="text-center text-lg h-12 bg-zinc-50 dark:bg-zinc-900 border-none rounded-xl"
                                />
                            @endif
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Email <span class="text-zinc-400 font-normal normal-case">(Optional)</span>
                        </label>
                        <div class="relative">
                            @if($foundRegistration)
                                <input type="email" value="{{ $email }}"
                                    readonly
                                    class="w-full text-center text-lg h-12 bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-600 rounded-xl text-zinc-700 dark:text-zinc-300 cursor-not-allowed"
                                />
                                <div class="absolute right-3 top-1/2 -translate-y-1/2 text-green-500 pointer-events-none">
                                    <flux:icon name="lock-closed" class="size-4" />
                                </div>
                            @else
                                <flux:input 
                                    wire:model="email" 
                                    type="email"
                                    placeholder="email@example.com" 
                                    class="text-center text-lg h-12 bg-zinc-50 dark:bg-zinc-900 border-none rounded-xl"
                                />
                            @endif
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Days Attending
                        </label>
                        <div class="flex flex-wrap justify-center gap-2">
                            @foreach(['Friday', 'Saturday', 'Sunday'] as $day)
                                <button type="button" 
                                    wire:click="toggleDay('{{ $day }}')"
                                    @class([
                                        'px-4 py-2 rounded-lg text-sm font-medium transition-all border',
                                        'bg-indigo-500 text-white border-indigo-600 shadow-md transform scale-105' => in_array($day, $days),
                                        'bg-zinc-50 dark:bg-zinc-900 text-zinc-500 dark:text-zinc-400 border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800' => !in_array($day, $days),
                                    ])
                                >
                                    {{ $day }}
                                </button>
                            @endforeach
                        </div>
                        {{-- Hidden input for Livewire binding since buttons handle it via JS/Alpine logic usually, 
                             but here we need to sync with backend. 
                             Actually, simpler way for Livewire array: --}}

                    </div>

                    <div class="space-y-3">
                        <label class="block text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Contact Number <span class="text-red-500">*</span>
                        </label>
                        <flux:input 
                            wire:model="contactNumber" 
                            placeholder="Mobile preferred..." 
                            type="tel"
                            class="text-center text-xl h-14 bg-zinc-50 dark:bg-zinc-900 border-none rounded-xl" 
                            required 
                        />
                        @error('contactNumber') 
                            <span class="text-red-500 text-sm text-center block mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    @if(!($walkInMode && $walkInVehicleType === 'coach'))
                        <div class="space-y-3">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" wire:model="elderlyInfirmParking"
                                    class="w-5 h-5 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Needs Elderly &amp; Infirm parking</span>
                            </label>
                        </div>
                    @endif

                    @if($walkInMode && $walkInVehicleType === 'coach')
                        <div class="space-y-3">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" wire:model="coachCaptainToBeAssigned"
                                    class="w-5 h-5 rounded border-zinc-300 text-amber-600 focus:ring-amber-500">
                                <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Coach captain to be assigned</span>
                            </label>
                        </div>
                    @endif

                    <div class="space-y-3">
                        <label class="block text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Notes <span class="text-zinc-400 font-normal normal-case">(Optional) e.g. Section A</span>
                        </label>
                        <flux:input 
                            wire:model="notes" 
                            placeholder="{{ ($walkInMode && $walkInVehicleType === 'coach') ? 'Coach bay, size, or parking location...' : 'Where they have parked...' }}" 
                            class="text-center text-lg h-12 bg-zinc-50 dark:bg-zinc-900 border-none rounded-xl"
                        />
                    </div>
                </div>

                <div class="flex flex-col gap-3">
                    @if($existingParkedPass)
                        <div class="w-full py-4 px-4 rounded-xl border border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/30 text-center text-sm font-semibold text-amber-800 dark:text-amber-200">
                            Already parked – cannot clock in again
                        </div>
                        <flux:button type="button" variant="primary" disabled class="w-full h-14 text-lg font-bold rounded-xl opacity-60 cursor-not-allowed">
                            CLOCK IN / PARK CAR
                        </flux:button>
                    @else
                        <flux:button type="submit" variant="primary" 
                            wire:loading.attr="disabled"
                            class="w-full h-14 text-lg font-bold rounded-xl shadow-lg shadow-indigo-500/20">
                            <span wire:loading.remove>CLOCK IN / PARK CAR</span>
                            <span wire:loading>PROCESSING...</span>
                        </flux:button>
                    @endif
                    <flux:button type="button" variant="ghost" wire:click="cancel" wire:loading.attr="disabled" class="w-full h-12">
                        {{ $walkInMode ? 'Cancel' : 'Abort Scan' }}
                    </flux:button>
                </div>
            </form>
        </div>
        @endif
        </div>
    @endif
</div>