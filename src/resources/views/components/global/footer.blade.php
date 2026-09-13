@props([
    'activeTab' => null,
])

<!-- Global User Shared Footer -->
<x-public.footer :active-tab="$activeTab" :terms-url="route('terms')" :versions-url="route('versions')" />
