@extends('layouts.theme')

@section('title', 'Editar tienda')

@section('page_header')
    @include('core::components.card', ['title' => 'Editar tienda'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="row g-3">

        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('remarketing.stores.update', $store) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">{{ $store->name }}</h5>
                        <small class="text-muted">{{ ucfirst($store->platform) }} — {{ $store->domain }}</small>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">

                            <div class="col-12">
                                <label class="form-label">Nombre de la tienda <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $store->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Configuración avanzada (JSON)</label>
                                <textarea name="settings_raw" rows="8"
                                          class="form-control font-monospace @error('settings_raw') is-invalid @enderror"
                                          placeholder='{"webhook_secret": "...", "sync_interval_minutes": 30}'>{{ old('settings_raw', json_encode($store->settings, JSON_PRETTY_PRINT)) }}</textarea>
                                @error('settings_raw')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Edita la configuración en formato JSON. Los cambios se aplican en la siguiente sincronización.</div>
                            </div>

                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save me-1"></i> Guardar cambios
                        </button>
                        <a href="{{ route('remarketing.stores.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Información de la tienda</h6>
                    <dl class="row small mb-0">
                        <dt class="col-5 text-muted">Plataforma</dt>
                        <dd class="col-7">{{ ucfirst($store->platform) }}</dd>
                        <dt class="col-5 text-muted">Dominio</dt>
                        <dd class="col-7">{{ $store->domain }}</dd>
                        <dt class="col-5 text-muted">Estado</dt>
                        <dd class="col-7">
                            @if($store->status === 'active')
                                <span class="badge bg-success-subtle text-success">Activa</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">{{ $store->status }}</span>
                            @endif
                        </dd>
                        <dt class="col-5 text-muted">Última sync</dt>
                        <dd class="col-7">{{ $store->last_synced_at?->diffForHumans() ?? 'Nunca' }}</dd>
                        <dt class="col-5 text-muted">Creada</dt>
                        <dd class="col-7">{{ $store->created_at->format('d/m/Y') }}</dd>
                    </dl>
                </div>
            </div>
        </div>

    </div>

@endsection
