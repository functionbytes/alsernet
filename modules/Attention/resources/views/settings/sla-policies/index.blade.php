@extends('layouts.theme')

@section('title', 'Políticas SLA')

@section('content')

    @include('core::components.card', ['title' => 'Políticas SLA'])

    <div class="widget-content searchable-container list">
        <div class="card card-body">
            <div class="row">
                <div class="col-md-12 col-xl-12">
                    <div class="d-flex justify-content-end">
                        <a href="{{ route('settings.attention.sla-policies.create') }}" class="btn btn-success" data-bs-toggle="tooltip" data-bs-placement="top" title="Nueva política SLA">
                            <i class="fa-duotone fa-plus"></i> Nuevo
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-body">
            <div class="table-responsive">
                <table class="table search-table align-middle text-nowrap">
                    <thead class="header-item">
                        <tr>
                            <th>Nombre</th>
                            <th class="text-center">Respuesta</th>
                            <th class="text-center">Resolución</th>
                            <th class="text-center">Cierre</th>
                            <th class="text-center">Escalación</th>
                            <th class="text-center">PQRSF</th>
                            <th class="text-center">Incumplimientos</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($policies as $policy)
                            <tr class="search-items">
                                <td>
                                    <div>
                                        <strong>{{ $policy->name }}</strong>
                                        @if($policy->is_default)
                                            <span class="badge bg-light-primary text-primary ms-2">Por defecto</span>
                                        @endif
                                        @if($policy->description)
                                            <br>
                                            <small class="text-muted">{{ Str::limit($policy->description, 60) }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light-info text-info">
                                        {{ round($policy->response_time / 60, 1) }}h
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light-warning text-warning">
                                        {{ round($policy->resolution_time / 60, 1) }}h
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light-success text-success">
                                        {{ round($policy->closure_time / 60, 1) }}h
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($policy->enable_escalation)
                                        <span class="badge bg-light-danger text-danger">
                                            {{ $policy->escalation_threshold_percent }}%
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark">{{ $policy->attentions_count }}</span>
                                </td>
                                <td class="text-center">
                                    @if($policy->breaches_count > 0)
                                        <span class="badge bg-danger">{{ $policy->breaches_count }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($policy->active)
                                        <span class="badge bg-light-success text-success">Activo</span>
                                    @else
                                        <span class="badge bg-light-secondary text-secondary">Inactivo</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-ellipsis"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('settings.attention.sla-policies.show', $policy->id) }}">
                                                    Ver detalles
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('settings.attention.sla-policies.edit', $policy->id) }}">
                                                    Editar
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a
                                                    class="dropdown-item text-danger delete-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#delete-modal"
                                                    data-url="{{ route('settings.attention.sla-policies.destroy', $policy->id) }}"
                                                    data-title="Eliminar política: {{ $policy->name }}">
                                                    Eliminar
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fa-duotone fa-inbox fa-3x mb-3"></i>
                                        <p>No se encontraron políticas SLA</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @include('core::components.delete')

@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Delete modal functionality
            $('.delete-btn').on('click', function() {
                const deleteUrl = $(this).data('url');
                const deleteTitle = $(this).data('title');

                $('#delete-modal .modal-title').text(deleteTitle);
                $('#delete-form').attr('action', deleteUrl);
            });

            // Initialize tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();

            @if (session('success'))
                toastr.success('{{ session('success') }}', 'Éxito');
            @endif

            @if (session('error'))
                toastr.error('{{ session('error') }}', 'Error');
            @endif
        });
    </script>
@endpush
