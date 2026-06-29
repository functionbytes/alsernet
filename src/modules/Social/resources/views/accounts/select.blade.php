@extends('layouts.theme')

@section('title', 'Seleccionar Cuentas')

@section('content')

    <div class="widget-content searchable-container list">

        <!-- Main Card -->
        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">
                            <i class="fas fa-check-square me-2"></i>Seleccionar Cuentas
                        </h5>
                        <p class="small mb-0 text-muted">Selecciona las cuentas que deseas conectar a tu perfil</p>
                    </div>
                </div>
            </div>

            <!-- Selection Form -->
            <div class="card-body">
                <form action="{{ route('admin.social.accounts.save-selected') }}" method="POST">
                    @csrf

                    @if(count($accounts) > 0)
                        <div class="alert alert-info mb-4">
                            <i class="fas fa-info-circle me-2"></i>
                            Se encontraron <strong>{{ count($accounts) }}</strong> cuenta(s) de <strong>{{ ucfirst($network) }}</strong>.
                            Selecciona las que deseas agregar.
                        </div>

                        <div class="row g-3">
                            @foreach($accounts as $account)
                                <div class="col-md-6 col-lg-4">
                                    <div class="card border h-100 account-card">
                                        <div class="card-body">
                                            <div class="form-check">
                                                <input class="form-check-input account-checkbox"
                                                       type="checkbox"
                                                       name="selected[]"
                                                       value="{{ $account['network_id'] }}"
                                                       id="account_{{ $account['network_id'] }}"
                                                       checked>
                                                <label class="form-check-label w-100" for="account_{{ $account['network_id'] }}">
                                                    <div class="d-flex align-items-start gap-3">
                                                        @if(isset($account['avatar']) && $account['avatar'])
                                                            <img src="{{ $account['avatar'] }}"
                                                                 class="rounded-circle"
                                                                 style="width: 50px; height: 50px; object-fit: cover;"
                                                                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'50\' height=\'50\'%3E%3Crect fill=\'%23e9ecef\' width=\'50\' height=\'50\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' dominant-baseline=\'middle\' text-anchor=\'middle\' fill=\'%236c757d\' font-size=\'20\'%3E{{ strtoupper(substr($account['name'], 0, 1)) }}%3C/text%3E%3C/svg%3E'">
                                                        @else
                                                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center"
                                                                 style="width: 50px; height: 50px;">
                                                                <span class="fs-4 text-muted">{{ strtoupper(substr($account['name'], 0, 1)) }}</span>
                                                            </div>
                                                        @endif
                                                        <div class="flex-fill">
                                                            <h6 class="mb-1 fw-semibold">{{ $account['name'] }}</h6>
                                                            <small class="text-muted d-block">{{ '@' . $account['username'] }}</small>
                                                            @if(isset($account['category']) && $account['category'])
                                                                <small class="badge bg-light text-dark mt-1">{{ $account['category'] }}</small>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Selection Controls -->
                        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                            <div>
                                <button type="button" class="btn btn-sm btn-light me-2" id="select-all">
                                    <i class="fas fa-check me-1"></i> Seleccionar Todas
                                </button>
                                <button type="button" class="btn btn-sm btn-light" id="deselect-all">
                                    <i class="fas fa-times me-1"></i> Deseleccionar Todas
                                </button>
                            </div>
                            <div>
                                <a href="{{ route('admin.social.accounts.index') }}" class="btn btn-secondary me-2">
                                    <i class="fas fa-times me-1"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-check me-1"></i> Conectar Seleccionadas
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <i class="fas fa-exclamation-circle fs-1 text-muted"></i>
                            </div>
                            <h5 class="text-muted mb-2">No se encontraron cuentas</h5>
                            <p class="text-muted mb-4">No pudimos encontrar cuentas disponibles para conectar</p>
                            <a href="{{ route('admin.social.accounts.index') }}" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-1"></i> Volver
                            </a>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select all
    document.getElementById('select-all')?.addEventListener('click', function() {
        document.querySelectorAll('.account-checkbox').forEach(checkbox => {
            checkbox.checked = true;
        });
    });

    // Deselect all
    document.getElementById('deselect-all')?.addEventListener('click', function() {
        document.querySelectorAll('.account-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
    });

    // Visual feedback on card click
    document.querySelectorAll('.account-card').forEach(card => {
        card.addEventListener('click', function(e) {
            if (e.target.type !== 'checkbox') {
                const checkbox = this.querySelector('.account-checkbox');
                checkbox.checked = !checkbox.checked;
            }
        });
    });
});
</script>
@endpush

@push('styles')
<style>
.account-card {
    cursor: pointer;
    transition: all 0.2s;
}

.account-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.account-card .form-check-input:checked ~ .form-check-label {
    opacity: 1;
}

.account-card .form-check-input:not(:checked) ~ .form-check-label {
    opacity: 0.6;
}
</style>
@endpush
