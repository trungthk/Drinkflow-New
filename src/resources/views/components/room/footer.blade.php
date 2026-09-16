@props([
    'room' => null,
])

<footer class="w-full bg-white border-t border-slate-200/80 py-6 mt-12 text-xs text-slate-500">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-4">
    <div class="flex items-center gap-2">
      <span>{{ __('room.footer.copyright', ['year' => date('Y')]) }}</span>
    </div>
    <div class="flex items-center gap-4">
      <a class="hover:text-slate-900 transition-colors" href="{{ route('terms') }}">{{ __('room.footer.order_policy') }}</a>
      <a class="hover:text-slate-900 transition-colors" href="{{ route('contact') }}">{{ __('room.footer.internal_support') }}</a>
    </div>
  </div>
</footer>
