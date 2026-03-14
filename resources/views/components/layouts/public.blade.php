<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white antialiased dark:bg-zinc-900">
    <flux:main class="py-6 sm:py-12 px-4 sm:px-6 min-w-0">
        {{ $slot }}
    </flux:main>
    @fluxScripts
</body>

</html>