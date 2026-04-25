@extends('ecommerce::layouts.shop-wowy')

@section('title', 'Crear cuenta')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header text-center">
                        <h5 class="mb-0">Crear cuenta</h5>
                        <p class="small text-muted mb-0">Registrate como cliente</p>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')

                        <form action="{{ route('ecommerce.register') }}" method="POST">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Telefono</label>
                                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}">
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Contrasena <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Confirmar contrasena <span class="text-danger">*</span></label>
                                <input type="password" name="password_confirmation" class="form-control" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Registrarse</button>
                        </form>
                    </div>
                    <div class="card-footer text-center">
                        <small class="text-muted">Ya tienes cuenta? <a href="{{ route('ecommerce.login') }}">Inicia sesion</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
