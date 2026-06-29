@extends('ecommerce::layouts.shop-wowy')

@section('title', 'Recuperar contrasena')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header text-center">
                        <h5 class="mb-0">Recuperar contrasena</h5>
                        <p class="small text-muted mb-0">Te enviaremos un enlace para restablecerla</p>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')

                        <form action="{{ route('ecommerce.password.email') }}" method="POST">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required autofocus>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Enviar enlace</button>
                        </form>
                    </div>
                    <div class="card-footer text-center">
                        <small class="text-muted"><a href="{{ route('ecommerce.login') }}">Volver al inicio de sesion</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
