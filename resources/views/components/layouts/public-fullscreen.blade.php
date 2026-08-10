<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="h-dvh overflow-hidden bg-white antialiased dark:bg-zinc-900">
    {{-- Plain shell (not flux:main) so the board can fill the viewport without Flux app-shell grid chrome. --}}
    <div class="flex h-full min-h-0 min-w-0 flex-col p-2 sm:p-3">
        {{ $slot }}
    </div>
    @fluxScripts
</body>

</html>
