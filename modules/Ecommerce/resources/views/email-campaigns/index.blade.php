@extends('layouts.theme')

@section('title', 'Campañas de email')

@section('content')

    <div class="card">
        <div class="card-header p-4 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Campañas de email</h5>
                    <p class="small mb-0 text-muted">Crea y gestiona campañas de email marketing para tu tienda</p>
                </div>
                @if(\Route::has('ecommerce.email-campaigns.create'))
                    <a href="{{ route('ecommerce.email-campaigns.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nueva campaña
                    </a>
                @endif
            </div>
        </div>

        <div class="card-body">
            @if(isset($campaigns) && $campaigns->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre</th>
                                <th>Asunto</th>
                                <th>Segmento</th>
                                <th>Enviados</th>
                                <th>Aperturas</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($campaigns as $campaign)
                                <tr>
                                    <td class="fw-semibold">{{ $campaign->name }}</td>
                                    <td class="text-muted small">{{ Str::limit($campaign->subject ?? '—', 40) }}</td>
                                    <td>{{ $campaign->segment ?? '—' }}</td>
                                    <td>{{ number_format($campaign->sent_count ?? 0) }}</td>
                                    <td>{{ number_format($campaign->opened_count ?? 0) }}</td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary">
                                            {{ $campaign->status ?? 'draft' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="javascript:void(0)" class="text-muted" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-vertical"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="#">Ver detalle</a></li>
                                                <li><a class="dropdown-item" href="#">Editar</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <div class="d-flex flex-column align-items-center">
                        <div class="mb-3 text-muted">
                            <i class="fas fa-envelope-open-text fs-2"></i>
                        </div>
                        <h6 class="mb-1">Sin campañas de email</h6>
                        <p class="text-muted mb-0">Crea tu primera campaña para empezar a enviar emails masivos</p>
                    </div>
                </div>
            @endif
        </div>

        @if(isset($campaigns) && method_exists($campaigns, 'hasPages') && $campaigns->hasPages())
            <div class="card-footer">{{ $campaigns->links() }}</div>
        @endif
    </div>

@endsection
