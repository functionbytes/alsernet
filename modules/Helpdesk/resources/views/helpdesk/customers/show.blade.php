@extends('layouts.theme')

@section('title', $customer->name . ' - Helpdesk')

@section('page_header')
    @include('core::components.card', ['title' => 'Detalles del Cliente'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- Main Card -->
        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center bv-icon-circle-64"
                             >
                            {{ strtoupper(substr($customer->name, 0, 2)) }}
                        </div>
                        <div>
                            <h5 class="mb-1 fw-bold">{{ $customer->name }}</h5>
                            <p class="small mb-0 text-muted">{{ $customer->email }}</p>
                            <div class="mt-2">
                                @if($customer->is_banned)
                                    <span class="badge bg-danger">
                                        <i class="fa fa-ban me-1"></i> Suspendido
                                    </span>
                                @elseif($customer->email_verified_at)
                                    <span class="badge bg-success">
                                        <i class="fa fa-check me-1"></i> Verificado
                                    </span>
                                @else
                                    <span class="badge bg-primary">
                                        <i class="fa fa-exclamation-triangle me-1"></i> Pendiente
                                    </span>
                                @endif

                                @if($customer->country)
                                    <span class="badge bg-light text-dark border">
                                        {{ strtoupper($customer->country) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('manager.helpdesk.customers.edit', $customer) }}" class="btn btn-primary">
                            <i class="fa fa-pen me-1"></i> Editar
                        </a>
                        @can('helpdesk.customers.merge')
                        <button class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#mergeContactModal">
                            <i class="fas fa-code-merge me-1"></i> Fusionar
                        </button>
                        @endcan
                        <div class="dropdown">
                            <button class="btn btn-light dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="#">
                                        Nueva conversación
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="#">
                                      Exportar datos
                                    </a>
                                </li>
                                @if(!$customer->is_banned)
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ route('manager.helpdesk.customers.ban', $customer) }}"
                                              class="bv-d-inline">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-warning"
                                                    onclick="window.__confirm('¿Suspender a este cliente?', () => this.closest('form').submit()); return false;">
                                                Suspender cliente
                                            </button>
                                        </form>
                                    </li>
                                @else
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ route('manager.helpdesk.customers.unban', $customer) }}"
                                              class="bv-d-inline">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-success">
                                               Reactivar cliente
                                            </button>
                                        </form>
                                    </li>
                                @endif
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('manager.helpdesk.customers.destroy', $customer) }}"
                                          class="bv-d-inline needs-confirm"
                                          data-confirm-msg="¿Estás seguro de eliminar este cliente? Esta acción no se puede deshacer.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item">
                                            <i class="fas fa-trash me-2"></i> Eliminar Cliente
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            @if($customer->is_banned && $customer->ban_reason)
                <!-- Ban Warning -->
                <div class="card-body border-bottom">
                    <div class="alert alert-warning border-0 bg-warning-subtle mb-0">
                        <div class="d-flex align-items-start gap-2">
                            <i class="fa fa-exclamation-triangle fs-5"></i>
                            <div>
                                <small class="fw-semibold">Cliente suspendido:</small>
                                <p class="mb-0 mt-1 small">{{ $customer->ban_reason }}</p>
                                @if($customer->banned_at)
                                    <small class="text-muted d-block mt-1">
                                        Suspendido el {{ $customer->banned_at->format('d/m/Y H:i') }}
                                    </small>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Statistics Cards -->
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-subtle h-100 mb-0">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-2 small">Conversaciones</h6>
                                <h3 class="mb-1 fw-bold text-primary">{{ $customer->total_conversations ?? 0 }}</h3>
                                <small class="text-muted">Total de chats</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-subtle h-100 mb-0">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-2 small">Páginas visitadas</h6>
                                <h3 class="mb-1 fw-bold text-success">{{ $customer->total_page_visits ?? 0 }}</h3>
                                <small class="text-muted">Vistas totales</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-subtle h-100 mb-0">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-2 small">Último acceso</h6>
                                @if($customer->last_seen_at)
                                    <h3 class="mb-1 fw-bold text-info bv-text-base">
                                        {{ $customer->last_seen_at->diffForHumans() }}
                                    </h3>
                                    <small class="text-muted">{{ $customer->last_seen_at->format('d/m/Y H:i') }}</small>
                                @else
                                    <h3 class="mb-1 fw-bold text-muted bv-text-base">—</h3>
                                    <small class="text-muted">Sin registro</small>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-subtle h-100 mb-0">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-2 small">Miembro desde</h6>
                                <h3 class="mb-1 fw-bold text-dark bv-text-base">
                                    {{ $customer->created_at->diffForHumans() }}
                                </h3>
                                <small class="text-muted">{{ $customer->created_at->format('d/m/Y') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="card-body border-bottom">
                <div class="mb-3">
                    <h6 class="mb-1 fw-bold">Información de contacto</h6>
                    <p class="text-muted mb-0">Datos de comunicación del cliente</p>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small text-muted">Correo electrónico</label>
                        <p class="mb-0 fw-semibold">{{ $customer->email }}</p>
                        @if($customer->email_verified_at)
                            <small class="text-success">
                                <i class="fa fa-check-circle me-1"></i>
                                Verificado el {{ $customer->email_verified_at->format('d/m/Y H:i') }}
                            </small>
                        @else
                            <small class="text-warning">
                                <i class="fa fa-exclamation-triangle me-1"></i>
                                Email no verificado
                            </small>
                        @endif
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small text-muted">Teléfono</label>
                        <p class="mb-0 fw-semibold">{{ $customer->phone ?? '—' }}</p>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small text-muted">Idioma preferido</label>
                        <p class="mb-0 fw-semibold">
                            @if($customer->language === 'es') Español
                            @elseif($customer->language === 'en') Ingles
                            @elseif($customer->language === 'fr') Frances
                            @elseif($customer->language === 'pt') Portugues
                            @elseif($customer->language === 'de') Aleman
                            @elseif($customer->language === 'it') Italiano
                            @else — @endif
                        </p>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small text-muted">Zona horaria</label>
                        <p class="mb-0 fw-semibold">{{ $customer->timezone ?? 'Detección automática' }}</p>
                    </div>
                </div>
            </div>

            <!-- Location Information -->
            <div class="card-body border-bottom">
                <div class="mb-3">
                    <h6 class="mb-1 fw-bold">Ubicación</h6>
                    <p class="text-muted mb-0">Información geográfica del cliente</p>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small text-muted">País</label>
                        <p class="mb-0 fw-semibold">{{ $customer->country ? strtoupper($customer->country) : '—' }}</p>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small text-muted">Estado/Región</label>
                        <p class="mb-0 fw-semibold">{{ $customer->state ?? '—' }}</p>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small text-muted">Ciudad</label>
                        <p class="mb-0 fw-semibold">{{ $customer->city ?? '—' }}</p>
                    </div>
                </div>
            </div>

            <!-- Conversations -->
            <div class="card-body border-bottom">
                <div class="mb-3 d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="mb-1 fw-bold">Conversaciones recientes</h6>
                        <p class="text-muted mb-0">Historial de comunicación con el cliente</p>
                    </div>
                    <span class="badge bg-primary-subtle text-primary">
                        <i class="fa fa-comments"></i> {{ $customer->total_conversations ?? 0 }}
                    </span>
                </div>

                @if($customer->conversations && $customer->conversations->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Asunto</th>
                                    <th scope="col">Estado</th>
                                    <th scope="col">Fecha</th>
                                    <th scope="col" width="80"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($customer->conversations->take(5) as $conversation)
                                <tr>
                                    <td>
                                        <strong>{{ $conversation->subject ?? 'Sin asunto' }}</strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary">
                                            {{ $conversation->status ?? 'Abierta' }}
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $conversation->created_at->diffForHumans() }}</small>
                                    </td>
                                    <td class="text-end">
                                        <a href="#" class="btn btn-sm btn-light">
                                            <i class="fa fa-arrow-right"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($customer->conversations->count() > 5)
                        <div class="text-center mt-3">
                            <a href="#" class="btn btn-sm btn-outline-primary">
                                Ver todas ({{ $customer->total_conversations }})
                            </a>
                        </div>
                    @endif
                @else
                    <div class="text-center py-5">
                        <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3 bv-icon-circle-64-soft"
                             >
                            <i class="fa fa-inbox fs-3 text-muted"></i>
                        </div>
                        <h6 class="mb-1">Sin conversaciones</h6>
                        <p class="text-muted mb-0">Este cliente no ha iniciado ninguna conversación</p>
                    </div>
                @endif
            </div>

            @if($customer->internal_notes)
                <!-- Internal Notes -->
                <div class="card-body border-bottom">
                    <div class="mb-3">
                        <h6 class="mb-1 fw-bold">Notas internas</h6>
                        <p class="text-muted mb-0">Información privada sobre el cliente</p>
                    </div>

                    <div class="alert alert-info bg-info-subtle border-0 mb-0">
                        <p class="mb-0 small">{{ $customer->internal_notes }}</p>
                    </div>
                </div>
            @endif

            <!-- Session Information -->
            @if($customer->latestSession)
                <div class="card-body border-bottom">
                    <div class="mb-3">
                        <h6 class="mb-1 fw-bold">Última sesión</h6>
                        <p class="text-muted mb-0">Información de la última conexión del cliente</p>
                    </div>

                    <div class="row g-3">
                        @if($customer->latestSession->country)
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-muted">País de Conexión</label>
                                <p class="mb-0 fw-semibold">{{ $customer->latestSession->country }}</p>
                            </div>
                        @endif

                        @if($customer->latestSession->user_agent)
                            <div class="col-md-8">
                                <label class="form-label fw-semibold small text-muted">Dispositivo</label>
                                <p class="mb-0 small text-break">{{ $customer->latestSession->user_agent }}</p>
                            </div>
                        @endif

                        <div class="col-md-12">
                            <label class="form-label fw-semibold small text-muted">Fecha y Hora</label>
                            <p class="mb-0 fw-semibold">
                                {{ $customer->latestSession->created_at->format('d/m/Y H:i') }}
                                <small class="text-muted">({{ $customer->latestSession->created_at->diffForHumans() }})</small>
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Emails enviados -->
            @if(filled($customer->email))
            <div class="card-body border-bottom">
                <div class="mb-3 d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="mb-1 fw-bold">Emails enviados</h6>
                        <p class="text-muted mb-0">Correos enviados a este cliente por el sistema</p>
                    </div>
                    <a href="{{ route('manager.helpdesk.customers.emails', $customer) }}" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-envelope me-1"></i> Ver todos
                    </a>
                </div>

                @if($recentEmails->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Asunto</th>
                                    <th scope="col">Modulo</th>
                                    <th scope="col">Estado</th>
                                    <th scope="col">Fecha</th>
                                    <th scope="col" width="60"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentEmails as $mail)
                                <tr>
                                    <td>
                                        <strong>{{ Str::limit($mail->subject, 50) }}</strong>
                                        @if($mail->mailable_class)
                                            <br><small class="text-muted">{{ class_basename($mail->mailable_class) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($mail->module)
                                            <span class="badge bg-info-subtle text-info">{{ $mail->module }}</span>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $mail->status_color }}-subtle text-{{ $mail->status_color }}">
                                            {{ $mail->status_label }}
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $mail->created_at->diffForHumans() }}</small>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('helpdeskemaillog.show', $mail->uid) }}"
                                           class="btn btn-sm btn-light" target="_blank">
                                            <i class="fa fa-arrow-right"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-envelope-open fa-2x text-muted mb-2 d-block opacity-25"></i>
                        <p class="text-muted small mb-0">No se han enviado emails a este cliente</p>
                    </div>
                @endif
            </div>
            @endif

            <!-- System Information -->
            <div class="card-body">
                <div class="mb-3">
                    <h6 class="mb-1 fw-bold">Información del sistema</h6>
                    <p class="text-muted mb-0">Fechas y datos de registro</p>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small text-muted">Fecha de Registro</label>
                        <p class="mb-0 fw-semibold">{{ $customer->created_at->format('d/m/Y H:i') }}</p>
                        <small class="text-muted">{{ $customer->created_at->diffForHumans() }}</small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small text-muted">Última Actualización</label>
                        <p class="mb-0 fw-semibold">{{ $customer->updated_at->format('d/m/Y H:i') }}</p>
                        <small class="text-muted">{{ $customer->updated_at->diffForHumans() }}</small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small text-muted">ID del Cliente</label>
                        <p class="mb-0 fw-semibold">#{{ $customer->id }}</p>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <a href="{{ route('manager.helpdesk.customers.index') }}" class="btn btn-primary w-100">
                    Volver a la lista
                </a>
            </div>

        </div>

    </div>

    @can('helpdesk.customers.merge')
    {{-- Merge contact modal --}}
    <div class="modal fade" id="mergeContactModal" tabindex="-1" aria-labelledby="mergeContactModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mergeContactModalLabel">Fusionar contacto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning small mb-3">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        El contacto seleccionado será eliminado y sus conversaciones, sesiones e integraciones se transferirán al contacto actual (<strong>{{ $customer->name }}</strong>).
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="mergeSearchInput">Buscar contacto a fusionar</label>
                        <input type="text" id="mergeSearchInput" class="form-control"
                               placeholder="Nombre, email o teléfono..."
                               autocomplete="off">
                        <div class="form-text">El contacto encontrado será eliminado; sus datos se combinarán con el actual.</div>
                    </div>

                    <div id="mergeSearchResults"></div>

                    <div id="mergeSelectedContact" class="d-none">
                        <div class="border rounded p-3 bg-light-subtle">
                            <div class="d-flex align-items-center gap-2">
                                <div class="flex-shrink-0">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center bv-icon-circle-32 bg-warning text-white fw-bold" id="mergeSelectedAvatar"></div>
                                </div>
                                <div class="flex-grow-1 min-width-0">
                                    <p class="mb-0 fw-semibold text-truncate" id="mergeSelectedName"></p>
                                    <small class="text-muted d-block text-truncate" id="mergeSelectedEmail"></small>
                                    <small class="text-muted" id="mergeSelectedMeta"></small>
                                </div>
                                <button type="button" class="btn btn-sm btn-light flex-shrink-0" id="btnClearMerge">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-column gap-2">
                    <button type="button" class="btn btn-warning w-100 mb-2" id="btnConfirmMerge" disabled>
                        <i class="fas fa-code-merge me-1"></i> Fusionar contactos
                    </button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
    @endcan

