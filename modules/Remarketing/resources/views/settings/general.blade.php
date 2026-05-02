@extends('layouts.theme')

@section('title', 'Configuración — Remarketing')

@section('page_header')
    @include('core::components.card', ['title' => 'Configuración de Remarketing'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="row g-3">

        <div class="col-12 col-lg-8">
            <form action="{{ route('settings.remarketing.update') }}" method="POST">
                @csrf
                @method('PATCH')

                {{-- Email provider --}}
                <div class="card mb-3">
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Proveedor de envío</h5>
                        <small class="text-muted">Selecciona el proveedor de email para el módulo Remarketing</small>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">

                            <div class="col-12">
                                <label class="form-label">Proveedor <span class="text-danger">*</span></label>
                                <select name="provider" class="form-select @error('provider') is-invalid @enderror">
                                    <option value="mailrelay" {{ $get('provider') === 'mailrelay' ? 'selected' : '' }}>Mailrelay</option>
                                    <option value="ses"       {{ $get('provider') === 'ses'       ? 'selected' : '' }}>Amazon SES</option>
                                    <option value="sendgrid"  {{ $get('provider') === 'sendgrid'  ? 'selected' : '' }}>SendGrid</option>
                                    <option value="mailgun"   {{ $get('provider') === 'mailgun'   ? 'selected' : '' }}>Mailgun</option>
                                </select>
                                @error('provider') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                        </div>
                    </div>
                </div>

                {{-- Sender identity --}}
                <div class="card mb-3">
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Identidad del remitente por defecto</h5>
                        <small class="text-muted">Se usará cuando la campaña no especifique remitente</small>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label">Nombre del remitente</label>
                                <input type="text" name="from_name" class="form-control @error('from_name') is-invalid @enderror"
                                       value="{{ old('from_name', $get('from_name')) }}"
                                       placeholder="Mi Tienda">
                                @error('from_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Email del remitente</label>
                                <input type="email" name="from_email" class="form-control @error('from_email') is-invalid @enderror"
                                       value="{{ old('from_email', $get('from_email')) }}"
                                       placeholder="noreply@mitienda.com">
                                @error('from_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                        </div>
                    </div>
                </div>

                {{-- Tracking --}}
                <div class="card mb-3">
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Tracking</h5>
                        <small class="text-muted">Dominio de seguimiento para clicks y aperturas</small>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">

                            <div class="col-12">
                                <label class="form-label">Dominio de tracking</label>
                                <input type="text" name="tracking_domain" class="form-control @error('tracking_domain') is-invalid @enderror"
                                       value="{{ old('tracking_domain', $get('tracking_domain')) }}"
                                       placeholder="click.mitienda.com">
                                <div class="form-text">Configura un CNAME apuntando a este servidor. Deja vacío para usar el dominio principal.</div>
                                @error('tracking_domain') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                        </div>
                    </div>
                </div>

                {{-- Save --}}
                <div class="card">
                    <div class="card-footer d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Guardar configuración
                        </button>
                    </div>
                </div>

            </form>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Sobre los proveedores</h6>
                    <p class="small text-muted mb-3">Remarketing usa el sistema multi-provider de Mailrelay. Elige el proveedor que tengas configurado en los ajustes de Mailrelay.</p>
                    <ul class="list-unstyled small text-muted">
                        <li class="mb-1"><i class="fas fa-circle-dot text-primary me-1"></i> Mailrelay — plataforma propia</li>
                        <li class="mb-1"><i class="fas fa-circle-dot text-warning me-1"></i> Amazon SES — alto volumen, bajo coste</li>
                        <li class="mb-1"><i class="fas fa-circle-dot text-success me-1"></i> SendGrid — entregabilidad premium</li>
                        <li class="mb-1"><i class="fas fa-circle-dot text-info me-1"></i> Mailgun — API flexible</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>

@endsection
