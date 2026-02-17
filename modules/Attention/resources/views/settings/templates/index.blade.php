@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Plantillas de Email - Integración con Mailer'])


        @if ($message = session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa fa-check-circle me-2"></i> {{ $message }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($message = session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa fa-circle-exclamation me-2"></i> {{ $message }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card">
            <div class="card-header border-bottom py-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-1 fw-bold text-dark">
                            Gestión de plantillas de correo
                        </h6>
                        <p class="mb-0 text-muted small">
                            Las plantillas se gestionan desde el módulo Mailer. Aquí puedes ver y seleccionar las disponibles.
                        </p>
                    </div>
                    <a href="{{ route('mailers.templates.index') }}" class="btn btn-primary" target="_blank">
                        <i class="fa fa-external-link me-1"></i> Ir a Mailer
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="alert alert-info mb-4">
                    <i class="fa fa-circle-info me-2"></i>
                    <strong>Integración con Mailer:</strong> El módulo Attention utiliza las plantillas configuradas en el módulo Mailer.
                    Para crear o editar plantillas, accede al módulo Mailer desde el enlace superior.
                </div>

                <h6 class="fw-bold mb-3">Plantillas disponibles para PQRSF</h6>

                @if(isset($availableTemplates) && count($availableTemplates) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($availableTemplates as $template)
                                    <tr>
                                        <td>
                                            <strong>{{ $template['text'] ?? $template['name'] }}</strong>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $template['description'] ?? 'Sin descripción' }}
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            @if($template['is_active'] ?? true)
                                                <span class="badge bg-success-subtle text-success">Activo</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Inactivo</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('mailers.templates.edit', $template['id']) }}"
                                               class="btn btn-sm btn-outline-primary" target="_blank">
                                                <i class="fa fa-edit"></i> Editar en Mailer
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fa fa-envelope fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay plantillas disponibles</h6>
                            <p class="text-muted mb-3">
                                Crea plantillas en el módulo Mailer para usarlas en las notificaciones de PQRSF
                            </p>
                            <a href="{{ route('mailers.templates.create') }}" class="btn btn-sm btn-primary" target="_blank">
                                <i class="fa fa-plus"></i> Crear primera plantilla en Mailer
                            </a>
                        </div>
                    </div>
                @endif

                <hr class="my-4">

                <h6 class="fw-bold mb-3">Uso de plantillas en Attention</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-body">
                                <h6 class="card-title text-primary">
                                    <i class="fa fa-circle-check me-2"></i>Notificación de recepción
                                </h6>
                                <p class="card-text small text-muted mb-2">
                                    Email enviado al ciudadano cuando crea una nueva solicitud.
                                </p>
                                <a href="{{ route('settings.attention.configurations.global') }}?tab=email" class="btn btn-sm btn-outline-secondary">
                                    Configurar
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-body">
                                <h6 class="card-title text-primary">
                                    <i class="fa fa-user-check me-2"></i>Notificación de asignación
                                </h6>
                                <p class="card-text small text-muted mb-2">
                                    Email enviado cuando la solicitud es asignada a un responsable.
                                </p>
                                <a href="{{ route('settings.attention.configurations.global') }}?tab=email" class="btn btn-sm btn-outline-secondary">
                                    Configurar
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-body">
                                <h6 class="card-title text-primary">
                                    <i class="fa fa-spinner me-2"></i>Notificación en proceso
                                </h6>
                                <p class="card-text small text-muted mb-2">
                                    Email enviado cuando la solicitud cambia a estado "En proceso".
                                </p>
                                <a href="{{ route('settings.attention.configurations.global') }}?tab=email" class="btn btn-sm btn-outline-secondary">
                                    Configurar
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-body">
                                <h6 class="card-title text-success">
                                    <i class="fa fa-check-double me-2"></i>Notificación de resolución
                                </h6>
                                <p class="card-text small text-muted mb-2">
                                    Email enviado cuando la solicitud es resuelta con una respuesta oficial.
                                </p>
                                <a href="{{ route('settings.attention.configurations.global') }}?tab=email" class="btn btn-sm btn-outline-secondary">
                                    Configurar
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-body">
                                <h6 class="card-title text-secondary">
                                    <i class="fa fa-lock me-2"></i>Notificación de cierre
                                </h6>
                                <p class="card-text small text-muted mb-2">
                                    Email enviado cuando la solicitud es cerrada definitivamente.
                                </p>
                                <a href="{{ route('settings.attention.configurations.global') }}?tab=email" class="btn btn-sm btn-outline-secondary">
                                    Configurar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-light border mt-4 mb-0">
                    <h6 class="fw-semibold mb-2">
                        <i class="fa fa-lightbulb text-warning me-2"></i>Tip: Variables dinámicas
                    </h6>
                    <p class="mb-0 small">
                        En las plantillas de Mailer puedes usar variables como:
                        <code>{{radicado}}</code>, <code>{{tipo}}</code>, <code>{{categoria}}</code>,
                        <code>{{estado}}</code>, <code>{{ciudadano}}</code>, <code>{{fecha}}</code>
                        para personalizar cada email.
                    </p>
                </div>
            </div>

            <div class="card-footer">
                <a href="{{ route('settings.attention.index') }}" class="btn btn-secondary w-100">
                    Volver a configuración
                </a>
            </div>
        </div>


@endsection