@endsection

@push('scripts')
@can('helpdesk.customers.merge')
<script>
(function () {
    const BASE_CUSTOMER_ID = {{ $customer->id }};
    const SEARCH_URL = '{{ route('manager.helpdesk.customers.search') }}';
    const MERGE_URL = '{{ route('manager.helpdesk.customers.merge') }}';
    const SHOW_URL = '{{ route('manager.helpdesk.customers.show', $customer) }}';
    const CSRF = $('meta[name="csrf-token"]').attr('content');

    let selectedMergeeId = null;
    let searchTimer = null;

    const $modal = $('#mergeContactModal');
    const $input = $('#mergeSearchInput');
    const $results = $('#mergeSearchResults');
    const $selected = $('#mergeSelectedContact');
    const $confirmBtn = $('#btnConfirmMerge');

    // Debounced search
    $input.on('input', function () {
        clearTimeout(searchTimer);
        const q = $(this).val().trim();

        if (q.length < 2) {
            $results.empty();
            return;
        }

        searchTimer = setTimeout(() => performSearch(q), 300);
    });

    function performSearch(q) {
        $results.html('<div class="text-center py-2 text-muted small"><i class="fas fa-circle-notch fa-spin me-1"></i> Buscando...</div>');

        $.get(SEARCH_URL, { q: q, exclude: BASE_CUSTOMER_ID }, function (data) {
            renderResults(data.filter(c => c.id !== BASE_CUSTOMER_ID));
        }).fail(function () {
            $results.html('<div class="text-muted small py-2">Error al buscar contactos.</div>');
        });
    }

    function renderResults(contacts) {
        if (contacts.length === 0) {
            $results.html('<div class="text-muted small py-2">No se encontraron contactos.</div>');
            return;
        }

        const items = contacts.map(c => `
            <div class="d-flex align-items-center gap-2 py-2 px-1 rounded merge-result-item"
                 style="cursor:pointer;"
                 data-id="${c.id}"
                 data-name="${escHtml(c.name)}"
                 data-email="${escHtml(c.email || '')}"
                 data-phone="${escHtml(c.phone || '')}"
                 data-conversations="${c.total_conversations || 0}">
                <div class="flex-shrink-0 rounded-circle d-flex align-items-center justify-content-center bv-icon-circle-32 bg-secondary-subtle text-secondary fw-bold small">
                    ${c.name ? c.name.substring(0, 2).toUpperCase() : '??'}
                </div>
                <div class="flex-grow-1 min-width-0">
                    <p class="mb-0 fw-semibold small text-truncate">${escHtml(c.name)}</p>
                    <small class="text-muted text-truncate d-block">${escHtml(c.email || c.phone || '—')}</small>
                </div>
                <small class="text-muted flex-shrink-0">${c.total_conversations} conv.</small>
            </div>
        `).join('<hr class="my-0">');

        $results.html(`<div class="border rounded">${items}</div>`);

        $results.find('.merge-result-item').on('click', function () {
            selectContact($(this).data());
        });
    }

    function selectContact(data) {
        selectedMergeeId = data.id;

        $('#mergeSelectedAvatar').text(data.name.substring(0, 2).toUpperCase());
        $('#mergeSelectedName').text(data.name);
        $('#mergeSelectedEmail').text(data.email || data.phone || '—');
        $('#mergeSelectedMeta').text(`${data.conversations} conversaciones`);

        $results.empty();
        $input.val('').closest('.mb-3').addClass('d-none');
        $selected.removeClass('d-none');
        $confirmBtn.prop('disabled', false);
    }

    $('#btnClearMerge').on('click', function () {
        clearSelection();
    });

    function clearSelection() {
        selectedMergeeId = null;
        $selected.addClass('d-none');
        $input.closest('.mb-3').removeClass('d-none');
        $input.val('');
        $results.empty();
        $confirmBtn.prop('disabled', true);
    }

    $modal.on('hidden.bs.modal', function () {
        clearSelection();
    });

    $confirmBtn.on('click', function () {
        if (! selectedMergeeId) {
            return;
        }

        $confirmBtn.prop('disabled', true).html('<i class="fas fa-circle-notch fa-spin me-1"></i> Fusionando...');

        $.ajax({
            url: MERGE_URL,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF },
            contentType: 'application/json',
            data: JSON.stringify({
                base_customer_id: BASE_CUSTOMER_ID,
                mergee_customer_id: selectedMergeeId,
            }),
            success: function (res) {
                toastr.success(res.message || 'Contactos fusionados correctamente.');
                $modal.modal('hide');
                setTimeout(() => window.location.href = SHOW_URL, 800);
            },
            error: function (xhr) {
                const msg = xhr.responseJSON?.message || 'Error al fusionar los contactos.';
                toastr.error(msg);
                $confirmBtn.prop('disabled', false).html('<i class="fas fa-code-merge me-1"></i> Fusionar contactos');

                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    const errors = Object.values(xhr.responseJSON.errors).flat().join(' ');
                    toastr.warning(errors);
                }
            },
        });
    });

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
}());
</script>
@endcan
@endpush
