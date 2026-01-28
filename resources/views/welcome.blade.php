<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Twickenham Parking</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body
    class="h-full bg-zinc-50 dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 antialiased flex flex-col items-center justify-center p-6">

    <div
        class="max-w-md w-full space-y-8 bg-white dark:bg-zinc-800 p-8 rounded-2xl shadow-xl border border-zinc-200 dark:border-zinc-700">
        <div class="text-center">
            <div class="mx-auto w-16 h-16 bg-zinc-900 dark:bg-white rounded-xl flex items-center justify-center mb-6">
                <svg class="w-8 h-8 text-white dark:text-zinc-900" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18">
                    </path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold tracking-tight">Twickenham Parking</h1>
            <p class="mt-2 text-zinc-500 dark:text-zinc-400">Official Convention Parking Management</p>
        </div>

        <div class="space-y-4 pt-4">
            @auth
                <div class="text-center space-y-4">
                    <div class="p-4 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg">
                        <p class="text-sm text-zinc-500">Logged in as</p>
                        <p class="font-medium">{{ auth()->user()->name }}</p>
                    </div>

                    <div class="grid gap-3">
                        <a href="{{ route('dashboard') }}"
                            class="block w-full py-3 px-4 rounded-lg bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 font-semibold text-center hover:opacity-90 transition">
                            Go to Dashboard
                        </a>

                        @if(auth()->user()->role === 'attendant')
                            <a href="{{ route('attendant.scan') }}"
                                class="block w-full py-3 px-4 rounded-lg bg-indigo-600 text-white font-semibold text-center hover:bg-indigo-700 transition">
                                Open Scanner
                            </a>
                        @endif
                    </div>
                </div>
            @else
                <div class="space-y-4">
                    <a href="{{ route('login') }}"
                        class="block w-full py-3 px-4 rounded-lg bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 font-semibold text-center hover:opacity-90 transition shadow-lg">
                        Log In
                    </a>

                    <div class="text-center text-sm text-zinc-500 pt-4">
                        <p>Staff Login: <strong>staff@twickenham.com</strong></p>
                        <p>Password: <strong>remote</strong></p>
                    </div>
                </div>
            @endauth
        </div>

        <div class="pt-6 text-center text-xs text-zinc-400">
            &copy; {{ date('Y') }} Twickenham Stadium.
        </div>
    </div>

</body>

</html>