<x-admin-auth.layout :title="$title ?? trim($__env->yieldContent('title'))">
    {{ $slot ?? '' }}
    @yield('content')
</x-admin-auth.layout>
