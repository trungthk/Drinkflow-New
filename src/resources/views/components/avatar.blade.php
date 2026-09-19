@props(['user', 'size' => 'md', 'class' => '', 'alt' => null])

@php
  $sizeClasses = match($size) {
    'xs' => 'w-6 h-6 text-[10px]',
    'sm' => 'w-8 h-8 text-xs',
    'md' => 'w-10 h-10 text-sm',
    'lg' => 'w-12 h-12 text-base',
    'xl' => 'w-16 h-16 text-lg',
    '2xl' => 'w-20 h-20 text-xl',
    default => 'w-10 h-10 text-sm',
  };

  $hasAvatar = !empty($user?->avatar_url);
  $userName = $user?->name ?? __('global.common.user');
  $firstLetter = strtoupper(mb_substr($userName, 0, 1));
  $altText = $alt ?? 'Avatar ' . $userName;
@endphp

@if ($hasAvatar)
  <img 
    class="rounded-full object-cover border border-slate-200/80 {{ $sizeClasses }} {{ $class }}"
    src="{{ $user->avatar_url }}"
    alt="{{ $altText }}"
    loading="lazy"
  >
@else
  <div class="rounded-full bg-[#006948] text-white font-bold flex items-center justify-center {{ $sizeClasses }} border border-slate-200/80 {{ $class }}">
    {{ $firstLetter }}
  </div>
@endif
