@extends('ecommerce::layouts.shop-wowy')

@section('title', 'Mi perfil')

@section('breadcrumb')
<div class="page-header breadcrumb-wrap">
    <div class="container">
        <div class="breadcrumb">
            <a href="{{ route('shop.index') }}" rel="nofollow"><i class="fa fa-home mr-5"></i>{{ __('Inicio') }}</a>
            <span></span> <a href="{{ route('account.dashboard') }}">{{ __('Mi cuenta') }}</a>
            <span></span> {{ __('Mi perfil') }}
        </div>
    </div>
</div>
@endsection

@section('content')
<section class="mt-60 mb-60">
    <div class="container">
        <div class="row">

            {{-- Sidebar --}}
            <div class="col-lg-3 mb-4">
                <div class="card">
                    <div class="list-group list-group-flush">
                        <a href="{{ route('account.dashboard') }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-tachometer-alt me-2"></i>{{ __('Dashboard') }}
                        </a>
                        <a href="{{ route('account.orders') }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-box me-2"></i>{{ __('Mis ordenes') }}
                        </a>
                        <a href="{{ route('account.profile') }}" class="list-group-item list-group-item-action active">
                            <i class="fas fa-user me-2"></i>{{ __('Mi perfil') }}
                        </a>
                        <a href="{{ route('account.addresses') }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-map-marker-alt me-2"></i>{{ __('Mis direcciones') }}
                        </a>
                        <a href="{{ route('ecommerce.logout') }}"
                           class="list-group-item list-group-item-action text-danger"
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="fas fa-sign-out-alt me-2"></i>{{ __('Cerrar sesion') }}
                        </a>
                        <form id="logout-form" action="{{ route('ecommerce.logout') }}" method="POST" class="d-none">@csrf</form>
                    </div>
                </div>
            </div>

            {{-- Main content --}}
            <div class="col-lg-9">

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show mb-4">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show mb-4">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                {{-- Personal data --}}
                <div class="card mb-4">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">{{ __('Datos personales') }}</h6>
                    </div>
                    <form action="{{ route('account.profile.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('Nombre completo') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                           value="{{ old('name', $customer->name) }}" required>
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('Telefono') }}</label>
                                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                           value="{{ old('phone', $customer->phone ?? '') }}">
                                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label">{{ __('Email') }}</label>
                                    <input type="email" class="form-control bg-light" value="{{ $customer->email }}" readonly disabled>
                                    <div class="form-text">{{ __('El email no se puede cambiar.') }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer border-top bg-white">
                            <button type="submit" class="btn btn-primary w-100 mb-2">{{ __('Guardar cambios') }}</button>
                        </div>
                    </form>
                </div>

                {{-- Change password --}}
                <div class="card mb-4">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">{{ __('Cambiar contrasena') }}</h6>
                    </div>
                    <form action="{{ route('account.password.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">{{ __('Contrasena actual') }} <span class="text-danger">*</span></label>
                                    <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required>
                                    @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('Nueva contrasena') }} <span class="text-danger">*</span></label>
                                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('Confirmar contrasena') }} <span class="text-danger">*</span></label>
                                    <input type="password" name="password_confirmation" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer border-top bg-white">
                            <button type="submit" class="btn btn-primary w-100 mb-2">{{ __('Cambiar contrasena') }}</button>
                        </div>
                    </form>
                </div>

                {{-- Privacy & GDPR --}}
                <div class="card">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">{{ __('Privacidad y datos personales') }}</h6>
                    </div>
                    <div class="card-body">
                        @if(session('warning'))
                            <div class="alert alert-warning alert-dismissible fade show mb-3">
                                {{ session('warning') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <h6 class="mb-1">{{ __('Exportar mis datos (GDPR)') }}</h6>
                        <p class="text-muted small mb-3">
                            {{ __('Solicita una copia de todos tus datos personales: perfil, órdenes, direcciones, lista de deseos y reseñas. Te enviaremos un enlace de descarga por correo en unos minutos.') }}
                        </p>
                        <form method="POST" action="{{ route('account.gdpr.request') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="fas fa-download me-2"></i>{{ __('Descargar mis datos') }}
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>
@endsection
