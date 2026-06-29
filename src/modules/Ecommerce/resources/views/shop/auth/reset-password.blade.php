@extends('ecommerce::layouts.shop-wowy')

@section('title', 'Restablecer contrasena')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header text-center">
                        <h5 class="mb-0">Restablecer contrasena</h5>
                        <p class="small text-muted mb-0">Crea una nueva contrasena para tu cuenta</p>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')

                        <form action="{{ route('ecommerce.password.update') }}" method="POST">
                            @csrf

                            <input type="hidden" name="token" value="{{ $token }}">

                            <div class="mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $email) }}" required autofocus>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nueva contrasena <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Confirmar contrasena <span class="text-danger">*</span></label>
                                <input type="password" name="password_confirmation" class="form-control" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Restablecer contrasena</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
