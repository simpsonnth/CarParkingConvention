@props([
    'class' => 'space-y-6',
])

<form
    {{ $attributes->class($class) }}
    x-data="attendantCheckInGeo"
    @submit.prevent="submitWithLocation"
>
    {{ $slot }}
</form>
