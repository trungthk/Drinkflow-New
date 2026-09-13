@props([
    'title' => null,
    'brandHero' => null,
])

<x-admin-auth.layout :title="$title">
    @if(isset($brandHero) && $brandHero->isNotEmpty())
        <x-slot name="brandHero">
            {{ $brandHero }}
        </x-slot>
    @endif
    {{ $slot }}
    @if(isset($scripts) && $scripts->isNotEmpty())
        <x-slot name="scripts">
            {{ $scripts }}
        </x-slot>
    @endif
</x-admin-auth.layout>
