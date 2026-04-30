@extends('layouts.theme')

@section('title', $webhook->name)

@section('content')

    @include('core::components.alerts')

    <div class="row">
        <div class="col-lg-8">
            <!-- Webhook Details Card -->
            <div class="card mb-3">
                <div class="card-header p-4 border-bottom border-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">{{ $webhook->name }}</h5>
                            @if($webhook->webhook_type === 0)
                                <span class="badge bg-primary-subtle text-primary">Nivel de cuenta</span>
                            @else
                                <span class="badge bg-info-subtle text-info">Nivel de buzón</span>
                            @endif
                        </div>
                        <div>
                            <a href="{{ route('settings.chat.webhooks.edit', $webhook) }}" class="btn btn-primary">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">Información básica</h6>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Nombre</h6>
                            <p class="mb-0"><strong>{{ $webhook->name }}</strong></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Tipo</h6>
                            <p class="mb-0">
                                @if($webhook->webhook_type === 0)
                                    <span class="badge bg-primary-subtle text-primary">Nivel de cuenta</span>
                                @else
                                    <span class="badge bg-info-subtle text-info">Nivel de buzón</span>
                                @endif
                            </p>
                        </div>
                        @if($webhook->inbox)
                            <div class="col-md-6">
                                <h6 class="text-muted mb-2">Buzón asociado</h6>
                                <p class="mb-0">{{ $webhook->inbox->name }}</p>
                            </div>
                        @endif
                        <div class="col-12">
                            <h6 class="text-muted mb-2">URL</h6>
                            <div class="card bg-light border-0">
                                <div class="card-body py-2">
                                    <code class="text-break">{{ $webhook->url }}</code>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold mb-3 border-bottom pb-2">Eventos suscritos ({{ count($webhook->subscriptions ?? []) }})</h6>

                    @php
                        $eventLabels = [
                            'conversation_created' => 'Conversación creada',
                            'conversation_updated' => 'Conversación actualizada',
                            'conversation_status_changed' => 'Estado de conversación cambiado',
                            'message_created' => 'Mensaje creado',
                            'message_updated' => 'Mensaje actualizado',
                            'conversation_opened' => 'Conversación abierta',
                            'conversation_resolved' => 'Conversación resuelta',
                            'customer_created' => 'Cliente creado',
                            'customer_updated' => 'Cliente actualizado',
                        ];
                    @endphp

                    @if(count($webhook->subscriptions ?? []) > 0)
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($webhook->subscriptions as $event)
                                <span class="badge bg-info-subtle text-info">
                                    <i class="fas fa-check-circle me-1"></i>{{ $eventLabels[$event] ?? $event }}
                                </span>
                            @endforeach
                        </div>
                    @else
                        <div class="alert bg-warning-subtle border-warning mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No hay eventos configurados para este webhook
                        </div>
                    @endif
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
                        <p class="mb-0">{{ $webhook->created_at->format('d/m/Y H:i') }}</p>
                        <small class="text-muted">{{ $webhook->created_at->diffForHumans() }}</small>
                    </div>

                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Última actualización</h6>
                        <p class="mb-0">{{ $webhook->updated_at->format('d/m/Y H:i') }}</p>
                        <small class="text-muted">{{ $webhook->updated_at->diffForHumans() }}</small>
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
                        <a href="{{ route('settings.chat.webhooks.edit', $webhook) }}" class="btn btn-outline-primary">
                            <i class="fas fa-edit"></i> Editar webhook
                        </a>
                        <a href="{{ route('settings.chat.webhooks.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver al listado
                        </a>
                        <hr>
                        <button type="button"
                            class="btn btn-outline-danger delete-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#delete-modal"
                            data-url="{{ route('settings.chat.webhooks.destroy', $webhook) }}"
                            data-title="Eliminar webhook: {{ $webhook->name }}">
                            <i class="fas fa-trash"></i> Eliminar webhook
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
