@extends('ecommerce::layouts.shop-wowy')

@section('title', 'Mis direcciones')

@section('breadcrumb')
<div class="page-header breadcrumb-wrap">
    <div class="container">
        <div class="breadcrumb">
            <a href="{{ route('shop.index') }}" rel="nofollow"><i class="fa fa-home mr-5"></i>{{ __('Inicio') }}</a>
            <span></span> <a href="{{ route('account.dashboard') }}">{{ __('Mi cuenta') }}</a>
            <span></span> {{ __('Mis direcciones') }}
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
                        <a href="{{ route('account.profile') }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-user me-2"></i>{{ __('Mi perfil') }}
                        </a>
                        <a href="{{ route('account.addresses') }}" class="list-group-item list-group-item-action active">
                            <i class="fas fa-map-marker-alt me-2"></i>{{ __('Mis direcciones') }}
                        </a>
                        <a href="{{ route('account.saved-searches.index') }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-bell me-2"></i>{{ __('Busquedas guardadas') }}
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

                {{-- Saved addresses --}}
                @if($addresses->isEmpty())
                    <div class="card mb-4">
                        <div class="card-body text-center py-5 text-muted">
                            <i class="fas fa-map-marker-alt fa-3x mb-3 d-block"></i>
                            {{ __('Aun no tienes direcciones guardadas.') }}
                        </div>
                    </div>
                @else
                    <div class="row g-3 mb-4">
                        @foreach($addresses as $address)
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header p-3 border-bottom d-flex justify-content-between align-items-center">
                                        <span class="fw-semibold small">{{ $address->name }}</span>
                                        @if($address->is_default)
                                            <span class="badge bg-success">{{ __('Principal') }}</span>
                                        @endif
                                    </div>
                                    <div class="card-body small text-muted">
                                        @if($address->phone)
                                            <div><i class="fas fa-phone me-1"></i> {{ $address->phone }}</div>
                                        @endif
                                        <div><i class="fas fa-map-marker-alt me-1"></i> {{ $address->address }}</div>
                                        <div>{{ $address->city }}@if($address->zip_code), {{ $address->zip_code }}@endif</div>
                                        <div>{{ $address->country }}</div>
                                    </div>
                                    <div class="card-footer border-top bg-white d-flex gap-2">
                                        @if(!$address->is_default)
                                            <form action="{{ route('account.addresses.default', $address) }}" method="POST" class="flex-fill">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-secondary btn-sm w-100">
                                                    {{ __('Hacer principal') }}
                                                </button>
                                            </form>
                                        @endif
                                        <form action="{{ route('account.addresses.destroy', $address) }}" method="POST" class="flex-fill"
                                              onsubmit="return confirm('{{ __('Eliminar esta direccion?') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-secondary btn-sm w-100">
                                                {{ __('Eliminar') }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Add new address --}}
                <div class="card">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">{{ __('Agregar nueva direccion') }}</h6>
                    </div>
                    <form action="{{ route('account.addresses.store') }}" method="POST">
                        @csrf
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('Nombre completo') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                           value="{{ old('name') }}" required>
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('Telefono') }}</label>
                                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                           value="{{ old('phone') }}">
                                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label">{{ __('Direccion') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="address" class="form-control @error('address') is-invalid @enderror"
                                           value="{{ old('address') }}" required>
                                    @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('Ciudad') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="city" class="form-control @error('city') is-invalid @enderror"
                                           value="{{ old('city') }}" required>
                                    @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('Pais') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="country" class="form-control @error('country') is-invalid @enderror"
                                           value="{{ old('country') }}" required>
                                    @error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('Codigo postal') }}</label>
                                    <input type="text" name="zip_code" class="form-control @error('zip_code') is-invalid @enderror"
                                           value="{{ old('zip_code') }}">
                                    @error('zip_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input type="checkbox" name="is_default" value="1" class="form-check-input" id="is_default"
                                               {{ old('is_default') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_default">{{ __('Establecer como direccion principal') }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer border-top bg-white">
                            <button type="submit" class="btn btn-primary w-100 mb-2">{{ __('Guardar direccion') }}</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</section>
@endsection
