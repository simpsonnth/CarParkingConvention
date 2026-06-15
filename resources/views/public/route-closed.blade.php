<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
    <title>{{ $pageTitle }} — {{ config('app.name') }}</title>
</head>

<body class="min-h-screen bg-white antialiased dark:bg-zinc-900">
    <main class="py-6 sm:py-12 px-4 sm:px-6 min-w-0">
        <div class="min-h-[70vh] flex flex-col items-center justify-center p-4 sm:p-6">
            <div class="w-full max-w-lg bg-white dark:bg-zinc-800 rounded-3xl shadow-xl p-6 sm:p-10 border border-zinc-100 dark:border-zinc-700 text-center">
                <div class="flex flex-wrap justify-center gap-2 mb-8">
                    <a href="{{ route('locale.set', 'en') }}" class="text-sm font-medium px-3 py-1.5 rounded-lg {{ app()->getLocale() === 'en' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">English</a>
                    <a href="{{ route('locale.set', 'pt') }}" class="text-sm font-medium px-3 py-1.5 rounded-lg {{ app()->getLocale() === 'pt' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">Português</a>
                    <a href="{{ route('locale.set', 'es') }}" class="text-sm font-medium px-3 py-1.5 rounded-lg {{ app()->getLocale() === 'es' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">Español</a>
                </div>

                <div class="mx-auto mb-6 flex size-16 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30">
                    <svg class="size-8 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 5c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z" />
                    </svg>
                </div>

                <h1 class="text-2xl sm:text-3xl font-black text-zinc-900 dark:text-white tracking-tight mb-3">
                    {{ __('routes_list.closed_heading') }}
                </h1>
                <p class="text-base text-zinc-600 dark:text-zinc-300 leading-relaxed">
                    {{ $message }}
                </p>

                <div class="mt-8">
                    <a href="{{ route('home') }}" class="inline-flex items-center justify-center rounded-xl border border-zinc-200 dark:border-zinc-600 px-5 py-2.5 text-sm font-semibold text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-900 transition">
                        {{ __('routes_list.closed_back_home') }}
                    </a>
                </div>
            </div>
        </div>
    </main>
</body>

</html>
