@php
    $registerHref = '#';
    if (Route::has('shop.auth.register')) {
        $registerHref = route('shop.auth.register');
    }
@endphp
<a href="{{ $registerHref }}" class="btn btn-outline-primary">Registrarse</a>
