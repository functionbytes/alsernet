@extends('layouts.theme')

@section('title', $slaPolicy->name)

@section('content')

    @include('core::components.alerts')

    <div class="row">
        <div class="col-lg-8">
            <!-- Policy Details Card -->
            <div class="card mb-3">
                <div class="card-header p-4 border-bottom border-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">{{ $slaPolicy->name }}</h5>
                            @if($slaPolicy->description)
                                <p class="small mb-0 text-muted">{{ $slaPolicy->description }}</p>
                            @endif
                        </div>
                        <div>
                            <a href="{{ route('settings.chat.sla-policies.edit', $slaPolicy) }}" class="btn btn-primary">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">Tiempos de respuesta</h6>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <h6 class="text-muted mb-2">Primera respuesta</h6>
                                @if($slaPolicy->first_response_time_minutes)
                                    <h4 class="mb-0 text-info">
                                        {{ floor($slaPolicy->first_response_time_minutes / 60) }}h
                                        {{ $slaPolicy->first_response_time_minutes % 60 }}m
                                    </h4>
                                    <small class="text-muted">{{ $slaPolicy->first_response_time_minutes }} minutos</small>
                                @else
                                    <p class="text-muted mb-0">No configurado</p>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <h6 class="text-muted mb-2">Siguiente respuesta</h6>
                                @if($slaPolicy->next_response_time_minutes)
                                    <h4 class="mb-0 text-info">
                                        {{ floor($slaPolicy->next_response_time_minutes / 60) }}h
                                        {{ $slaPolicy->next_response_time_minutes % 60 }}m
                                    </h4>
                                    <small class="text-muted">{{ $slaPolicy->next_response_time_minutes }} minutos</small>
                                @else
                                    <p class="text-muted mb-0">No configurado</p>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <h6 class="text-muted mb-2">Resolución</h6>
                                @if($slaPolicy->resolution_time_minutes)
                                    <h4 class="mb-0 text-warning">
                                        {{ floor($slaPolicy->resolution_time_minutes / 60) }}h
                                        {{ $slaPolicy->resolution_time_minutes % 60 }}m
                                    </h4>
                                    <small class="text-muted">{{ $slaPolicy->resolution_time_minutes }} minutos</small>
                                @else
                                    <p class="text-muted mb-0">No configurado</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold mb-3 border-bottom pb-2">Configuración</h6>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <h6 class="text-muted mb-2">Horario</h6>
                                @if($slaPolicy->business_hours_only)
                                    <span class="badge bg-info-subtle text-info">
                                        <i class="fas fa-business-time"></i> Horario comercial
                                    </span>
                                @else
                                    <span class="badge bg-info-subtle text-info">
                                        <i class="fas fa-clock"></i> 24/7
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <h6 class="text-muted mb-2">Estado</h6>
                                @if($slaPolicy->is_active)
                                    <span class="badge bg-success-subtle text-success">
                                        <i class="fas fa-check-circle"></i> Activa
                                    </span>
                                @else
                                    <span class="badge bg-info-subtle text-info">
                                        <i class="fas fa-times-circle"></i> Inactiva
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <h6 class="text-muted mb-2">Prioridad</h6>
                                <span class="badge bg-light text-dark fs-6">{{ $slaPolicy->priority ?? 0 }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Conversations Card -->
            @if(isset($slaPolicy->appliedConversations) && $slaPolicy->appliedConversations->count() > 0)
                <div class="card">
                    <div class="card-header p-4 border-bottom border-light">
                        <h6 class="mb-0 fw-bold">Conversaciones recientes</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Cliente</th>
                                        <th>Estado</th>
                                        <th>Creada</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($slaPolicy->appliedConversations->take(10) as $conversation)
                                        <tr>
                                            <td>#{{ $conversation->id }}</td>
                                            <td>{{ $conversation->customer->full_name ?? 'N/A' }}</td>
                                            <td>
                                                @if($conversation->status?->slug === 'open')
                                                    <span class="badge bg-success-subtle text-success">Abierta</span>
                                                @elseif($conversation->status?->slug === 'pending')
                                                    <span class="badge bg-warning-subtle text-warning">Pendiente</span>
                                                @elseif($conversation->status?->slug === 'resolved')
                                                    <span class="badge bg-info-subtle text-info">Resuelta</span>
                                                @else
                                                    <span class="badge bg-info-subtle text-info">{{ ucfirst($conversation->status?->name ?? 'N/A') }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $conversation->created_at->diffForHumans() }}</td>
                                            <td>
                                                <a href="{{ route('chat.conversations.show', $conversation) }}" class="btn btn-sm btn-outline-secondary">
                                                    Ver
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <!-- Statistics Card -->
            <div class="card mb-3">
                <div class="card-header p-4 border-bottom border-light">
                    <h6 class="mb-0 fw-bold">Estadísticas</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Creada</h6>
                        <p class="mb-0">{{ $slaPolicy->created_at->format('d/m/Y H:i') }}</p>
                        <small class="text-muted">{{ $slaPolicy->created_at->diffForHumans() }}</small>
                    </div>

                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Última actualización</h6>
                        <p class="mb-0">{{ $slaPolicy->updated_at->format('d/m/Y H:i') }}</p>
                        <small class="text-muted">{{ $slaPolicy->updated_at->diffForHumans() }}</small>
                    </div>

                    <hr>

                    <div>
                        <h6 class="text-muted mb-1">Conversaciones aplicadas</h6>
                        <h4 class="mb-0">{{ $slaPolicy->applied_conversations_count ?? 0 }}</h4>
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
                        <a href="{{ route('settings.chat.sla-policies.edit', $slaPolicy) }}" class="btn btn-outline-primary">
                            <i class="fas fa-edit"></i> Editar política
                        </a>
                        <a href="{{ route('settings.chat.sla-policies.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver al listado
                        </a>
                        <hr>
                        <button type="button"
                            class="btn btn-outline-danger delete-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#delete-modal"
                            data-url="{{ route('settings.chat.sla-policies.destroy', $slaPolicy) }}"
                            data-title="Eliminar política SLA: {{ $slaPolicy->name }}">
                            <i class="fas fa-trash"></i> Eliminar política
                        </button>
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
