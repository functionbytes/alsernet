@php
    $loginHref = '#';
    if (Route::has('shop.auth.login')) {
        $loginHref = route('shop.auth.login');
    }
@endphp
<a href="{{ $loginHref }}" class="btn btn-primary">Iniciar sesion</a>
