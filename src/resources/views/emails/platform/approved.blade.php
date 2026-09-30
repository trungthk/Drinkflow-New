@extends('emails.platform.layout')
@section('title', __('platform.emails.approved_subject', ['app' => config('app.name', 'DrinkFlow')]))
@section('content')
    <p>{{ __('platform.emails.approved_intro') }}</p>
    <div class="box">
        <strong>{{ $packageName }}</strong><br>
        {{ __('platform.packages.rooms_limit', ['count' => $roomLimit]) }}
    </div>
    <p style="text-align: center;"><a class="button" href="{{ route('admin.login.page') }}">{{ __('platform.emails.sign_in_button') }}</a></p>
@endsection
