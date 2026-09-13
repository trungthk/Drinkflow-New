@props([
    'cols' => 5,
    'rows' => 5,
    'id' => null,
])

<tbody @if($id) id="{{ $id }}" @endif class="admin-table-skeleton animate-pulse divide-y divide-slate-100 dark:divide-slate-800/60">
    @for($r = 0; $r < $rows; $r++)
        <tr>
            @for($c = 0; $c < $cols; $c++)
                <td class="px-6 py-4 whitespace-nowrap">
                    @if($c === 0)
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-slate-200 dark:bg-slate-800 shrink-0"></div>
                            <div class="space-y-1.5 w-full max-w-[140px]">
                                <div class="h-3.5 bg-slate-200 dark:bg-slate-800 rounded w-3/4"></div>
                                <div class="h-2.5 bg-slate-100 dark:bg-slate-800/60 rounded w-1/2"></div>
                            </div>
                        </div>
                    @elseif($c === $cols - 1)
                        <div class="flex items-center justify-end gap-2">
                            <div class="h-7 w-16 bg-slate-200 dark:bg-slate-800 rounded-lg"></div>
                        </div>
                    @else
                        <div class="h-3.5 bg-slate-200 dark:bg-slate-800 rounded" style="width: {{ [45, 60, 75, 85, 50][$c % 5] }}%;"></div>
                    @endif
                </td>
            @endfor
        </tr>
    @endfor
</tbody>
