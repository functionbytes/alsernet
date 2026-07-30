@extends('layouts.theme')

@section('title', 'Autenticación de Dos Factores')

@section('page_header')
    @include('core::components.card', ['title' => 'Autenticación de Dos Factores'])
@endsection

@section('content')
<div class="px-3">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Autenticación de dos factores (2FA)</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">
                Añade una capa adicional de seguridad a tu cuenta requiriendo un código de verificación además de tu contraseña.
            </p>

            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Funcionalidad en desarrollo</strong><br>
                La autenticación de dos factores estará disponible próximamente.
            </div>

            {{-- TODO: Implement 2FA UI --}}
        </div>
    </div>
</div>
@endsection
