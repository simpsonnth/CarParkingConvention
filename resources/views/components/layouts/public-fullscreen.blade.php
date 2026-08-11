<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body
    class="min-h-svh overflow-x-hidden overflow-y-auto bg-white antialiased dark:bg-zinc-900 lg:h-svh lg:overflow-hidden">
    <div class="mx-auto flex min-h-svh w-full min-w-0 max-w-7xl flex-col p-3 lg:h-full lg:min-h-0 lg:max-w-none lg:p-2 xl:p-3">
        {{ $slot }}
    </div>
    @fluxScripts
</body>

</html>
