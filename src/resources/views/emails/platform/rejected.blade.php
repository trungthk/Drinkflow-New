@extends('emails.platform.layout')
@section('title', __('platform.emails.rejected_subject', ['app' => config('app.name', 'DrinkFlow')]))
@section('content')
    <p>{{ __('platform.emails.rejected_intro') }}</p>
    <div class="box"><strong>{{ __('platform.emails.reason') }}:</strong> {{ $reason }}</div>
    <p class="muted">{{ __('platform.emails.rejected_contact') }}</p>
@endsection
