<x-admin.layout :title="$title ?? trim($__env->yieldContent('title'))" :active="$active ?? trim($__env->yieldContent('active')) ?: 'dashboard'" :room="$room ?? null">
    {{ $slot ?? '' }}
    @yield('content')
</x-admin.layout>
