@extends('layouts.theme')

@section('title', 'Afiliados')

@section('content')

    <div class="card">
        <div class="card-header p-4 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Afiliados</h5>
                    <p class="small mb-0 text-muted">Gestiona tu programa de afiliados y comisiones</p>
                </div>
            </div>
        </div>

        <div class="card-body">
            @if(isset($affiliates) && $affiliates->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Código</th>
                                <th>Referidos</th>
                                <th>Comisión</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($affiliates as $affiliate)
                                <tr>
                                    <td>{{ $affiliate->name ?? '—' }}</td>
                                    <td>{{ $affiliate->email ?? '—' }}</td>
                                    <td><code>{{ $affiliate->code ?? '—' }}</code></td>
                                    <td>{{ $affiliate->referrals_count ?? 0 }}</td>
                                    <td>${{ number_format($affiliate->total_commission ?? 0, 2) }}</td>
                                    <td>
                                        <span class="badge bg-{{ ($affiliate->status ?? 'inactive') === 'active' ? 'success' : 'secondary' }}-subtle text-{{ ($affiliate->status ?? 'inactive') === 'active' ? 'success' : 'secondary' }}">
                                            {{ $affiliate->status ?? 'inactive' }}
                                        </span>
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
                            <i class="fas fa-users fs-2"></i>
                        </div>
                        <h6 class="mb-1">Sin afiliados</h6>
                        <p class="text-muted mb-0">El programa de afiliados aún no está configurado</p>
                    </div>
                </div>
            @endif
        </div>

        @if(isset($affiliates) && method_exists($affiliates, 'hasPages') && $affiliates->hasPages())
            <div class="card-footer">{{ $affiliates->links() }}</div>
        @endif
    </div>

@endsection
