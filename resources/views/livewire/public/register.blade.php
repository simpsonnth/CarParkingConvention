<div class="min-h-screen flex flex-col items-center justify-center p-6 bg-zinc-50 dark:bg-zinc-900">
    <div
        class="w-full max-w-lg bg-white dark:bg-zinc-800 rounded-3xl shadow-xl p-8 border border-zinc-100 dark:border-zinc-700">

        <div class="text-center mb-8">
            <h1 class="text-3xl font-black text-zinc-900 dark:text-white tracking-tight mb-2">Parking Registration</h1>
            <p class="text-zinc-500 dark:text-zinc-400">Please fill in your details to register your vehicle.</p>
        </div>

        @if($registered)
            <div
                class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-2xl p-6 text-center">
                <div class="flex justify-center mb-4">
                    <div class="rounded-full bg-green-100 dark:bg-green-900 p-3">
                        <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                </div>
                <h3 class="text-xl font-bold text-green-800 dark:text-green-200 mb-2">Registration Complete!</h3>
                <p class="text-green-700 dark:text-green-300 mb-6">Thank you for registering. You can now close this page.
                </p>
                <button wire:click="$set('registered', false)"
                    class="text-sm font-semibold text-green-800 dark:text-green-200 hover:underline">
                    Register another vehicle
                </button>
            </div>
        @else
            <form wire:submit="register" class="space-y-6">
                <!-- Name -->
                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">Full Name</label>
                    <input type="text" wire:model="name"
                        class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 transition py-3 px-4"
                        placeholder="e.g. John Doe">
                    @error('name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Congregation -->
                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">Congregation</label>
                    <select wire:model="congregation"
                        class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 transition py-3 px-4">
                        <option value="">Select Congregation...</option>
                        @foreach($congregations as $cong)
                            <option value="{{ $cong->name }}">{{ $cong->name }}</option>
                        @endforeach
                    </select>
                    @error('congregation') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Contact & Email Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">Contact Number</label>
                        <input type="tel" wire:model="contactNumber"
                            class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 transition py-3 px-4"
                            placeholder="07123 456789">
                        @error('contactNumber') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">Email Address</label>
                        <input type="email" wire:model="email"
                            class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 transition py-3 px-4"
                            placeholder="john@example.com">
                        @error('email') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Vehicle Reg -->
                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">Vehicle
                        Registration</label>
                    <input type="text" wire:model="vehicleReg"
                        class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 transition py-3 px-4 uppercase font-mono tracking-wider"
                        placeholder="AB12 CDE">
                    @error('vehicleReg') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Days (Checkboxes) -->
                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-3">Attending Days</label>
                    <div class="space-y-3">
                        @foreach(['Friday', 'Saturday', 'Sunday'] as $day)
                            <label
                                class="flex items-center p-3 border border-zinc-200 dark:border-zinc-700 rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-700/50 cursor-pointer transition">
                                <input type="checkbox" wire:model="days" value="{{ $day }}"
                                    class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500 border-zinc-300">
                                <span class="ml-3 text-zinc-900 dark:text-white font-medium">{{ $day }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('days') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="pt-4">
                    <button type="submit"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 px-6 rounded-xl transition transform active:scale-[0.98] shadow-lg shadow-indigo-500/20">
                        Submit Registration
                    </button>
                    <!-- Loading Indicator -->
                    <div wire:loading class="text-center mt-2 text-zinc-400 text-sm">
                        Processing...
                    </div>
                </div>
            </form>
        @endif

        <div class="mt-8 text-center">
            <p class="text-xs text-zinc-400">
                &copy; {{ date('Y') }} Convention Parking System
            </p>
        </div>
    </div>
</div>