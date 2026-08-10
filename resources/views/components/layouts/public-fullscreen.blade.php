<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="h-svh overflow-hidden bg-white antialiased dark:bg-zinc-900">
    <div class="flex h-full min-h-0 w-full min-w-0 flex-col p-2 sm:p-3">
        {{ $slot }}
    </div>
    @fluxScripts
</body>

</html>
