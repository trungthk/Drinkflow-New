{{--
    Text label that stays collapsed (zero width) until the parent `.group` element is hovered or keyboard-focused,
    then expands smoothly to its natural width. Pair it with an icon and give the parent an aria-label/title.
--}}
<span {{ $attributes->merge(['class' => 'grid grid-cols-[0fr] opacity-0 transition-[grid-template-columns,opacity,margin] duration-300 ease-out group-hover:ml-2 group-hover:grid-cols-[1fr] group-hover:opacity-100 group-focus-visible:ml-2 group-focus-visible:grid-cols-[1fr] group-focus-visible:opacity-100']) }}>
    <span class="min-w-0 overflow-hidden whitespace-nowrap">{{ $slot }}</span>
</span>
