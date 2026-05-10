<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('errors.service_unavailable_title') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body
    class="h-full bg-zinc-50 dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 antialiased flex flex-col items-center justify-center p-4 sm:p-6">

    <div
        class="max-w-md w-full space-y-6 bg-white dark:bg-zinc-800 p-4 sm:p-8 rounded-2xl shadow-xl border border-zinc-200 dark:border-zinc-700">
        <div class="text-center">
            <div class="mx-auto w-16 h-16 bg-amber-100 dark:bg-amber-900/40 rounded-xl flex items-center justify-center mb-6">
                <svg class="w-8 h-8 text-amber-700 dark:text-amber-300" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                    </path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold tracking-tight">{{ __('errors.service_unavailable_title') }}</h1>
            <p class="mt-3 text-zinc-600 dark:text-zinc-400 leading-relaxed">{{ __('errors.service_unavailable_body') }}</p>
        </div>

        <p class="text-center text-xs text-zinc-400 dark:text-zinc-500 pt-2 border-t border-zinc-100 dark:border-zinc-700">
            {{ __('errors.service_unavailable_footer') }}
        </p>
    </div>

</body>

</html>
