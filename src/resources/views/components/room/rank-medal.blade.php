@props(['rank'])

@php
    // Bảng màu huy chương (vàng, bạc, đồng...). Giữ đồng bộ với `medalClasses` trong user/campaign.blade.php,
    // nơi modal "Top món yêu thích" được render bằng Alpine.
    $palette = [
        1 => 'text-amber-500',
        2 => 'text-slate-400',
        3 => 'text-orange-700',
        4 => 'text-emerald-600',
        5 => 'text-emerald-400',
    ];
    $rankLabel = __('room.dashboard.rank_label', ['rank' => $rank]);
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex h-6 w-6 shrink-0 items-center justify-center ' . ($palette[$rank] ?? 'text-slate-300')]) }}
      role="img" title="{{ $rankLabel }}" aria-label="{{ $rankLabel }}">
    <span class="material-symbols-outlined text-[24px]" style="font-variation-settings: 'FILL' 1;" aria-hidden="true">military_tech</span>
</span>
