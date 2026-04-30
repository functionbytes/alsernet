@extends('layouts.theme')

@section('title', $teamRole->name)

@section('content')

    @include('core::components.alerts')

    <div class="row">
        <div class="col-lg-8">
            <!-- Role Details Card -->
            <div class="card mb-3">
                <div class="card-header p-4 border-bottom border-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">{{ $teamRole->name }}</h5>
                            @if($teamRole->is_default)
                                <span class="badge bg-success-subtle text-success">Por defecto</span>
                            @endif
                        </div>
                        <div>
                            <a href="{{ route('settings.chat.team-roles.edit', $teamRole) }}" class="btn btn-primary">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">Detalles del rol</h6>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Nombre</h6>
                            <p class="mb-0">{{ $teamRole->name }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Descripción</h6>
                            <p class="mb-0">{{ $teamRole->description ?? 'Sin descripción' }}</p>
                        </div>
                    </div>

                    <h6 class="fw-bold mb-3 border-bottom pb-2">Permisos ({{ count($teamRole->permissions ?? []) }})</h6>

                    @php
                        $groupedPermissions = [];
                        foreach ($availablePermissions as $key => $label) {
                            $parts = explode('.', $key);
                            $group = $parts[0];
                            if (!isset($groupedPermissions[$group])) {
                                $groupedPermissions[$group] = [];
                            }
                            $groupedPermissions[$group][$key] = $label;
                        }
                    @endphp

                    <div class="row">
                        @foreach($groupedPermissions as $group => $permissions)
                            <div class="col-md-6 mb-3">
                                <h6 class="text-uppercase text-muted small">{{ $group }}</h6>
                                @foreach($permissions as $key => $label)
                                    <div class="mb-2">
                                        @if(in_array($key, $teamRole->permissions ?? []))
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                        @else
                                            <i class="fas fa-times-circle text-muted me-2"></i>
                                        @endif
                                        <span class="{{ in_array($key, $teamRole->permissions ?? []) ? '' : 'text-muted' }}">
                                            {{ $label }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Statistics Card -->
            <div class="card mb-3">
                <div class="card-header p-4 border-bottom border-light">
                    <h6 class="mb-0 fw-bold">Estadísticas</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Creado</h6>
                        <p class="mb-0">{{ $teamRole->created_at->format('d/m/Y H:i') }}</p>
                        <small class="text-muted">{{ $teamRole->created_at->diffForHumans() }}</small>
                    </div>

                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Última actualización</h6>
                        <p class="mb-0">{{ $teamRole->updated_at->format('d/m/Y H:i') }}</p>
                        <small class="text-muted">{{ $teamRole->updated_at->diffForHumans() }}</small>
                    </div>

                    <hr>

                    <div>
                        <h6 class="text-muted mb-1">Usuarios asignados</h6>
                        <h4 class="mb-0">{{ $teamRole->users_count ?? 0 }}</h4>
                    </div>
                </div>
            </div>

            <!-- Actions Card -->
            <div class="card">
                <div class="card-header p-4 border-bottom border-light">
                    <h6 class="mb-0 fw-bold">Acciones</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('settings.chat.team-roles.edit', $teamRole) }}" class="btn btn-outline-primary">
                            <i class="fas fa-edit"></i> Editar rol
                        </a>
                        <a href="{{ route('settings.chat.team-roles.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver al listado
                        </a>
                        @if(($teamRole->users_count ?? 0) == 0)
                            <hr>
                            <button type="button"
                                class="btn btn-outline-danger delete-btn"
                                data-bs-toggle="modal"
                                data-bs-target="#delete-modal"
                                data-url="{{ route('settings.chat.team-roles.destroy', $teamRole) }}"
                                data-title="Eliminar rol: {{ $teamRole->name }}">
                                <i class="fas fa-trash"></i> Eliminar rol
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('core::components.delete')

@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Delete modal functionality
            $('.delete-btn').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const deleteUrl = $(this).data('url');
                const deleteTitle = $(this).data('title');

                $('#delete-modal .modal-title').text(deleteTitle);
                $('#delete-form').attr('action', deleteUrl);

                const deleteModal = new bootstrap.Modal(document.getElementById('delete-modal'));
                deleteModal.show();
            });

            @if (session('success'))
            toastr.success('{{ session('success') }}', 'Éxito');
            @endif

            @if (session('error'))
            toastr.error('{{ session('error') }}', 'Error');
            @endif
        });
    </script>
@endpush
