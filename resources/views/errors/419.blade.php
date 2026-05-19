<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('errors.419_title') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-zinc-50 dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 flex items-center justify-center p-6">
    <div class="max-w-md w-full text-center space-y-6 bg-white dark:bg-zinc-800 p-8 rounded-2xl shadow-xl border border-zinc-200 dark:border-zinc-700">
        <h1 class="text-2xl font-bold">{{ __('errors.419_title') }}</h1>
        <p class="text-zinc-600 dark:text-zinc-400">{{ __('errors.419_message') }}</p>
        <a href="{{ route('login', ['session_expired' => 1]) }}"
            class="inline-block w-full py-3 px-4 rounded-lg bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 font-semibold hover:opacity-90 transition">
            {{ __('errors.419_login') }}
        </a>
    </div>
</body>
</html>
