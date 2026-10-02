@extends('emails.platform.layout')
@section('title', __('platform.emails.activated_subject', ['app' => config('app.name', 'DrinkFlow')]))
@section('content')
    <p>{{ __('platform.emails.activated_intro') }}</p>
    <div class="box">
        <p style="margin: 0;"><strong>{{ __('platform.emails.activated_package_label') }}</strong> {{ $packageName }}</p>
        <p style="margin: 6px 0 0;"><strong>{{ __('platform.emails.activated_room_limit_label') }}</strong> {{ $roomLimit }}</p>
    </div>
    <p>{{ __('platform.emails.activated_next_step') }}</p>
    <p style="text-align: center;"><a class="button" href="{{ route('admin.login.page') }}">{{ __('platform.emails.sign_in_button') }}</a></p>
    <p class="muted">{{ __('platform.emails.activated_security_note') }}</p>
@endsection
