{{-- Result of a server-side form submit: success message from session('status') or the first validation error. --}}
@if (session('status'))
    <div class="sa-notice success is-visible" role="status">{{ session('status') }}</div>
@elseif ($errors->any())
    <div class="sa-notice error is-visible" role="alert">{{ $errors->first() }}</div>
@endif
