<div class="flex flex-col gap-8 max-w-lg mx-auto py-4">
    <div class="text-center space-y-3">
        <div class="inline-flex items-center justify-center p-3 bg-indigo-500/10 rounded-2xl mb-2">
            <flux:icon name="qr-code" class="size-8 text-indigo-500" />
        </div>
        <flux:heading size="xl" class="tracking-tight">Vehicle Check-in</flux:heading>
        <p class="text-zinc-400 text-sm">
            @if($step === 'confirm') 
                Please verify the vehicle details below 
            @else 
                Scan a congregation pass to check-in a vehicle 
            @endif
        </p>
    </div>

    @if($step === 'scan')
        <div class="space-y-6">
            {{-- Input Card --}}
            <div class="bg-white dark:bg-zinc-800 p-1 rounded-2xl border border-zinc-200 dark:border-zinc-700 shadow-xl overflow-hidden">
                <form wire:submit.prevent="scan" class="flex flex-col">
                    {{-- Camera View --}}
                    <div id="reader" class="w-full bg-black rounded-t-xl overflow-hidden" style="min-height: 300px; display: none;"></div>

                    <div class="p-6 space-y-4">
                        <div class="flex flex-col gap-2">
                            <flux:button type="button" variant="ghost" class="w-full" id="toggle-camera">
                                <flux:icon name="camera" class="mr-2" /> Scan with Camera
                            </flux:button>
                        </div>
                        
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
                    </div>
                </form>
            </div>

            {{-- Scripts --}}
            <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const toggleBtn = document.getElementById('toggle-camera');
                    const readerDiv = document.getElementById('reader');
                    let html5QrCode = null;
                    let isScanning = false;

                    toggleBtn.addEventListener('click', () => {
                        if (isScanning) {
                            if(html5QrCode) {
                                html5QrCode.stop().then(() => {
                                    isScanning = false;
                                    readerDiv.style.display = 'none';
                                    toggleBtn.innerText = 'Scan with Camera';
                                }).catch(err => console.error(err));
                            }
                        } else {
                            // Check browser support
                            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                                alert('Your browser does not support camera access. Please ensure you are using a modern browser and functioning HTTPS connection.');
                                return;
                            }

                            readerDiv.style.display = 'block';
                            
                            // Initialize logic
                            Html5Qrcode.getCameras().then(devices => {
                                if (devices && devices.length) {
                                    if (!html5QrCode) {
                                        html5QrCode = new Html5Qrcode("reader");
                                    }
                                    
                                    html5QrCode.start(
                                        { facingMode: "environment" }, 
                                        {
                                            fps: 10,
                                            qrbox: 250
                                        },
                                        (decodedText, decodedResult) => {
                                            console.log(`Scan matched: ${decodedText}`, decodedResult);
                                            @this.set('uuid', decodedText);
                                            // Play a beep or haptic feedback if possible
                                            if (navigator.vibrate) navigator.vibrate(200);
                                            
                                            html5QrCode.stop().then(() => {
                                                isScanning = false;
                                                readerDiv.style.display = 'none';
                                                toggleBtn.innerText = 'Scan with Camera';
                                                @this.scan();
                                            });
                                        },
                                        (errorMessage) => {
                                            // ignore
                                        }
                                    ).then(() => {
                                        isScanning = true;
                                        toggleBtn.innerText = 'Stop Camera';
                                    }).catch(err => {
                                        console.error(err);
                                        // Specific error handling
                                        if (err.name === 'NotAllowedError') {
                                           alert('Camera access was denied. Please allow camera permissions in your browser settings.');
                                        } else if (err.name === 'NotFoundError') {
                                            alert('No camera found on this device.');
                                        } else if (err.name === 'NotReadableError') {
                                            alert('Camera is improperly configured, in use, or blocked by system settings.');
                                        } else if (err.name === 'OverconstrainedError') {
                                            alert('Camera constraints failed. Retrying with default settings...');
                                            // Fallback retry could go here
                                        } else {
                                            alert('Camera Start Error: ' + err);
                                        }
                                        
                                        readerDiv.style.display = 'none';
                                        isScanning = false;
                                    });
                                } else {
                                    alert('No cameras found on your device.');
                                    readerDiv.style.display = 'none';
                                }
                            }).catch(err => {
                                console.error('Error fetching cameras', err);
                                alert('Error accessing camera information: ' + err);
                                readerDiv.style.display = 'none';
                            });
                        }
                    });
                });
                
                document.addEventListener('livewire:navigated', () => {
                     // Cleanup could be added here if needed
                });
            </script>

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
                                    {{ $lastScanPass->congregation->carPark->name ?? 'Unassigned' }}
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
        <div class="bg-white dark:bg-zinc-800 p-8 rounded-3xl border border-zinc-200 dark:border-zinc-700 shadow-2xl space-y-8 animate-in slide-in-from-bottom-4 duration-300">
            <div class="text-center">
                <div class="text-xs font-bold text-indigo-500 uppercase tracking-widest mb-1">Pass Authorized</div>
                <flux:heading size="xl" class="text-3xl">{{ $scannedCongregation->name }}</flux:heading>
                <div class="mt-3 inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full bg-indigo-500/10 text-indigo-500 font-bold text-sm">
                    <flux:icon name="map-pin" class="size-4" />
                    {{ $scannedCongregation->carPark->name }}
                </div>
            </div>

            <form wire:submit.prevent="confirm" class="space-y-6">
                @if($lastScanResult === 'error' && $lastScanMessage)
                    <div class="p-4 bg-red-500/10 border border-red-500/30 rounded-xl flex items-center gap-3 text-red-600 animate-in fade-in slide-in-from-top-2">
                        <flux:icon name="exclamation-circle" class="size-5 shrink-0" />
                        <span class="text-sm font-bold uppercase tracking-wide">{{ $lastScanMessage }}</span>
                    </div>
                @endif

                <div class="space-y-5">
                     <div class="space-y-3">
                        <label class="block text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Vehicle Plate Number <span class="text-red-500">*</span>
                        </label>
                        <flux:input 
                            wire:model.live.debounce.500ms="vehicleReg" 
                            placeholder="Enter Registration..."
                            class="uppercase text-center text-xl h-14 bg-zinc-50 dark:bg-zinc-900 border-none rounded-xl font-mono tracking-wider" 
                            autofocus
                        />
                        @error('vehicleReg') 
                            <span class="text-red-500 text-sm text-center block mt-1">{{ $message }}</span>
                        @enderror
                    </div>

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
                            <flux:input 
                                wire:model="name" 
                                placeholder="Driver's Name" 
                                class="text-center text-lg h-12 bg-zinc-50 dark:bg-zinc-900 border-none rounded-xl"
                            />
                            @if($foundRegistration)
                                <div class="absolute right-3 top-1/2 -translate-y-1/2 text-green-500">
                                    <flux:icon name="lock-closed" class="size-4" />
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Email <span class="text-zinc-400 font-normal normal-case">(Optional)</span>
                        </label>
                         <div class="relative">
                            <flux:input 
                                wire:model="email" 
                                type="email"
                                placeholder="email@example.com" 
                                class="text-center text-lg h-12 bg-zinc-50 dark:bg-zinc-900 border-none rounded-xl"
                            />
                            @if($foundRegistration)
                                <div class="absolute right-3 top-1/2 -translate-y-1/2 text-green-500">
                                    <flux:icon name="lock-closed" class="size-4" />
                                </div>
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
                </div>

                <div class="flex flex-col gap-3">
                    <flux:button type="submit" variant="primary" 
                        wire:loading.attr="disabled"
                        class="w-full h-14 text-lg font-bold rounded-xl shadow-lg shadow-indigo-500/20">
                        <span wire:loading.remove>CLOCK IN / PARK CAR</span>
                        <span wire:loading>PROCESSING...</span>
                    </flux:button>
                    <flux:button type="button" variant="ghost" wire:click="cancel" wire:loading.attr="disabled" class="w-full h-12">
                        Abort Scan
                    </flux:button>
                </div>
            </form>
        </div>
    @endif
</div>