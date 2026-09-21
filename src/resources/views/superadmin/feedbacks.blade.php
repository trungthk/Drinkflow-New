@extends('superadmin.layout', ['title' => __('superadmin.feedbacks.title'), 'active' => 'feedbacks'])
@section('content')
<div class="superadmin-heading"><div><p class="superadmin-eyebrow">{{ __('superadmin.common.platform_data') }}</p><h1>{{ __('superadmin.feedbacks.title') }}</h1><p>{{ __('superadmin.feedbacks.description', ['rating' => \App\Models\Feedback::MIN_VISIBLE_RATING]) }}</p></div></div>
<div id="notice" class="sa-notice"></div>
<section class="sa-card sa-section">
    <div class="sa-section-header">
        <div><h2>{{ __('superadmin.feedbacks.queue') }}</h2><p>{{ __('superadmin.feedbacks.pending_count', ['count' => $pendingCount]) }} · {{ __('superadmin.common.records_count', ['count' => $feedbacks->total()]) }}</p></div>
        <form method="GET" class="superadmin-actions">
            <input name="q" value="{{ $filters['search'] }}" class="sa-input" placeholder="{{ __('superadmin.feedbacks.search') }}">
            <select name="status" class="sa-input">
                <option value="all" @selected($filters['status'] === 'all')>{{ __('superadmin.common.all_statuses') }}</option>
                <option value="inactive" @selected($filters['status'] === 'inactive')>{{ __('superadmin.feedbacks.status_inactive') }}</option>
                <option value="active" @selected($filters['status'] === 'active')>{{ __('superadmin.feedbacks.status_active') }}</option>
            </select>
            <select name="rating" class="sa-input">
                <option value="">{{ __('superadmin.feedbacks.all_ratings') }}</option>
                @for ($stars = 5; $stars >= 1; $stars--)
                    <option value="{{ $stars }}" @selected($filters['rating'] === $stars)>{{ $stars }} ★</option>
                @endfor
            </select>
            <button class="sa-button">{{ __('superadmin.common.filter') }}</button>
        </form>
    </div>
    <div class="sa-table-wrap"><table class="sa-table"><thead><tr>
        <th>{{ __('superadmin.common.user') }}</th><th>{{ __('superadmin.feedbacks.rating') }}</th><th>{{ __('superadmin.feedbacks.content') }}</th><th>{{ __('superadmin.common.time') }}</th><th>{{ __('superadmin.common.status') }}</th><th>{{ __('superadmin.common.actions') }}</th>
    </tr></thead><tbody>
    @forelse($feedbacks as $feedback)
        @php
            $isActive = $feedback->status === \App\Enums\FeedbackStatus::Active;
            $belowMinimum = $feedback->rating < \App\Models\Feedback::MIN_VISIBLE_RATING;
        @endphp
        <tr>
            <td><strong>{{ $feedback->user_display_name ?: __('global.feedback.anonymous_user') }}</strong><br><small>{{ $feedback->globalUser?->email }}{{ $feedback->department_name ? ' · '.$feedback->department_name : '' }}</small></td>
            <td><strong>{{ $feedback->rating }} ★</strong><br><small>{{ $feedback->subsystem_label }}</small></td>
            <td style="max-width: 28rem; white-space: pre-line; word-break: break-word;">{{ $feedback->content }}</td>
            <td>{{ $feedback->created_at?->format('H:i d/m/Y') }}</td>
            <td>
                <span class="status-pill {{ $isActive ? 'status-active' : 'status-pending' }}"><span class="status-dot"></span>{{ $isActive ? __('superadmin.feedbacks.status_active') : __('superadmin.feedbacks.status_inactive') }}</span>
                @if ($isActive && $belowMinimum)
                    <br><small>{{ __('superadmin.feedbacks.hidden_low_rating', ['rating' => \App\Models\Feedback::MIN_VISIBLE_RATING]) }}</small>
                @endif
            </td>
            <td>
                @if ($isActive)
                    <button class="sa-button" onclick="setFeedbackStatus({{ $feedback->id }}, 'inactive', this)">{{ __('superadmin.feedbacks.deactivate') }}</button>
                @else
                    <button class="sa-button" onclick="setFeedbackStatus({{ $feedback->id }}, 'active', this)">{{ __('superadmin.feedbacks.approve') }}</button>
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="6" class="sa-empty">{{ __('superadmin.feedbacks.empty') }}</td></tr>
    @endforelse
    </tbody></table></div>
    <div class="mt-4">{{ $feedbacks->links() }}</div>
</section>
@endsection
@push('scripts')<script>
async function setFeedbackStatus(id, status, button) {
    button.disabled = true;
    try {
        await dfApi(`/superadmin/feedbacks/${id}/status`, { method: 'PATCH', body: { status } });
        window.location.reload();
    } catch (error) {
        alert(error.message);
        button.disabled = false;
    }
}
</script>@endpush
